<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getCodeAttribute(string $value): string
    {
        return strtoupper($value);
    }

    public function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = strtoupper($value);
    }

    public static function availableCodes(): array
    {
        return static::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->pluck('code')
            ->all();
    }
}
