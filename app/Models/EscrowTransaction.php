<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EscrowTransaction extends Model
{
    protected $fillable = [
        'contract_id',
        'amount',
        'status',
        'locked_at',
        'released_at',
        'release_eligible_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'locked_at' => 'datetime',
            'released_at' => 'datetime',
            'release_eligible_at' => 'datetime',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function sourceAllocations(): HasMany
    {
        return $this->hasMany(SourceAllocation::class, 'contract_id', 'contract_id');
    }
}
