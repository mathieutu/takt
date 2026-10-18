<?php

namespace App\Mcp\Tools;

use App\Http\Concerns\BuildsProjectBillingEntry;
use App\Models\Client;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\URL;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Billing recap of a client over a range of months (YYYY-MM, inclusive). Returns as JSON, per project and per month, the worked days (worked_days, billable or not) including the billable ones (billable_days, the only ones counted in the amounts), the worked/invoiced/discounted amounts, the amount left to invoice (to_invoice, gross) and left to collect (to_pay, net), plus duplicate_billing_days (days where more than one day is billed to the client across its projects: report them to the user and fix them with upsert_entries), plus the opening balance (opening_to_invoice: amount left to invoice before the period). Non-billable entries are excluded from the amounts. billing_page_url is the direct link to the client billing page in Takt (login required): always give it to the user along with the recap. pdf_url downloads the same recap as a PDF, valid for 10 minutes, without authentication: share it only with the user.')]
#[IsReadOnly]
class BillingRecap extends TaktTool
{
    use BuildsProjectBillingEntry;

    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'client_id' => ['required', 'string'],
            'from' => ['required', 'date_format:Y-m'],
            'to' => ['required', 'date_format:Y-m', 'after_or_equal:from'],
            'project_ids' => ['nullable', 'array'],
            'project_ids.*' => ['string'],
        ]);

        $client = $this->findClient($request, $data['client_id']);

        $projects = $client->projects()
            ->when($data['project_ids'] ?? null, fn ($query, $ids) => $query->whereIn('id', $ids))
            ->orderedByEndDateThenName()
            ->with(['timesheetEntries', 'invoices'])
            ->get();

        if ($projects->isEmpty() || $projects->count() !== count($data['project_ids'] ?? $projects)) {
            throw new AuthorizationException('Project(s) not found for this client.');
        }

        $expiresAt = now()->addMinutes(10);

        return Response::json([
            'client' => ['id' => $client->id, 'name' => $client->name],
            'from' => $data['from'],
            'to' => $data['to'],
            'projects' => $this->buildBillingPeriodEntries($client, $projects, $data['from'], $data['to'])->values(),
            'duplicate_billing_days' => Client::overbookedDays(
                $client->billableCoverageRows($data['from'].'-01', CarbonImmutable::parse($data['to'].'-01')->endOfMonth()->toDateString())
            ),
            'billing_page_url' => route('clients.billing.show', $client),
            'pdf_url' => URL::temporarySignedRoute('exports.billing.signed', $expiresAt, [
                'client' => $client->id,
                'project_ids' => $projects->pluck('id')->all(),
                'from' => $data['from'],
                'to' => $data['to'],
            ]),
            'pdf_url_expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'client_id' => $schema->string()->description('Client id.')->required(),
            'from' => $schema->string()->description('First month included, YYYY-MM.')->required(),
            'to' => $schema->string()->description('Last month included, YYYY-MM.')->required(),
            'project_ids' => $schema->array()->items($schema->string())->description('Limit to these projects of the client (all by default).'),
        ];
    }
}
