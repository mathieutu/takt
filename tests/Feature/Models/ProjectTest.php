<?php

use App\Models\Project;

describe('isArchived', function () {
    it('is not archived without an end date', function () {
        $project = Project::factory()->create(['end_date' => null]);

        expect($project->isArchived())->toBeFalse();
    });

    it('is not archived when the end date is in the future', function () {
        $project = Project::factory()->create(['end_date' => today()->addDay()]);

        expect($project->isArchived())->toBeFalse();
    });

    it('is archived from the end date onward, included', function () {
        $project = Project::factory()->create(['end_date' => today()]);

        expect($project->isArchived())->toBeTrue();
    });

    it('is archived when the end date is in the past', function () {
        $project = Project::factory()->create(['end_date' => today()->subDay()]);

        expect($project->isArchived())->toBeTrue();
    });
});

describe('scopeActive', function () {
    it('includes projects without an end date and with a future end date', function () {
        $active = Project::factory()->create(['end_date' => null]);
        $futureEnd = Project::factory()->create(['end_date' => today()->addDay()]);
        $archived = Project::factory()->archived()->create();

        $results = Project::active()->pluck('id');

        expect($results)->toContain($active->id, $futureEnd->id)
            ->not->toContain($archived->id);
    });
});

describe('scopeArchived', function () {
    it('includes only projects with a past or today end date', function () {
        $active = Project::factory()->create(['end_date' => null]);
        $archived = Project::factory()->archived()->create();

        $results = Project::archived()->pluck('id');

        expect($results)->toContain($archived->id)
            ->not->toContain($active->id);
    });
});
