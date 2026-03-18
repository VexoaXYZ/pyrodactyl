<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Webhooks;

use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;
use Pterodactyl\Models\WebhookConfiguration;
use Pterodactyl\Transformers\Api\Application\WebhookConfigurationTransformer;
use Pterodactyl\Http\Requests\Api\Application\Webhooks\GetWebhooksRequest;
use Pterodactyl\Http\Requests\Api\Application\Webhooks\StoreWebhookRequest;
use Pterodactyl\Http\Requests\Api\Application\Webhooks\UpdateWebhookRequest;
use Pterodactyl\Http\Requests\Api\Application\Webhooks\DeleteWebhookRequest;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

class WebhookConfigurationController extends ApplicationApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(GetWebhooksRequest $request): array
    {
        $webhooks = QueryBuilder::for(WebhookConfiguration::query())
            ->allowedFilters(['type', 'is_enabled'])
            ->allowedSorts(['id', 'created_at'])
            ->paginate($request->query('per_page') ?? 50);

        return $this->fractal->collection($webhooks)
            ->transformWith($this->getTransformer(WebhookConfigurationTransformer::class))
            ->toArray();
    }

    public function view(GetWebhooksRequest $request, WebhookConfiguration $webhook): array
    {
        return $this->fractal->item($webhook)
            ->transformWith($this->getTransformer(WebhookConfigurationTransformer::class))
            ->toArray();
    }

    public function store(StoreWebhookRequest $request): JsonResponse
    {
        $webhook = WebhookConfiguration::create($request->validated());

        return $this->fractal->item($webhook)
            ->transformWith($this->getTransformer(WebhookConfigurationTransformer::class))
            ->addMeta([
                'resource' => route('api.application.webhooks.view', [
                    'webhook' => $webhook->id,
                ]),
            ])
            ->respond(201);
    }

    public function update(UpdateWebhookRequest $request, WebhookConfiguration $webhook): array
    {
        $webhook->update($request->validated());

        return $this->fractal->item($webhook->fresh())
            ->transformWith($this->getTransformer(WebhookConfigurationTransformer::class))
            ->toArray();
    }

    public function delete(DeleteWebhookRequest $request, WebhookConfiguration $webhook): JsonResponse
    {
        $webhook->delete();

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
