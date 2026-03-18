<?php

namespace Pterodactyl\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Pterodactyl\Models\Webhook;

class PruneWebhookLogsCommand extends Command
{
    protected $signature = 'p:webhooks:prune';

    protected $description = 'Prune old webhook execution logs from the database.';

    public function handle(): void
    {
        $days = config('panel.webhook.prune_days', 30);

        $count = Webhook::where('created_at', '<', Carbon::now()->subDays($days))->delete();

        $this->info("Pruned {$count} webhook log records older than {$days} days.");
    }
}
