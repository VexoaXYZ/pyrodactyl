<?php

namespace Pterodactyl\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Models\Webhook;
use Pterodactyl\Models\WebhookConfiguration;

class ProcessWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(
        private WebhookConfiguration $config,
        private string $event,
        private array $serverData,
    ) {}

    public function handle(): void
    {
        $payload = $this->buildPayload();
        $headers = array_merge(
            ['Content-Type' => 'application/json'],
            $this->config->headers ?? [],
        );

        $payloadJson = json_encode($payload);

        try {
            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->post($this->config->endpoint, $payload);

            Webhook::create([
                'webhook_configuration_id' => $this->config->id,
                'event' => $this->event,
                'endpoint' => $this->config->endpoint,
                'payload_sent' => $payloadJson,
                'response_code' => $response->status(),
                'successful' => $response->successful(),
                'error' => $response->successful() ? null : $response->body(),
            ]);

            if (!$response->successful()) {
                throw new Exception("Webhook returned HTTP {$response->status()}");
            }
        } catch (Exception $exception) {
            Webhook::create([
                'webhook_configuration_id' => $this->config->id,
                'event' => $this->event,
                'endpoint' => $this->config->endpoint,
                'payload_sent' => $payloadJson,
                'response_code' => null,
                'successful' => false,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function buildPayload(): array
    {
        if ($this->config->isDiscord()) {
            return $this->buildDiscordPayload();
        }

        if (!empty($this->config->payload)) {
            return array_merge($this->config->payload, [
                'event' => $this->event,
                'server' => $this->serverData,
                'fired_at' => now()->toIso8601String(),
            ]);
        }

        return [
            'event' => $this->event,
            'server' => $this->serverData,
            'fired_at' => now()->toIso8601String(),
        ];
    }

    private function buildDiscordPayload(): array
    {
        $colors = [
            'server:created' => 0x2ECC71,
            'server:updated' => 0x3498DB,
            'server:deleted' => 0xE74C3C,
        ];

        $titles = [
            'server:created' => 'Server Created',
            'server:updated' => 'Server Updated',
            'server:deleted' => 'Server Deleted',
        ];

        return [
            'embeds' => [
                [
                    'title' => $titles[$this->event] ?? $this->event,
                    'color' => $colors[$this->event] ?? 0x95A5A6,
                    'fields' => [
                        [
                            'name' => 'Server Name',
                            'value' => $this->serverData['name'] ?? 'Unknown',
                            'inline' => true,
                        ],
                        [
                            'name' => 'UUID',
                            'value' => $this->serverData['uuid'] ?? 'Unknown',
                            'inline' => true,
                        ],
                        [
                            'name' => 'Node ID',
                            'value' => (string) ($this->serverData['node_id'] ?? 'Unknown'),
                            'inline' => true,
                        ],
                    ],
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ];
    }
}
