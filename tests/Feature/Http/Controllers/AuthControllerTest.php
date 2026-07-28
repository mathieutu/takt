<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

describe('redirect-after-login', function () {
    it('lands back on the shared page after the local disabled() bypass', function () {
        $this->withoutMiddleware(PreventRequestForgery::class);
        app()->instance('env', 'local');

        $owner = User::factory()->create();
        $client = Client::factory()->for($owner)->create(['share_token' => (string) Str::uuid()]);
        $user = User::factory()->create();

        $sharedUrl = route('shares.show', $client->share_token);

        $this->post(route('login.disabled'), ['user_id' => $user->id, 'redirect' => $sharedUrl])
            ->assertRedirect($sharedUrl);
    });

    it('lands back on the shared page after the GitHub OAuth callback', function () {
        $owner = User::factory()->create();
        $client = Client::factory()->for($owner)->create(['share_token' => (string) Str::uuid()]);
        $sharedUrl = route('shares.show', $client->share_token);

        $this->get(route('login.redirect', ['redirect' => $sharedUrl]));

        $githubUser = new SocialiteUser;
        $githubUser->map([
            'id' => 'gh-123',
            'name' => 'GitHub User',
            'nickname' => 'ghuser',
            'email' => 'ghuser@example.com',
            'avatar' => 'https://example.com/avatar.png',
        ]);

        Socialite::shouldReceive('driver->user')->andReturn($githubUser);

        $this->get(route('login.callback'))->assertRedirect($sharedUrl);
    });
});

describe('quick account switch (dev)', function () {
    it('switches the authenticated user via the disabled() bypass, even while already logged in', function () {
        $this->withoutMiddleware(PreventRequestForgery::class);
        app()->instance('env', 'local');

        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $this->actingAs($firstUser)
            ->post(route('login.disabled'), ['user_id' => $secondUser->id])
            ->assertRedirect(route('dashboard'));

        expect(auth()->id())->toBe($secondUser->id);
    });

    it('stays on the current page instead of the dashboard when a redirect is provided', function () {
        $this->withoutMiddleware(PreventRequestForgery::class);
        app()->instance('env', 'local');

        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $this->actingAs($firstUser)
            ->post(route('login.disabled'), ['user_id' => $secondUser->id, 'redirect' => route('timesheet')])
            ->assertRedirect(route('timesheet'));

        expect(auth()->id())->toBe($secondUser->id);
    });
});
