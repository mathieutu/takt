<?php

namespace App\Mcp\Tools;

use App\Actions\SaveInvoice;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Record an invoice issued on a project. Amounts are integers in euro cents, entered as is; the remaining amount to invoice is computed by Takt (see billing_recap).')]
class CreateInvoice extends TaktTool
{
    public function handle(Request $request, SaveInvoice $saveInvoice): Response
    {
        $project = $this->findProject($request, (string) $request->get('project_id'));

        return Response::json($this->invoiceData($saveInvoice($project, $request->all())));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->string()->description('Project id.')->required(),
            'amount' => $schema->integer()->min(1)->description('Gross invoiced amount (euro cents).')->required(),
            'discount_amount' => $schema->integer()->min(0)->description('Discount (0 if none), at most the amount (euro cents).')->required(),
            'created_at' => $schema->string()->description('Invoice date, YYYY-MM-DD.')->required(),
            'paid_at' => $schema->string()->description('Payment date, YYYY-MM-DD, empty if unpaid.'),
            'notes' => $schema->string()->description('Notes (1000 characters max).'),
        ];
    }
}
