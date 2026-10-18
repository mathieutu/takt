<?php

namespace App\Http\Controllers;

use App\Http\Concerns\BuildsProjectBillingEntry;
use App\Http\Requests\ExportBillingRequest;
use App\Models\Client;
use App\Services\HolidayService;
use App\Services\PdfGenerator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Billing PDF behind a temporary signed URL (see the MCP `billing_recap` tool): the signature, issued
 * only after an ownership check, stands in for the session and the EnsureUserOwnsResource middleware.
 */
class SignedBillingExportHandler
{
    use BuildsProjectBillingEntry;

    public function __invoke(ExportBillingRequest $request, Client $client, HolidayService $holidays, PdfGenerator $pdf): Response
    {
        $projects = $this->resolveExportProjects($client, $request);

        return $this->buildBillingExportResponse(
            $client, $projects, $client->user->name, $client->user->email,
            $request->validated('from'), $request->validated('to'), $holidays, $pdf, config('app.url'),
        );
    }
}
