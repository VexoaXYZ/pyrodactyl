<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Pterodactyl\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Pterodactyl\Facades\Activity;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Http\Controllers\Controller;

class AuthTokenController extends Controller
{
    public function __construct(
        private Google2FA $google2FA,
        private Encrypter $encrypter,
    ) {}

    /**
     * Exchange email/username + password for an API bearer token.
     * Supports 2FA via authentication_code parameter.
     *
     * POST /api/auth/token
     */
    public function create(Request $request): JsonResponse
    {
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
}
