<?php

use App\Actions\CreateDemoData;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimesheetEntry;
use App\Models\User;

it('creates the demo user with clients, projects, entries and invoices, without erroring', function () {
    $demo = (new CreateDemoData)();

    expect($demo)->toBeInstanceOf(User::class)
        ->and($demo->email)->toBe(CreateDemoData::DEMO_EMAIL);

    expect(Client::query()->where('user_id', $demo->id)->count())->toBeGreaterThan(0)
        ->and(Project::query()->whereRelation('client', 'user_id', $demo->id)->count())->toBeGreaterThan(0)
        ->and(TimesheetEntry::query()->whereRelation('project.client', 'user_id', $demo->id)->count())->toBeGreaterThan(0)
        ->and(Invoice::query()->whereRelation('project.client', 'user_id', $demo->id)->count())->toBeGreaterThan(0);
});

it('also creates the Alice Recoque account sharing a project with the demo account', function () {
    (new CreateDemoData)();

    $alice = User::where('email', CreateDemoData::ALICE_EMAIL)->firstOrFail();

    expect($alice->shares()->exists())->toBeFalse();
    expect(Client::where('user_id', $alice->id)->firstOrFail()->shares()->exists())->toBeTrue();
});

it('archives the Leroux client with a deleted_at date in the past', function () {
    $demo = (new CreateDemoData)();

    $leroux = Client::withTrashed()->where('user_id', $demo->id)->where('name', 'Leroux & Fils Conseil')->firstOrFail();

    expect($leroux->deleted_at)->not->toBeNull();
});
