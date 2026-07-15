<?php

use App\Models\Project;

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
