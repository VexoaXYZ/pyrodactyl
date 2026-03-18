<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Http\Controllers\Controller;
use Spatie\Health\Health;
use Spatie\Health\Commands\RunHealthChecksCommand;
use Illuminate\Support\Facades\Artisan;
use Spatie\Health\ResultStores\ResultStore;

class HealthController extends Controller
{
    public function __construct(
        private ViewFactory $view,
    ) {}

    public function index(): View
    {
        Artisan::call(RunHealthChecksCommand::class);

        $checkResults = app(ResultStore::class)->latestResults();

        return $this->view->make('admin.health.index', [
            'lastRanAt' => $checkResults?->finishedAt,
            'checkResults' => $checkResults?->storedCheckResults ?? collect(),
        ]);
    }

    public function api(): JsonResponse
    {
        Artisan::call(RunHealthChecksCommand::class);

        $checkResults = app(ResultStore::class)->latestResults();
        $results = $checkResults?->storedCheckResults ?? collect();

        $allOk = $results->every(fn ($r) => $r->status === 'ok');

        return response()->json([
            'healthy' => $allOk,
            'finished_at' => $checkResults?->finishedAt?->toIso8601String(),
            'checks' => $results->map(fn ($r) => [
                'name' => $r->label,
                'status' => $r->status,
                'message' => $r->shortSummary,
                'meta' => $r->meta,
            ])->values(),
        ], $allOk ? 200 : 503);
    }
}
