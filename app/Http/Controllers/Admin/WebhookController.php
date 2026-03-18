<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\WebhookConfiguration;
use Pterodactyl\Http\Requests\Admin\WebhookFormRequest;

class WebhookController extends Controller
{
    public function __construct(
        private AlertsMessageBag $alert,
        private ViewFactory $view,
    ) {}

    public function index(): View
    {
        return $this->view->make('admin.webhooks.index', [
            'webhooks' => WebhookConfiguration::withCount('webhooks')->get(),
        ]);
    }

    public function create(): View
    {
        return $this->view->make('admin.webhooks.new');
    }

    public function store(WebhookFormRequest $request): RedirectResponse
    {
        $webhook = WebhookConfiguration::create($request->normalize());

        $this->alert->success('Webhook configuration created successfully.')->flash();

        return redirect()->route('admin.webhooks.view', $webhook->id);
    }

    public function view(int $id): View
    {
        $webhook = WebhookConfiguration::withCount('webhooks')->findOrFail($id);

        return $this->view->make('admin.webhooks.view', [
            'webhook' => $webhook,
            'executions' => $webhook->webhooks()->orderByDesc('created_at')->paginate(25),
        ]);
    }

    public function update(WebhookFormRequest $request, int $id): RedirectResponse
    {
        $webhook = WebhookConfiguration::findOrFail($id);
        $webhook->update($request->normalize());

        $this->alert->success('Webhook configuration updated successfully.')->flash();

        return redirect()->route('admin.webhooks.view', $webhook->id);
    }

    public function delete(int $id): RedirectResponse
    {
        $webhook = WebhookConfiguration::findOrFail($id);
        $webhook->delete();

        $this->alert->success('Webhook configuration deleted successfully.')->flash();

        return redirect()->route('admin.webhooks');
    }
}
