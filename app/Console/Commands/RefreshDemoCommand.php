<?php

namespace App\Console\Commands;

use App\Actions\CreateDemoData;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimesheetEntry;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RefreshDemoCommand extends Command
{
    protected $signature = 'app:demo-refresh';

    protected $description = 'Seed the demo account with realistic data';

    /** Shared pool of available days per month, consumed across all projects. */
    private array $monthSchedules = [];

    public function handle(CreateDemoData $createDemoData): void
    {
        DB::transaction(function () use ($createDemoData) {
            $this->removeExistingDemoData(CreateDemoData::DEMO_EMAIL);
            $this->removeExistingDemoData(CreateDemoData::ALICE_EMAIL);
            $createDemoData();
        });

        $this->info('Demo account recreated successfully.');
    }

    private function removeExistingDemoData(string $email): void
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            return;
        }

        $clientIds = $user->clients()->withTrashed()->pluck('id');
        $projectIds = Project::whereIn('client_id', $clientIds)->pluck('id');

        TimesheetEntry::whereIn('project_id', $projectIds)->delete();
        Invoice::whereIn('project_id', $projectIds)->delete();
        Project::whereIn('id', $projectIds)->delete();
        Client::whereIn('id', $clientIds)->withTrashed()->forceDelete();
        $user->delete();
    }
}
