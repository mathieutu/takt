<?php

namespace App\Mcp\Tools;

use App\Models\Invoice;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the invoices of a project (gross amount, discount, net amount, payment date).')]
#[IsReadOnly]
class ListInvoices extends TaktTool
{
    public function handle(Request $request): Response
    {
        $invoices = $this->findProject($request, (string) $request->get('project_id'))
            ->invoices()->orderBy('created_at')->get();

        return Response::json($invoices->map(fn (Invoice $invoice) => $this->invoiceData($invoice))->all());
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->string()->description('Project id.')->required(),
        ];
    }
}
