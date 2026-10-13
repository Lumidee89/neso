<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Contract extends Model
{
    protected $fillable = [
        'payer_id',
        'vendor_id',
        'title',
        'description',
        'amount',
        'delivery_date',
        'terms',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'delivery_date' => 'date',
        ];
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(ContractStatusLog::class);
    }

    public function escrowTransaction(): HasOne
    {
        return $this->hasOne(EscrowTransaction::class);
    }

    public function sourceAllocations(): HasMany
    {
        return $this->hasMany(SourceAllocation::class);
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class);
    }
}
