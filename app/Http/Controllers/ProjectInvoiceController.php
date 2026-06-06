<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectInvoiceController
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $project->invoices()->create($request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]));

        return redirect()->back();
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $invoice->update($request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]));

        return redirect()->back();
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $invoice->delete();

        return redirect()->back();
    }
}
