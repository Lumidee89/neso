<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwoFactor extends Model
{
    protected $table = 'two_factor';

    protected $fillable = [
        'user_id',
        'secret',
        'recovery_codes',
        'enabled',
        'confirmed_at',
    ];

    protected $hidden = [
        'secret',
        'recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'recovery_codes' => 'encrypted:array',
            'enabled' => 'boolean',
            'confirmed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
