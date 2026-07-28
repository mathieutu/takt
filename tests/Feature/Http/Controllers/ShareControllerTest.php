<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\SavedShare;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->client = Client::factory()->for($this->owner)->create(['share_token' => (string) Str::uuid()]);
    Project::factory()->for($this->client)->create();

    $this->user = User::factory()->create();
});

describe('store', function () {
    it('saves a share for a valid token', function () {
        $this->actingAs($this->user)
            ->post(route('shares.store'), ['token' => $this->client->share_token])
            ->assertRedirect();

        expect(SavedShare::query()->where('user_id', $this->user->id)->where('client_id', $this->client->id)->first())
            ->token->toBe($this->client->share_token);
    });

    it('returns a 404 for an invalid token', function () {
        $this->actingAs($this->user)
            ->post(route('shares.store'), ['token' => (string) Str::uuid()])
            ->assertNotFound();
    });

    it('rejects saving a client the current user already owns', function () {
        $this->actingAs($this->owner)
            ->post(route('shares.store'), ['token' => $this->client->share_token])
            ->assertForbidden();

        expect(SavedShare::query()->where('client_id', $this->client->id)->exists())->toBeFalse();
    });

    it('refreshes the stored token when saving again after the owner regenerated it', function () {
        SavedShare::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'token' => (string) Str::uuid(),
        ]);

        $this->actingAs($this->user)
            ->post(route('shares.store'), ['token' => $this->client->share_token])
            ->assertRedirect();

        expect(SavedShare::query()->where('user_id', $this->user->id)->where('client_id', $this->client->id)->get())
            ->toHaveCount(1)
            ->first()->token->toBe($this->client->share_token);
    });
});

describe('destroy', function () {
    it('removes the share for its owner', function () {
        $share = SavedShare::factory()->create(['user_id' => $this->user->id, 'client_id' => $this->client->id]);

        $this->actingAs($this->user)
            ->delete(route('shares.destroy', $share))
            ->assertRedirect();

        expect(SavedShare::find($share->id))->toBeNull();
    });

    it('denies access to another user\'s share', function () {
        $share = SavedShare::factory()->create(['user_id' => $this->user->id, 'client_id' => $this->client->id]);
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->delete(route('shares.destroy', $share))
            ->assertForbidden();

        expect(SavedShare::find($share->id))->not->toBeNull();
    });
});

describe('SavedShare::isValid()', function () {
    it('is false after the client share_token has been revoked', function () {
        $share = SavedShare::factory()->create(['client_id' => $this->client->id, 'token' => $this->client->share_token]);

        $this->client->update(['share_token' => null]);

        expect($share->fresh()->isValid())->toBeFalse();
    });

    it('is false after the client share_token has been regenerated', function () {
        $share = SavedShare::factory()->create(['client_id' => $this->client->id, 'token' => $this->client->share_token]);

        $this->client->update(['share_token' => (string) Str::uuid()]);

        expect($share->fresh()->isValid())->toBeFalse();
    });

    it('is false, without throwing, after the client has been soft-deleted', function () {
        $share = SavedShare::factory()->create(['client_id' => $this->client->id, 'token' => $this->client->share_token]);

        $this->client->delete();

        expect($share->fresh()->isValid())->toBeFalse();
    });

    it('is true while the stored token still matches the client share_token', function () {
        $share = SavedShare::factory()->create(['client_id' => $this->client->id, 'token' => $this->client->share_token]);

        expect($share->isValid())->toBeTrue();
    });
});

describe('show', function () {
    it('silently resyncs an existing SavedShare token on every authenticated visit', function () {
        $share = SavedShare::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'token' => (string) Str::uuid(),
        ]);

        $this->actingAs($this->user)->get(route('shares.show', $this->client->share_token))->assertOk();

        expect($share->fresh()->token)->toBe($this->client->share_token);
    });

    it('never creates a SavedShare on mere viewing, only store() does', function () {
        $this->actingAs($this->user)->get(route('shares.show', $this->client->share_token))->assertOk();

        expect(SavedShare::query()->where('user_id', $this->user->id)->where('client_id', $this->client->id)->exists())->toBeFalse();
    });

    it('redirects the owner viewing their own share to their billing page', function () {
        $this->actingAs($this->owner)
            ->get(route('shares.show', $this->client->share_token))
            ->assertRedirect(route('clients.billing.show', $this->client));
    });
});

describe('index', function () {
    it('my_shares only includes clients with a non-null share_token, scoped to the authenticated user', function () {
        $otherOwner = User::factory()->create();
        $otherClient = Client::factory()->for($otherOwner)->create(['share_token' => (string) Str::uuid()]);
        $unsharedClient = Client::factory()->for($this->owner)->create(['share_token' => null]);

        $response = $this->actingAs($this->owner)->get(route('shares.index'));

        $response->assertOk();
        $response->assertInertia(function ($page) use ($otherClient, $unsharedClient) {
            $ids = collect($page->toArray()['props']['my_shares'])->pluck('id');

            expect($ids)->toContain($this->client->id)
                ->not->toContain($otherClient->id)
                ->not->toContain($unsharedClient->id);
        });
    });

    it('my_shares followers include both valid and invalid entries for the same client, each correctly flagged', function () {
        $validFollower = SavedShare::factory()->create(['client_id' => $this->client->id, 'token' => $this->client->share_token]);
        $invalidFollower = SavedShare::factory()->create(['client_id' => $this->client->id, 'token' => (string) Str::uuid()]);

        $response = $this->actingAs($this->owner)->get(route('shares.index'));

        $response->assertInertia(function ($page) use ($validFollower, $invalidFollower) {
            $client = collect($page->toArray()['props']['my_shares'])->firstWhere('id', $this->client->id);
            $followers = collect($client['followers'])->keyBy('id');

            expect($followers[$validFollower->id]['is_valid'])->toBeTrue();
            expect($followers[$invalidFollower->id]['is_valid'])->toBeFalse();
        });
    });

    it('keeps a client in my_shares once its share_token is revoked, as long as SavedShare rows remain, flagged as revoked', function () {
        $follower = SavedShare::factory()->create(['client_id' => $this->client->id, 'token' => $this->client->share_token]);

        $this->client->update(['share_token' => null]);

        $response = $this->actingAs($this->owner)->get(route('shares.index'));

        $response->assertInertia(function ($page) use ($follower) {
            $client = collect($page->toArray()['props']['my_shares'])->firstWhere('id', $this->client->id);

            expect($client)->not->toBeNull();
            expect($client['is_shared'])->toBeFalse();
            expect($client['share_url'])->toBeNull();

            $followerData = collect($client['followers'])->firstWhere('id', $follower->id);
            expect($followerData['is_valid'])->toBeFalse();
        });
    });

    it('drops a client from my_shares entirely once its share_token is null and it has no SavedShare at all', function () {
        $neverShared = Client::factory()->for($this->owner)->create(['share_token' => null]);

        $response = $this->actingAs($this->owner)->get(route('shares.index'));

        $response->assertInertia(function ($page) use ($neverShared) {
            $ids = collect($page->toArray()['props']['my_shares'])->pluck('id');

            expect($ids)->not->toContain($neverShared->id);
        });
    });
});
