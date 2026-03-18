<?php

namespace Pterodactyl\Http\Requests\Api\Application\Webhooks;

use Pterodactyl\Services\Acl\Api\AdminAcl as Acl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class GetWebhooksRequest extends ApplicationApiRequest
{
    protected ?string $resource = Acl::RESOURCE_WEBHOOKS;

    protected int $permission = Acl::READ;
}
