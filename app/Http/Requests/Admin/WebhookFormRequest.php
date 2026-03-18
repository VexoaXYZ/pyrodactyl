<?php

namespace Pterodactyl\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;

class WebhookFormRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'endpoint' => 'required|url|max:500',
            'description' => 'nullable|string|max:191',
            'events' => 'required|array|min:1',
            'events.*' => 'string|in:server:created,server:updated,server:deleted',
            'type' => 'required|string|in:regular,discord',
            'headers' => 'nullable|array',
            'is_enabled' => 'required|boolean',
        ];
    }

    public function normalize(?array $only = null): array
    {
        // Exclude wildcard keys like 'events.*' which break data_get/Arr::set
        return $this->only($only ?? array_filter(
            array_keys($this->rules()),
            fn ($key) => !str_contains($key, '.')
        ));
    }

    protected function getValidatorInstance(): Validator
    {
        // Decode headers JSON string to array
        if ($this->filled('headers') && is_string($this->input('headers'))) {
            $decoded = json_decode($this->input('headers'), true);
            $this->merge(['headers' => is_array($decoded) ? $decoded : null]);
        } elseif (!$this->filled('headers')) {
            $this->merge(['headers' => null]);
        }

        // Convert is_enabled string to boolean
        $this->merge([
            'is_enabled' => filter_var($this->input('is_enabled', true), FILTER_VALIDATE_BOOLEAN),
        ]);

        return parent::getValidatorInstance();
    }
}
