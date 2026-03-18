<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Pterodactyl\Models\User;
use Pterodactyl\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Pterodactyl\Facades\Activity;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Acl\Api\AdminAcl;

class AuthTokenController extends Controller
{
    public function __construct(
        private Google2FA $google2FA,
        private Encrypter $encrypter,
    ) {}

    /**
     * Exchange email/username + password for an API bearer token.
     * Requires a valid Application API key with auth_tokens permission.
     * Supports 2FA via authentication_code parameter.
     *
     * POST /api/auth/token
     */
    public function create(Request $request): JsonResponse
    {
        // Verify Application API key from Authorization header
        $apiKey = $this->validateApplicationKey($request);
        if ($apiKey instanceof JsonResponse) {
            return $apiKey;
        }

        $request->validate([
            'user' => 'required|string',
            'password' => 'required|string',
            'authentication_code' => 'nullable|string',
            'description' => 'nullable|string|max:500',
        ]);

        $field = str_contains($request->input('user'), '@') ? 'email' : 'username';
        $user = User::query()->where($field, $request->input('user'))->first();

        if (!$user || !password_verify($request->input('password'), $user->password)) {
            return new JsonResponse([
                'error' => 'InvalidCredentials',
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        // Handle 2FA if enabled
        if ($user->use_totp) {
            $code = $request->input('authentication_code');

            if (empty($code)) {
                return new JsonResponse([
                    'error' => 'TwoFactorRequired',
                    'message' => 'This account has two-factor authentication enabled. Provide authentication_code.',
                ], 403);
            }

            $decrypted = $this->encrypter->decrypt($user->totp_secret);
            if (!$this->google2FA->verifyKey($decrypted, $code, config('pterodactyl.auth.2fa.window'))) {
                return new JsonResponse([
                    'error' => 'InvalidTwoFactor',
                    'message' => 'The two-factor authentication code provided is invalid.',
                ], 401);
            }
        }

        $description = $request->input('description', 'API Token');
        $token = $user->createToken($description, []);

        Activity::event('user:api-key.create-via-auth')
            ->subject($token->accessToken)
            ->property('identifier', $token->accessToken->identifier)
            ->log();

        return new JsonResponse([
            'data' => [
                'token' => $token->plainTextToken,
                'token_id' => $token->accessToken->identifier,
                'user' => [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'username' => $user->username,
                    'email' => $user->email,
                    'name_first' => $user->name_first,
                    'name_last' => $user->name_last,
                    'language' => $user->language,
                    'root_admin' => $user->root_admin,
                ],
            ],
        ]);
    }

    /**
     * Validate the Application API key from the Authorization header.
     * Returns the ApiKey model on success, or a JsonResponse error.
     */
    private function validateApplicationKey(Request $request): ApiKey|JsonResponse
    {
        $bearer = $request->bearerToken();

        if (empty($bearer)) {
            return new JsonResponse([
                'error' => 'MissingApiKey',
                'message' => 'An Application API key is required. Pass it as a Bearer token.',
            ], 401);
        }

        $apiKey = ApiKey::findToken($bearer);

        if (!$apiKey || $apiKey->key_type !== ApiKey::TYPE_APPLICATION) {
            return new JsonResponse([
                'error' => 'InvalidApiKey',
                'message' => 'The provided API key is invalid or not an Application API key.',
            ], 401);
        }

        // Check that this key has auth_tokens permission
        if (!AdminAcl::check($apiKey, AdminAcl::RESOURCE_AUTH_TOKENS, AdminAcl::WRITE)) {
            return new JsonResponse([
                'error' => 'InsufficientPermission',
                'message' => 'This API key does not have permission to issue auth tokens.',
            ], 403);
        }

        // Update last used timestamp
        $apiKey->update(['last_used_at' => now()]);

        return $apiKey;
    }
}
