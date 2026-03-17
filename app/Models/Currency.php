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
        return cache()->remember('currency:available_codes', 60 * 60, function () {
            return static::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->pluck('code')
                ->all();
        });
    }

    public static function flushAvailableCodesCache(): void
    {
        cache()->forget('currency:available_codes');
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::flushAvailableCodesCache());
        static::deleted(fn () => static::flushAvailableCodesCache());
    }
}
