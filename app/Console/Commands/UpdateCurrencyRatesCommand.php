<?php

namespace App\Console\Commands;

use App\Contracts\CurrencyRateProvider;
use App\Contracts\CurrencyRateRepository;
use App\Models\Currency;
use Illuminate\Console\Command;

class UpdateCurrencyRatesCommand extends Command
{
    protected $signature = 'currency:update-rates
                            {--base= : Базовая валюта (по умолчанию из CURRENCY_BASE)}';

    protected $description = 'Обновить курсы валют из FreeCurrencyAPI (атомарно)';

    public function __construct(
        private readonly CurrencyRateProvider $provider,
        private readonly CurrencyRateRepository $repository,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $base = strtoupper((string) $this->option('base') ?: config('currency.base', 'USD'));

        $codes = Currency::availableCodes();
        if ($codes === []) {
            $this->warn('Нет доступных валют. Добавьте валюты в админ-панели.');
            return self::SUCCESS;
        }

        $this->info("Получение курсов для базы {$base}...");

        try {
            $rates = $this->provider->getLatestRates($base, $codes);
        } catch (\Throwable $e) {
            $this->error('Ошибка при получении курсов: ' . $e->getMessage());
            return self::FAILURE;
        }

        if ($rates === []) {
            $this->warn('Нет данных для сохранения.');
            return self::SUCCESS;
        }

        $this->repository->replaceRates($base, $rates);
        $this->info('Курсы обновлены (атомарно), записей: ' . count($rates));

        return self::SUCCESS;
    }
}
