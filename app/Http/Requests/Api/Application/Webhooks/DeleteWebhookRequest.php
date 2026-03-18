<?php

namespace Pterodactyl\Http\Requests\Api\Application\Webhooks;

use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteWebhookRequest extends ApplicationApiRequest
{
    protected ?string $resource = AdminAcl::RESOURCE_WEBHOOKS;

    protected int $permission = AdminAcl::WRITE;
}
