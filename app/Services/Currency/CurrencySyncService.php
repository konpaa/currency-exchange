<?php

namespace App\Services\Currency;

use App\Contracts\CurrencyRateProvider;
use App\Contracts\CurrencyRateRepository;
use App\Models\Currency;

readonly class CurrencySyncService
{
    public function __construct(
        private CurrencyRateProvider   $provider,
        private CurrencyRateRepository $repository,
    ) {
    }

    /**
     * Синхронизировать курсы для всех доступных валют (база из конфига).
     */
    public function syncAll(): void
    {
        $codes = Currency::availableCodes();
        if ($codes === []) {
            return;
        }
        $base = strtoupper((string) config('currency.base', 'USD'));
        $rates = $this->provider->getLatestRates($base, $codes);
        $this->repository->replaceRates($base, $rates);
    }

    /**
     * Синхронизировать курсы только для указанных кодов (объединить с текущими).
     */
    public function syncForCodes(array $codes): void
    {
        if ($codes === []) {
            return;
        }
        $codes = array_map(fn (string $c) => strtoupper($c), $codes);
        $base = strtoupper((string) config('currency.base', 'USD'));
        $current = $this->repository->getRatesByBase($base);
        $newRates = $this->provider->getLatestRates($base, $codes);
        $merged = array_merge($current, $newRates);
        $this->repository->replaceRates($base, $merged);
    }
}
