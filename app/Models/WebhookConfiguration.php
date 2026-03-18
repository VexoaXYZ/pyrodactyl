<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class WebhookConfiguration extends Model
{
    use SoftDeletes;

    public const RESOURCE_NAME = 'webhook_configuration';

    protected $table = 'webhook_configurations';

    protected bool $immutableDates = true;

    protected $fillable = [
        'endpoint',
        'description',
        'events',
        'type',
        'payload',
        'headers',
        'is_enabled',
    ];

    protected $casts = [
        'events' => 'array',
        'payload' => 'array',
        'headers' => 'array',
        'is_enabled' => 'boolean',
    ];

    public static array $validationRules = [
        'endpoint' => 'required|url|max:500',
        'description' => 'nullable|string|max:191',
        'events' => 'required',
        'type' => 'required|string|in:regular,discord',
        'payload' => 'nullable',
        'headers' => 'nullable',
        'is_enabled' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class, 'webhook_configuration_id');
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query->whereJsonContains('events', $event);
    }

    public function isDiscord(): bool
    {
        return $this->type === 'discord';
    }
}
