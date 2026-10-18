<?php

namespace App\Actions;

use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SaveInvoice
{
    /**
     * Creates an invoice on `$project`, or updates `$invoice` when given (it may then be moved to
     * another project of the same client through `project_id`).
     *
     * @param  array<string, mixed>  $input
     */
    public function __invoke(Project $project, array $input, ?Invoice $invoice = null): Invoice
    {
        $data = Validator::make($input, [
            'amount' => ['required', 'integer', 'min:1'],
            'discount_amount' => ['required', 'integer', 'min:0', 'lte:amount'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'created_at' => ['required', 'date'],
            ...($invoice ? [
                'project_id' => ['sometimes', 'string', Rule::exists('projects', 'id')->where('client_id', $project->client_id)],
            ] : []),
        ])->validate();

        if (! $invoice) {
            return $project->invoices()->create($data);
        }

        $invoice->update($data);

        return $invoice;
    }
}
