<?php

namespace App\Providers;

use App\Contracts\CurrencyRateProvider;
use App\Contracts\CurrencyRateRepository as CurrencyRateRepositoryContract;
use App\Repositories\DatabaseCurrencyRateRepository;
use App\Services\Currency\FreeCurrencyApiProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CurrencyRateRepositoryContract::class, DatabaseCurrencyRateRepository::class);

        $this->app->bind(CurrencyRateProvider::class, function ($app) {
            $config = config('services.freecurrencyapi');
            return new FreeCurrencyApiProvider(
                $app->make(\Illuminate\Http\Client\Factory::class),
                (string) ($config['base_url'] ?? 'https://api.freecurrencyapi.com/v1'),
                (string) ($config['api_key'] ?? ''),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
