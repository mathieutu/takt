<?php

use App\Models\TimesheetEntry;
use Illuminate\Support\Collection;

describe('billableCoverageSum', function () {
    it('sums only the billable entries coverage', function () {
        $entries = new Collection([
            new TimesheetEntry(['coverage' => 50, 'billable' => true]),
            new TimesheetEntry(['coverage' => 30, 'billable' => false]),
            new TimesheetEntry(['coverage' => 20, 'billable' => true]),
        ]);

        expect(TimesheetEntry::billableCoverageSum($entries))->toBe(70);
    });

    it('is zero for an empty collection', function () {
        expect(TimesheetEntry::billableCoverageSum(new Collection))->toBe(0);
    });
});
