<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Webhook;

class WebhookTransformer extends BaseTransformer
{
    public function getResourceName(): string
    {
        return Webhook::RESOURCE_NAME;
    }

    public function transform(Webhook $model): array
    {
        return [
            'id' => $model->id,
            'webhook_configuration_id' => $model->webhook_configuration_id,
            'event' => $model->event,
            'endpoint' => $model->endpoint,
            'response_code' => $model->response_code,
            'successful' => $model->successful,
            'error' => $model->error,
            'created_at' => $this->formatTimestamp($model->created_at),
        ];
    }
}
