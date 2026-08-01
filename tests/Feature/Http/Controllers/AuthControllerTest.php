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

describe('callback', function () {
    it('updates the github_id and avatar of an existing user matched by email', function () {
        $existingUser = User::factory()->create(['email' => 'ghuser@example.com', 'github_id' => null]);

        $githubUser = new SocialiteUser;
        $githubUser->map([
            'id' => 'gh-456',
            'name' => 'GitHub User',
            'nickname' => 'ghuser',
            'email' => 'ghuser@example.com',
            'avatar' => 'https://example.com/new-avatar.png',
        ]);

        Socialite::shouldReceive('driver->user')->andReturn($githubUser);

        $this->get(route('login.callback'))->assertRedirect(route('dashboard'));

        expect($existingUser->fresh())
            ->github_id->toBe('gh-456')
            ->avatar->toBe('https://example.com/new-avatar.png');

        expect(auth()->id())->toBe($existingUser->id);
    });
});

describe('demo', function () {
    it('creates the demo account and logs in when it does not exist yet', function () {
        $this->get(route('demo'))->assertRedirect(route('dashboard'));

        expect(auth()->user()->email)->toBe(config('auth.demo_email'));
    });

    it('logs into the existing demo account without recreating it', function () {
        $demoUser = User::factory()->create(['email' => config('auth.demo_email')]);

        $this->get(route('demo'))->assertRedirect(route('dashboard'));

        expect(auth()->id())->toBe($demoUser->id)
            ->and(User::where('email', config('auth.demo_email'))->count())->toBe(1);
    });
});

describe('logout', function () {
    it('logs the user out and redirects home', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('logout'))
            ->assertRedirect('/');

        expect(auth()->check())->toBeFalse();
    });
});

describe('quick account switch (dev)', function () {
    it('is forbidden outside the local environment', function () {
        $this->withoutMiddleware(PreventRequestForgery::class);

        $user = User::factory()->create();

        $this->post(route('login.disabled'), ['user_id' => $user->id])
            ->assertForbidden();
    });

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
