<?php

use App\Models\Client;

describe('shareUrl', function () {
    it('is null when the client has no share_token', function () {
        $client = new Client(['share_token' => null]);

        expect($client->shareUrl())->toBeNull();
    });

    it('builds the shares.show route when a share_token is set', function () {
        $client = new Client(['share_token' => 'a-token']);

        expect($client->shareUrl())->toBe(route('shares.show', 'a-token'));
    });
});
