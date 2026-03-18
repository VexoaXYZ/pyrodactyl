<?php

namespace Pterodactyl\Observers;

use Pterodactyl\Models\Server;
use Pterodactyl\Models\WebhookConfiguration;
use Pterodactyl\Jobs\ProcessWebhook;

class ServerWebhookObserver
{
    public function created(Server $server): void
    {
        $this->dispatch('server:created', $server);
    }

    public function updated(Server $server): void
    {
        $this->dispatch('server:updated', $server);
    }

    public function deleted(Server $server): void
    {
        $this->dispatch('server:deleted', $server);
    }

    private function dispatch(string $event, Server $server): void
    {
        $serverData = [
            'id' => $server->id,
            'uuid' => $server->uuid,
            'name' => $server->name,
            'node_id' => $server->node_id,
            'description' => $server->description,
        ];

        $configurations = WebhookConfiguration::query()
            ->enabled()
            ->forEvent($event)
            ->get();

        foreach ($configurations as $config) {
            ProcessWebhook::dispatch($config, $event, $serverData);
        }
    }
}
