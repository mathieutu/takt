<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\BillingRecap;
use App\Mcp\Tools\CreateClient;
use App\Mcp\Tools\CreateInvoice;
use App\Mcp\Tools\CreateProject;
use App\Mcp\Tools\GetProject;
use App\Mcp\Tools\ListClients;
use App\Mcp\Tools\ListEntries;
use App\Mcp\Tools\ListInvoices;
use App\Mcp\Tools\ListProjects;
use App\Mcp\Tools\UpdateClient;
use App\Mcp\Tools\UpdateInvoice;
use App\Mcp\Tools\UpdateProject;
use App\Mcp\Tools\UpsertEntries;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Takt')]
#[Version('1.0.0')]
#[Instructions('Takt tracks the time spent per project to bill clients. A client has projects; a project has timesheet entries (one per day, coverage = percentage of a day) and invoices. Start with list_clients / list_projects to get the ids. An entry is only accepted within the project period (start_date, end_date): create the project with an earlier start_date to record the past. To import many days, use upsert_entries with dry_run first. All amounts (rates, budgets, invoices, recap) are integers in euro cents: 50000 = €500. No deletion is exposed; an empty entry only deletes its own day. Reply to the user in their own language.')]
class TaktServer extends Server
{
    protected array $tools = [
        ListClients::class,
        CreateClient::class,
        UpdateClient::class,
        ListProjects::class,
        GetProject::class,
        CreateProject::class,
        UpdateProject::class,
        ListEntries::class,
        UpsertEntries::class,
        ListInvoices::class,
        CreateInvoice::class,
        UpdateInvoice::class,
        BillingRecap::class,
    ];
}
