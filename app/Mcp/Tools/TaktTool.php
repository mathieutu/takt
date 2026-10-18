<?php

namespace App\Mcp\Tools;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Tool;

abstract class TaktTool extends Tool
{
    protected function user(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new AuthorizationException('Authentication required.');
        }

        return $user;
    }

    /**
     * Ownership is enforced by scoping every lookup to the authenticated user's own records (there is
     * no route binding to run EnsureUserOwnsResource on). A miss surfaces as an AuthorizationException
     * because the MCP server forwards its message to the agent, unlike a ModelNotFoundException.
     */
    protected function findClient(Request $request, string $id): Client
    {
        return $this->user($request)->clients()->withTrashed()->find($id)
            ?? throw new AuthorizationException('Client not found.');
    }

    protected function findProject(Request $request, string $id): Project
    {
        $user = $this->user($request);

        return Project::query()
            ->whereIn('client_id', $user->clients()->withTrashed()->select('id'))
            ->with(['client' => fn ($query) => $query->withTrashed()])
            ->find($id)
            ?? throw new AuthorizationException('Project not found.');
    }

    protected function findInvoice(Request $request, string $id): Invoice
    {
        $invoice = Invoice::query()->with('project')->find($id);

        $isOwned = $invoice && Project::query()
            ->whereKey($invoice->project_id)
            ->whereIn('client_id', $this->user($request)->clients()->withTrashed()->select('id'))
            ->exists();

        return $isOwned ? $invoice : throw new AuthorizationException('Invoice not found.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function projectData(Project $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'description' => $project->description,
            'client_id' => $project->client_id,
            'client_name' => $project->client?->name,
            'daily_rate' => $project->daily_rate,
            'max_month_budget' => $project->max_month_budget,
            'max_total_budget' => $project->max_total_budget,
            'start_date' => $project->start_date->toDateString(),
            'end_date' => $project->end_date?->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function invoiceData(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'project_id' => $invoice->project_id,
            'amount' => $invoice->amount,
            'discount_amount' => $invoice->discount_amount,
            'net_amount' => $invoice->netAmount(),
            'paid_at' => $invoice->paid_at?->toDateString(),
            'notes' => $invoice->notes,
            'created_at' => $invoice->created_at->toDateString(),
        ];
    }
}
