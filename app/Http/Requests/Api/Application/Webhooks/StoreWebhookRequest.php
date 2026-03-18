<?php

namespace Pterodactyl\Http\Requests\Api\Application\Webhooks;

use Pterodactyl\Models\WebhookConfiguration;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreWebhookRequest extends ApplicationApiRequest
{
    protected ?string $resource = AdminAcl::RESOURCE_WEBHOOKS;

    protected int $permission = AdminAcl::WRITE;

    public function rules(?array $rules = null): array
    {
        return $rules ?? WebhookConfiguration::getRules();
    }
}
