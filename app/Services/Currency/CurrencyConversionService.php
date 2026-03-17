<?php

namespace App\Services\Currency;

use App\Contracts\CurrencyRateRepository;
use InvalidArgumentException;

/**
 * Конвертация валют с точной арифметикой (bcmath), без ошибок плавающей точки.
 */
class CurrencyConversionService
{
    private const SCALE = 18;

    public function __construct(
        private readonly CurrencyRateRepository $repository,
    ) {
    }

    /**
     * Конвертировать сумму из одной валюты в другую.
     *
     * @param  int|float|string  $amount  Сумма (например, 123 или "100.50").
     * @param  string  $fromCurrency  Исходная валюта.
     * @param  string  $toCurrency  Целевая валюта.
     * @return string  Результат в виде строки.
     */
    public function convert(int|float|string $amount, string $fromCurrency, string $toCurrency): string
    {
        $amount = (string) $amount;
        $fromCurrency = strtoupper($fromCurrency);
        $toCurrency = strtoupper($toCurrency);

        if ($fromCurrency === $toCurrency) {
            return $this->normalizeAmount($amount);
        }

        $this->validateAmount($amount);

        $baseCurrency = strtoupper((string) config('currency.base', 'USD'));

        $rates = $this->repository->getRatesForCodes($baseCurrency, [$fromCurrency, $toCurrency]);

        $rateFrom = $fromCurrency === $baseCurrency ? '1' : ($rates[$fromCurrency] ?? null);
        $rateTo = $toCurrency === $baseCurrency ? '1' : ($rates[$toCurrency] ?? null);

        if ($rateFrom === null) {
            throw new InvalidArgumentException(__('currency.errors.rate_not_found', ['currency' => $fromCurrency]));
        }
        if ($rateTo === null) {
            throw new InvalidArgumentException(__('currency.errors.rate_not_found', ['currency' => $toCurrency]));
        }

        $amountInBase = bcdiv($amount, $rateFrom, self::SCALE);
        $result = bcmul($amountInBase, $rateTo, self::SCALE);

        return $this->normalizeAmount($result);
    }

    private function validateAmount(string $amount): void
    {
        if (! is_numeric($amount) || bccomp($amount, '0', self::SCALE) < 0) {
            throw new InvalidArgumentException(__('currency.errors.amount_invalid'));
        }
    }

    private function normalizeAmount(string $value): string
    {
        $value = rtrim(rtrim($value, '0'), '.');
        return $value === '' ? '0' : $value;
    }
}
