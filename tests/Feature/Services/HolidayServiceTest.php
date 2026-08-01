<?php

use App\Services\HolidayService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->service = new HolidayService;
});

describe('forYear', function () {
    it('fetches and caches the holidays for a given year', function () {
        Http::fake([
            'calendrier.api.gouv.fr/jours-feries/metropole/2026.json' => Http::response(['2026-01-01' => 'Jour de l\'an']),
        ]);

        expect($this->service->forYear(2026))->toBe(['2026-01-01' => 'Jour de l\'an']);

        Http::assertSentCount(1);
    });

    it('does not call the API again once the year is cached', function () {
        Http::fake([
            'calendrier.api.gouv.fr/*' => Http::response(['2026-01-01' => 'Jour de l\'an']),
        ]);

        $this->service->forYear(2026);
        $this->service->forYear(2026);

        Http::assertSentCount(1);
    });

    it('returns an empty array without throwing when the API call fails', function () {
        Http::fake([
            'calendrier.api.gouv.fr/*' => Http::response(null, 500),
        ]);

        expect($this->service->forYear(2026))->toBe([]);
    });
});

describe('forMonth', function () {
    it('only keeps the holidays whose key falls in the requested month', function () {
        Http::fake([
            'calendrier.api.gouv.fr/*' => Http::response([
                '2026-01-01' => 'Jour de l\'an',
                '2026-05-01' => 'Fête du travail',
                '2026-05-08' => 'Victoire 1945',
            ]),
        ]);

        expect($this->service->forMonth(CarbonImmutable::parse('2026-05-15')))->toBe([
            '2026-05-01' => 'Fête du travail',
            '2026-05-08' => 'Victoire 1945',
        ]);
    });
});

describe('forPeriod', function () {
    it('merges holidays across years spanned by the period and filters by date range', function () {
        Http::fake([
            'calendrier.api.gouv.fr/jours-feries/metropole/2025.json' => Http::response(['2025-12-25' => 'Noël']),
            'calendrier.api.gouv.fr/jours-feries/metropole/2026.json' => Http::response(['2026-01-01' => 'Jour de l\'an']),
        ]);

        $holidays = $this->service->forPeriod(
            CarbonImmutable::parse('2025-12-20'),
            CarbonImmutable::parse('2026-01-05'),
        );

        expect($holidays)->toBe([
            '2025-12-25' => 'Noël',
            '2026-01-01' => 'Jour de l\'an',
        ]);
    });
});
