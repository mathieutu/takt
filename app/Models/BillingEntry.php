<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property Carbon $month
 * @property numeric $amount_billed
 * @property Carbon|null $payment_date
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
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
    protected $fillable = [
        'project_id',
        'month',
        'amount_billed',
        'payment_date',
        'notes',
    ];

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
