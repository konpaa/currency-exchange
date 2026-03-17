<?php

namespace App\Console\Commands;

use App\Contracts\CurrencyRateProvider;
use App\Models\Currency;
use Illuminate\Console\Command;

class ImportCurrenciesCommand extends Command
{
    protected $signature = 'currency:import
                            {--activate : Активировать все импортированные валюты}';

    protected $description = 'Импортировать список доступных валют из FreeCurrencyAPI';

    public function __construct(
        private readonly CurrencyRateProvider $provider,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Получение списка валют из API...');

        try {
            $currencies = $this->provider->getAvailableCurrencies();
        } catch (\Throwable $e) {
            $this->error('Ошибка: ' . $e->getMessage());
            return self::FAILURE;
        }

        $activate = $this->option('activate');
        $created = 0;
        $updated = 0;

        foreach ($currencies as $item) {
            $currency = Currency::query()->where('code', $item['code'])->first();

            if ($currency === null) {
                Currency::query()->create([
                    'code' => $item['code'],
                    'name' => $item['name'],
                    'is_active' => $activate,
                ]);
                $created++;
            } else {
                $currency->update(['name' => $item['name']]);
                $updated++;
            }
        }

        $apiCodes = array_keys($currencies);
        $deactivated = Currency::query()
            ->where('is_active', true)
            ->whereNotIn('code', $apiCodes)
            ->update(['is_active' => false]);

        if ($deactivated > 0) {
            Currency::flushAvailableCodesCache();
        }

        $this->info("Готово. Создано: {$created}, обновлено: {$updated}, деактивировано (нет в API): {$deactivated}, всего в API: " . count($currencies));

        if ($created > 0 && ! $activate) {
            $this->comment('Новые валюты добавлены как неактивные. Включите нужные в админ-панели или повторите с --activate.');
        }

        return self::SUCCESS;
    }
}
