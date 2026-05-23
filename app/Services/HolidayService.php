<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class HolidayService
{
    public function forYear(int $year): array
    {
        return Cache::remember("holidays.{$year}", now()->addYear(), function () use ($year) {
            $response = Http::get("https://calendrier.api.gouv.fr/jours-feries/metropole/{$year}.json");

            return $response->ok() ? $response->json() : [];
        });
    }

    public function forMonth(CarbonInterface $date): array
    {
        $prefix = $date->format('Y-m-');

        return array_filter(
            $this->forYear($date->year),
            fn (string $key) => str_starts_with($key, $prefix),
            ARRAY_FILTER_USE_KEY,
        );
    }
}
