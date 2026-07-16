<?php

namespace App\Http\Controllers;

use App\Http\Concerns\BuildsProjectBillingEntry;
use App\Http\Requests\ExportBillingRequest;
use App\Models\Client;
use App\Services\HolidayService;
use App\Services\PdfGenerator;
use Symfony\Component\HttpFoundation\Response;

class ExportSharedBillingHandler
{
    use BuildsProjectBillingEntry;

    public function __invoke(ExportBillingRequest $request, string $token, HolidayService $holidays, PdfGenerator $pdf): Response
    {
        $client = Client::findByShareTokenOrFail($token);
        $projects = $this->resolveExportProjects($client, $request);

        $sourceUrl = route('shares.show', $token);

        return $this->buildBillingExportResponse($client, $projects, $client->user->name, null, $request->validated('from'), $request->validated('to'), $holidays, $pdf, $sourceUrl);
    }
}
