<?php

namespace App\Contracts;

/**
 * Хранилище курсов валют (персистентность, атомарное обновление).
 */
interface CurrencyRateRepository
{
    /**
     * Получить курс для пары базовая валюта -> валюта.
     */
    public function getRate(string $baseCurrency, string $currencyCode): ?string;

    /**
     * Получить курсы для нескольких валют за один запрос.
     *
     * @param  array<string>  $currencyCodes
     * @return array<string, string>  [код валюты => курс] (только найденные)
     */
    public function getRatesForCodes(string $baseCurrency, array $currencyCodes): array;

    /**
     * Получить все курсы для базовой валюты.
     *
     * @return array<string, string>  [код валюты => курс]
     */
    public function getRatesByBase(string $baseCurrency): array;

    /**
     * Атомарно заменить все курсы для базовой валюты.
     * Старые данные не должны исчезать до появления новых (никаких «пустых» периодов).
     *
     * @param  string  $baseCurrency
     * @param  array<string, string>  $rates  [код валюты => курс]
     */
    public function replaceRates(string $baseCurrency, array $rates): void;
}
