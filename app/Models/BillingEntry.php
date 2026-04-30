<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
            'month'         => 'date',
            'payment_date'  => 'date',
            'amount_billed' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
