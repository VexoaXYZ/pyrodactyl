<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Webhook extends Model
{
    public const RESOURCE_NAME = 'webhook';

    public const UPDATED_AT = null;

    protected $table = 'webhooks';

    protected bool $immutableDates = true;

    protected $fillable = [
        'webhook_configuration_id',
        'event',
        'endpoint',
        'payload_sent',
        'response_code',
        'successful',
        'error',
    ];

    protected $casts = [
        'webhook_configuration_id' => 'integer',
        'response_code' => 'integer',
        'successful' => 'boolean',
    ];

    public static array $validationRules = [
        'webhook_configuration_id' => 'required|exists:webhook_configurations,id',
        'event' => 'required|string|max:100',
        'endpoint' => 'required|string|max:500',
        'payload_sent' => 'nullable|string',
        'response_code' => 'nullable|integer|min:0|max:65535',
        'successful' => 'boolean',
        'error' => 'nullable|string',
    ];

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(WebhookConfiguration::class, 'webhook_configuration_id');
    }
}
