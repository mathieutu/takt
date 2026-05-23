<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $project_id
 * @property CarbonImmutable $month
 * @property numeric $amount_billed
 * @property CarbonImmutable|null $payment_date
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Project $project
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingEntry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingEntry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingEntry query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingEntry whereAmountBilled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingEntry whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingEntry whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingEntry whereMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingEntry whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingEntry wherePaymentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingEntry whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingEntry whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class BillingEntry extends Model
{
    protected function casts(): array
    {
        return [
            'month' => 'date',
            'payment_date' => 'date',
            'amount_billed' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
