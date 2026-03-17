<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyRate extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'base_currency',
        'currency_code',
        'rate',
        'generation',
    ];

    protected $casts = [
        'rate' => 'decimal:18',
    ];
}
