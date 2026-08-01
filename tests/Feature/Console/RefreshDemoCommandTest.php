<?php

use App\Actions\CreateDemoData;
use App\Models\Client;
use App\Models\User;

it('creates the demo and Alice accounts on a fresh run', function () {
    $this->artisan('app:demo-refresh')->assertSuccessful();

    expect(User::where('email', CreateDemoData::DEMO_EMAIL)->exists())->toBeTrue()
        ->and(User::where('email', CreateDemoData::ALICE_EMAIL)->exists())->toBeTrue();
});

it('is idempotent: running it twice does not duplicate the demo or Alice accounts', function () {
    $this->artisan('app:demo-refresh')->assertSuccessful();
    $this->artisan('app:demo-refresh')->assertSuccessful();

    expect(User::where('email', CreateDemoData::DEMO_EMAIL)->count())->toBe(1)
        ->and(User::where('email', CreateDemoData::ALICE_EMAIL)->count())->toBe(1);
});

it('removes previously trashed demo clients before reseeding', function () {
    $this->artisan('app:demo-refresh')->assertSuccessful();

    $demo = User::where('email', CreateDemoData::DEMO_EMAIL)->firstOrFail();
    $trashedClientId = Client::where('user_id', $demo->id)->onlyTrashed()->value('id');

    $this->artisan('app:demo-refresh')->assertSuccessful();

    expect(Client::withTrashed()->find($trashedClientId))->toBeNull();
});
