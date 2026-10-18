<?php

namespace App\Mcp\Tools;

use App\Actions\SaveInvoice;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Description('Update an invoice (for example to mark it paid with paid_at). All the create_invoice fields are required: reuse the current values from list_invoices.')]
#[IsIdempotent]
class UpdateInvoice extends TaktTool
{
    public function handle(Request $request, SaveInvoice $saveInvoice): Response
    {
        $invoice = $this->findInvoice($request, (string) $request->get('invoice_id'));

        if ($targetId = $request->get('project_id')) {
            $this->findProject($request, (string) $targetId);
        }

        return Response::json($this->invoiceData($saveInvoice($invoice->project, $request->all(), $invoice)));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'invoice_id' => $schema->string()->description('Invoice id.')->required(),
            'project_id' => $schema->string()->description('Move the invoice to another project of the same client.'),
            'amount' => $schema->integer()->min(1)->description('Gross invoiced amount (euro cents).')->required(),
            'discount_amount' => $schema->integer()->min(0)->required(),
            'created_at' => $schema->string()->description('Invoice date, YYYY-MM-DD.')->required(),
            'paid_at' => $schema->string()->description('Payment date, YYYY-MM-DD, empty if unpaid.'),
            'notes' => $schema->string(),
        ];
    }
}
