<?php

namespace Pterodactyl\Http\Controllers\Admin\Nests;

use Pterodactyl\Models\Egg;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use Pterodactyl\Services\Eggs\Sharing\EggExporterService;
use Pterodactyl\Services\Eggs\Sharing\EggImporterService;
use Pterodactyl\Http\Requests\Admin\Egg\EggImportFormRequest;
use Pterodactyl\Http\Requests\Admin\Egg\EggImportUrlFormRequest;
use Pterodactyl\Services\Eggs\Sharing\EggUpdateImporterService;
use Pterodactyl\Exceptions\Model\InvalidFileUploadException;
use Exception;

class EggShareController extends Controller
{
    /**
     * EggShareController constructor.
     */
    public function __construct(
        protected AlertsMessageBag $alert,
        protected EggExporterService $exporterService,
        protected EggImporterService $importerService,
        protected EggUpdateImporterService $updateImporterService,
    ) {}

    /**
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function export(Egg $egg): Response
    {
        $filename = trim(preg_replace('/\W/', '-', kebab_case($egg->name)), '-');

        return response($this->exporterService->handle($egg->id), 200, [
            'Content-Transfer-Encoding' => 'binary',
            'Content-Description' => 'File Transfer',
            'Content-Disposition' => 'attachment; filename=egg-' . $filename . '.json',
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Import a new service option using an XML file.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Egg\BadJsonFormatException
     * @throws \Pterodactyl\Exceptions\Service\InvalidFileUploadException
     */
    public function import(EggImportFormRequest $request): RedirectResponse
    {
        $egg = $this->importerService->handle($request->file('import_file'), $request->input('import_to_nest'));
        $this->alert->success(trans('admin/nests.eggs.notices.imported'))->flash();

        return redirect()->route('admin.nests.egg.view', ['egg' => $egg->id]);
    }

    /**
     * Import a new service option from a URL.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Egg\BadJsonFormatException
     * @throws \Pterodactyl\Exceptions\Service\InvalidFileUploadException
     */
    public function importFromUrl(EggImportUrlFormRequest $request): RedirectResponse
    {
        try {
            $url = $request->input('import_file_url');
            $parsed_url = parse_url($url);

            // Validate URL scheme is HTTPS only
            if (!is_array($parsed_url) || !isset($parsed_url['scheme']) || $parsed_url['scheme'] !== 'https') {
                $this->alert->danger('The Egg import URL must use HTTPS.')->flash();
                return redirect()->back();
            }

            // Validate host exists
            if (!isset($parsed_url['host']) || empty($parsed_url['host'])) {
                $this->alert->danger('The Egg import URL has an invalid host.')->flash();
                return redirect()->back();
            }

            // Block internal/private IPs to prevent SSRF
            $ip = gethostbyname($parsed_url['host']);
            if ($ip === $parsed_url['host'] || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_NO_LOOPBACK) === false) {
                $this->alert->danger('The Egg import URL resolves to a private or invalid IP address.')->flash();
                return redirect()->back();
            }

            // Validate against allowed hosts whitelist
            $allowed_hosts = array_filter(array_map('trim', explode(',', config('app.allowed_egg_hosts', env('ALLOWED_EGG_HOSTS', '')))));
            if (!empty($allowed_hosts) && !in_array($parsed_url['host'], $allowed_hosts)) {
                $this->alert->danger('The Egg import URL is not from an allowed host.')->flash();
                return redirect()->back();
            }

            // Use Laravel HTTP client with strict timeout and redirect limits
            $response = Http::timeout(10)
                ->maxRedirects(2)
                ->withOptions([
                    'allow_redirects' => [
                        'max' => 2,
                        'strict' => true,
                        'protocols' => ['https'],
                    ],
                ])
                ->get($url);

            if ($response->failed()) {
                $this->alert->danger('Fetching the Egg from the URL failed (HTTP ' . $response->status() . ').')->flash();
                return redirect()->back();
            }

            // Validate response is valid JSON before processing
            $body = $response->body();
            if (json_decode($body) === null) {
                $this->alert->danger('The fetched content is not valid JSON.')->flash();
                return redirect()->back();
            }

            $egg = $this->importerService->handleFromString($body, $request->input('import_to_nest'));
            $this->alert->success(trans('admin/nests.eggs.notices.imported'))->flash();

            return redirect()->route('admin.nests.egg.view', ['egg' => $egg->id]);
        } catch (\Throwable $e) {
            report($e);
            $this->alert->danger('Failed to import egg from URL. Please try again.')->flash();
            return redirect()->back();
        }
    }

    /**
     * Update an existing Egg using a new imported file.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Pterodactyl\Exceptions\Service\Egg\BadJsonFormatException
     * @throws \Pterodactyl\Exceptions\Service\InvalidFileUploadException
     */
    public function update(EggImportFormRequest $request, Egg $egg): RedirectResponse
    {
        $this->updateImporterService->handle($egg, $request->file('import_file'));
        $this->alert->success(trans('admin/nests.eggs.notices.updated_via_import'))->flash();

        return redirect()->route('admin.nests.egg.view', ['egg' => $egg]);
    }
}
