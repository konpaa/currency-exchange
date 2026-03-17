<?php

namespace App\Repositories;

use App\Contracts\CurrencyRateRepository;
use App\Models\CurrencyRate;
use Illuminate\Support\Facades\DB;

class DatabaseCurrencyRateRepository implements CurrencyRateRepository
{
    public function getRate(string $baseCurrency, string $currencyCode): ?string
    {
        $baseCurrency = strtoupper($baseCurrency);
        $currencyCode = strtoupper($currencyCode);

        if ($baseCurrency === $currencyCode) {
            return '1';
        }

        $model = CurrencyRate::query()
            ->where('base_currency', $baseCurrency)
            ->where('currency_code', $currencyCode)
            ->where('generation', $this->currentGeneration($baseCurrency))
            ->first();

        return $model ? $this->normalizeRate((string) $model->rate) : null;
    }

    public function getRatesForCodes(string $baseCurrency, array $currencyCodes): array
    {
        $baseCurrency = strtoupper($baseCurrency);
        $currencyCodes = array_map('strtoupper', $currencyCodes);
        $gen = $this->currentGeneration($baseCurrency);

        return CurrencyRate::query()
            ->where('base_currency', $baseCurrency)
            ->whereIn('currency_code', $currencyCodes)
            ->where('generation', $gen)
            ->pluck('rate', 'currency_code')
            ->map(fn ($rate) => $this->normalizeRate((string) $rate))
            ->all();
    }

    public function getRatesByBase(string $baseCurrency): array
    {
        $baseCurrency = strtoupper($baseCurrency);
        $gen = $this->currentGeneration($baseCurrency);

        return CurrencyRate::query()
            ->where('base_currency', $baseCurrency)
            ->where('generation', $gen)
            ->pluck('rate', 'currency_code')
            ->map(fn ($rate) => $this->normalizeRate((string) $rate))
            ->all();
    }

    /**
     * Атомарное обновление без временного лага: сначала вставляем новое поколение,
     * затем удаляем старые. Читатели всегда видят данные (текущее поколение).
     */
    public function replaceRates(string $baseCurrency, array $rates): void
    {
        $baseCurrency = strtoupper($baseCurrency);

        DB::transaction(function () use ($baseCurrency, $rates): void {
            $newGen = $this->currentGeneration($baseCurrency) + 1;
            $now = now();
            $rows = [];
            foreach ($rates as $currencyCode => $rate) {
                $rows[] = [
                    'base_currency' => $baseCurrency,
                    'currency_code' => strtoupper((string) $currencyCode),
                    'rate' => (string) $rate,
                    'generation' => $newGen,
                    'updated_at' => $now,
                ];
            }

            if ($rows !== []) {
                CurrencyRate::query()->insert($rows);
            }

            CurrencyRate::query()
                ->where('base_currency', $baseCurrency)
                ->where('generation', '<', $newGen)
                ->delete();
        });
    }

    private function currentGeneration(string $baseCurrency): int
    {
        $max = CurrencyRate::query()
            ->where('base_currency', $baseCurrency)
            ->max('generation');

        return (int) ($max ?? 0);
    }

    private function normalizeRate(string $value): string
    {
        $value = rtrim(rtrim($value, '0'), '.');
        return $value === '' ? '0' : $value;
    }
}
