<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourceAllocation extends Model
{
    protected $fillable = [
        'contract_id',
        'vendor_id',
        'source_id',
        'allocated_amount',
        'transaction_code',
        'status',
        'vendor_otp_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'allocated_amount' => 'decimal:2',
            'vendor_otp_verified_at' => 'datetime',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(User::class, 'source_id');
    }
}
