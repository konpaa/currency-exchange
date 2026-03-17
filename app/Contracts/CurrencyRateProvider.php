<?php

namespace App\Contracts;

/**
 * Получение актуальных курсов валют из внешнего источника.
 */
interface CurrencyRateProvider
{
    /**
     * Возвращает последние курсы относительно базовой валюты.
     *
     * @param  string  $baseCurrency  Код базовой валюты (например, USD).
     * @param  array<string>|null  $currencies  Список кодов валют или null для всех.
     * @return array<string, string>  Пары [код валюты => курс в виде строки для точной арифметики].
     */
    public function getLatestRates(string $baseCurrency, ?array $currencies = null): array;
}
