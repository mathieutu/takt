<?php

namespace App\Http\Controllers;

use App\Actions\SaveInvoice;
use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectInvoiceController
{
    public function store(Request $request, Project $project, SaveInvoice $saveInvoice): RedirectResponse
    {
        $saveInvoice($project, $request->all());

        return redirect()->back();
    }

    public function update(Request $request, Invoice $invoice, SaveInvoice $saveInvoice): RedirectResponse
    {
        $saveInvoice($invoice->project, $request->all(), $invoice);

        return redirect()->back();
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $invoice->delete();

        return redirect()->back();
    }
}
