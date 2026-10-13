<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceCharge extends Model
{
    protected $fillable = [
        'name',
        'type',
        'value',
        'min_amount',
        'max_amount',
        'applies_to',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'active' => 'boolean',
        ];
    }
}
