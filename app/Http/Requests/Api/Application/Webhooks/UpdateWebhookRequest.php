<?php

namespace Pterodactyl\Http\Requests\Api\Application\Webhooks;

use Pterodactyl\Models\WebhookConfiguration;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class UpdateWebhookRequest extends ApplicationApiRequest
{
    protected ?string $resource = AdminAcl::RESOURCE_WEBHOOKS;

    protected int $permission = AdminAcl::WRITE;

    public function rules(?array $rules = null): array
    {
        $rules = $rules ?? WebhookConfiguration::getRules();

        // Make all fields optional for updates
        return collect($rules)->map(function ($rule) {
            $rule = is_array($rule) ? $rule : explode('|', $rule);
            $rule = array_filter($rule, fn ($r) => $r !== 'required');
            array_unshift($rule, 'sometimes');
            return $rule;
        })->toArray();
    }
}
