<?php

namespace Pterodactyl\Transformers\Api\Application;

use League\Fractal\Resource\Collection;
use League\Fractal\Resource\NullResource;
use Pterodactyl\Models\WebhookConfiguration;
use Pterodactyl\Services\Acl\Api\AdminAcl;

class WebhookConfigurationTransformer extends BaseTransformer
{
    protected array $availableIncludes = ['webhooks'];

    public function getResourceName(): string
    {
        return WebhookConfiguration::RESOURCE_NAME;
    }

    public function transform(WebhookConfiguration $model): array
    {
        return [
            'id' => $model->id,
            'endpoint' => $model->endpoint,
            'description' => $model->description,
            'events' => $model->events,
            'type' => $model->type,
            'payload' => $model->payload,
            'headers' => $model->headers,
            'is_enabled' => $model->is_enabled,
            'created_at' => $this->formatTimestamp($model->created_at),
            'updated_at' => $this->formatTimestamp($model->updated_at),
        ];
    }

    public function includeWebhooks(WebhookConfiguration $model): Collection|NullResource
    {
        if (!$this->authorize(AdminAcl::RESOURCE_WEBHOOKS)) {
            return $this->null();
        }

        $model->loadMissing('webhooks');

        return $this->collection($model->getRelation('webhooks'), $this->makeTransformer(WebhookTransformer::class), 'webhook');
    }
}
