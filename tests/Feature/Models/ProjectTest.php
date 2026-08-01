<?php

use App\Models\Project;
use Carbon\CarbonImmutable;

describe('isInactive', function () {
    it('is not inactive without an end date', function () {
        $project = Project::factory()->create(['end_date' => null]);

        expect($project->isInactive())->toBeFalse();
    });

    it('is not inactive when the end date is in the future', function () {
        $project = Project::factory()->create(['end_date' => today()->addDay()]);

        expect($project->isInactive())->toBeFalse();
    });

    it('is not inactive on the end date itself', function () {
        $project = Project::factory()->create(['end_date' => today()]);

        expect($project->isInactive())->toBeFalse();
    });

    it('is inactive starting the day after the end date', function () {
        $project = Project::factory()->create(['end_date' => today()->subDay()]);

        expect($project->isInactive())->toBeTrue();
    });

    it('is inactive when the start date is in the future', function () {
        $project = Project::factory()->create(['start_date' => today()->addDay(), 'end_date' => null]);

        expect($project->isInactive())->toBeTrue();
    });
});

describe('scopeActive', function () {
    it('includes projects without an end date, with a future end date, or ending today', function () {
        $active = Project::factory()->create(['end_date' => null]);
        $futureEnd = Project::factory()->create(['end_date' => today()->addDay()]);
        $endingToday = Project::factory()->create(['end_date' => today()]);
        $inactive = Project::factory()->inactive()->create();

        $results = Project::active()->pluck('id');

        expect($results)->toContain($active->id, $futureEnd->id, $endingToday->id)
            ->not->toContain($inactive->id);
    });

    it('excludes projects whose start date is in the future', function () {
        $notYetStarted = Project::factory()->create(['start_date' => today()->addDay(), 'end_date' => null]);

        expect(Project::active()->pluck('id'))->not->toContain($notYetStarted->id);
    });
});

describe('scopeInactive', function () {
    it('includes only projects ended before today', function () {
        $active = Project::factory()->create(['end_date' => null]);
        $endingToday = Project::factory()->create(['end_date' => today()]);
        $inactive = Project::factory()->inactive()->create();

        $results = Project::inactive()->pluck('id');

        expect($results)->toContain($inactive->id)
            ->not->toContain($active->id, $endingToday->id);
    });

    it('includes projects whose start date is in the future', function () {
        $notYetStarted = Project::factory()->create(['start_date' => today()->addDay(), 'end_date' => null]);

        expect(Project::inactive()->pluck('id'))->toContain($notYetStarted->id);
    });
});

describe('isActiveOn', function () {
    it('is not active before the start date', function () {
        $project = Project::factory()->create(['start_date' => today(), 'end_date' => null]);

        expect($project->isActiveOn(today()->subDay()))->toBeFalse();
    });

    it('is active on the start date', function () {
        $project = Project::factory()->create(['start_date' => today(), 'end_date' => null]);

        expect($project->isActiveOn(today()))->toBeTrue();
    });

    it('is active without an end date, however far in the future', function () {
        $project = Project::factory()->create(['start_date' => today(), 'end_date' => null]);

        expect($project->isActiveOn(today()->addYears(5)))->toBeTrue();
    });

    it('is active on the end date, even for an already inactive project', function () {
        $project = Project::factory()->create([
            'start_date' => today()->subMonths(2),
            'end_date' => today()->subDay(),
        ]);

        expect($project->isInactive())->toBeTrue()
            ->and($project->isActiveOn($project->end_date))->toBeTrue();
    });

    it('is not active after the end date', function () {
        $project = Project::factory()->create([
            'start_date' => today()->subMonths(2),
            'end_date' => today()->subDay(),
        ]);

        expect($project->isActiveOn(today()))->toBeFalse();
    });
});

describe('getDailyRateForDate', function () {
    it('falls back to 0 when no rate was ever set', function () {
        $project = Project::factory()->create();
        $project->update(['daily_rates' => []]);

        expect($project->getDailyRateForDate(today()))->toBe(0);
    });

    it('returns the only known rate regardless of date, when there is a single entry', function () {
        $project = Project::factory()->create();
        $project->update(['daily_rates' => ['2026-01-01' => 50000]]);

        expect($project->getDailyRateForDate('2020-01-01'))->toBe(50000)
            ->and($project->getDailyRateForDate('2030-01-01'))->toBe(50000);
    });

    it('picks the latest rate effective on or before the given date', function () {
        $project = Project::factory()->create();
        $project->update(['daily_rates' => [
            '2026-01-01' => 50000,
            '2026-03-01' => 60000,
        ]]);

        expect($project->getDailyRateForDate('2026-02-15'))->toBe(50000)
            ->and($project->getDailyRateForDate('2026-03-01'))->toBe(60000)
            ->and($project->getDailyRateForDate('2026-12-31'))->toBe(60000);
    });

    it('falls back to the earliest known rate for a date before any history', function () {
        $project = Project::factory()->create();
        $project->update(['daily_rates' => ['2026-03-01' => 60000]]);

        expect($project->getDailyRateForDate('2020-01-01'))->toBe(60000);
    });
});

describe('getMonthlyBudgetForDate', function () {
    it('returns null when no budget was ever set', function () {
        $project = Project::factory()->create();
        $project->update(['monthly_budgets' => []]);

        expect($project->getMonthlyBudgetForDate(today()))->toBeNull();
    });

    it('returns null before a budget was introduced, then the budget once effective', function () {
        $project = Project::factory()->create();
        $project->update(['monthly_budgets' => ['2026-03-01' => 495000]]);

        expect($project->getMonthlyBudgetForDate('2026-01-01'))->toBeNull()
            ->and($project->getMonthlyBudgetForDate('2026-03-01'))->toBe(495000)
            ->and($project->getMonthlyBudgetForDate('2026-06-01'))->toBe(495000);
    });
});

describe('theoreticalBudgetThrough', function () {
    it('returns the flat max_total_budget when set, ignoring monthly_budgets', function () {
        $project = Project::factory()->create();
        $project->update([
            'max_total_budget' => 1000000,
            'monthly_budgets' => ['2026-01-01' => 495000],
        ]);

        expect($project->theoreticalBudgetThrough(CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2026-06-01')))
            ->toBe(1000000);
    });

    it('returns null when no monthly budget was ever set', function () {
        $project = Project::factory()->create();
        $project->update(['max_total_budget' => null, 'monthly_budgets' => []]);

        expect($project->theoreticalBudgetThrough(CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2026-06-01')))
            ->toBeNull();
    });

    it('only accrues the budget from the month it was introduced', function () {
        $project = Project::factory()->create();
        $project->update([
            'max_total_budget' => null,
            'monthly_budgets' => ['2026-03-01' => 495000],
        ]);

        // January and February had no budget yet, so they contribute nothing.
        expect($project->theoreticalBudgetThrough(CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2026-04-01')))
            ->toBe(495000 * 2);
    });
});

describe('setDailyRateFrom/setMonthlyBudgetFrom', function () {
    it('drops a later entry that becomes redundant once a matching value is inserted before it', function () {
        $project = Project::factory()->create();
        $project->update(['daily_rates' => [
            '2026-01-01' => 50000,
            '2026-06-01' => 60000,
        ]]);

        // Backdating a 60000 rate to March makes the June entry (same value) redundant.
        $project->setDailyRateFrom(60000, '2026-03-01');

        expect($project->daily_rates->all())->toBe([
            '2026-01-01' => 50000,
            '2026-03-01' => 60000,
        ]);
    });

    it('keeps entries whose value differs from the one right before them', function () {
        $project = Project::factory()->create();
        $project->update(['daily_rates' => ['2026-01-01' => 50000]]);

        $project->setDailyRateFrom(60000, '2026-06-01');

        expect($project->daily_rates->all())->toBe([
            '2026-01-01' => 50000,
            '2026-06-01' => 60000,
        ]);
    });

    it('applies the same pruning to monthly_budgets', function () {
        $project = Project::factory()->create();
        $project->update(['monthly_budgets' => [
            '2026-01-01' => 495000,
            '2026-06-01' => 660000,
        ]]);

        $project->setMonthlyBudgetFrom(660000, '2026-03-01');

        expect($project->monthly_budgets->all())->toBe([
            '2026-01-01' => 495000,
            '2026-03-01' => 660000,
        ]);
    });

    it('normalizes a monthly budget of 0 to unlimited', function () {
        $project = Project::factory()->create();
        $project->update(['monthly_budgets' => ['2026-01-01' => 495000]]);

        $project->setMonthlyBudgetFrom(0, '2026-03-01');

        expect($project->getMonthlyBudgetForDate('2026-03-01'))->toBeNull()
            ->and($project->monthly_budgets->get('2026-03-01'))->toBeNull();
    });

    it('still records a rate backdated before all known history, even if it matches the earliest entry', function () {
        $project = Project::factory()->create();
        $project->update(['daily_rates' => ['2026-03-01' => 50000]]);

        // Before this call, getDailyRateForDate('2026-01-01') already falls back to 50000 (the
        // earliest known rate) — the new entry must still be recorded, not treated as a no-op. It then
        // makes the March entry redundant (same value, no longer marks an actual change), so pruning
        // collapses the history down to just the new, earlier entry.
        $project->setDailyRateFrom(50000, '2026-01-01');

        expect($project->daily_rates->all())->toBe(['2026-01-01' => 50000])
            ->and($project->getDailyRateForDate('2026-03-01'))->toBe(50000);
    });

    it('collapses monthly_budgets to null when the only remaining entry is unlimited', function () {
        $project = Project::factory()->create();
        $project->update(['monthly_budgets' => ['2026-01-01' => 495000]]);

        $project->setMonthlyBudgetFrom(0, '2026-01-01');

        expect($project->monthly_budgets)->toBeNull();
    });

    it('does not collapse monthly_budgets to null when other dated entries remain', function () {
        $project = Project::factory()->create();
        $project->update(['monthly_budgets' => [
            '2026-01-01' => 495000,
            '2026-03-01' => 660000,
        ]]);

        $project->setMonthlyBudgetFrom(0, '2026-01-01');

        expect($project->monthly_budgets->all())->toBe([
            '2026-01-01' => null,
            '2026-03-01' => 660000,
        ]);
    });
});

describe('dailyRate setter', function () {
    it('records a rate of 0 on a brand new project instead of leaving daily_rates empty', function () {
        // On a project with no history yet, the daily_rate getter falls back to 0 — setting a rate of
        // 0 must not be mistaken for "no change" and skip recording it, or daily_rates stays null and
        // violates the not-null constraint on save.
        $project = Project::factory()->create(['daily_rate' => 0]);

        expect($project->daily_rates->all())->toBe([today()->toDateString() => 0]);
    });
});
