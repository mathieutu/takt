<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectInvoiceController
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $project->invoices()->create($request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'discount_amount' => ['required', 'integer', 'min:0', 'lte:amount'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'created_at' => ['required', 'date'],
        ]));

        return redirect()->back();
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $invoice->update($request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'discount_amount' => ['required', 'integer', 'min:0', 'lte:amount'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'created_at' => ['required', 'date'],
            'project_id' => ['sometimes', 'string', Rule::exists('projects', 'id')->where('client_id', $invoice->project->client_id)],
        ]));

        return redirect()->back();
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $invoice->delete();

        return redirect()->back();
    }
}
