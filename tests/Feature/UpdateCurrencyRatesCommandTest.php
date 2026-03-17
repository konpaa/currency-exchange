<?php

namespace Tests\Feature;

use App\Contracts\CurrencyRateProvider;
use App\Models\Currency;
use App\Models\CurrencyRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateCurrencyRatesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Currency::query()->insert([
            ['code' => 'USD', 'name' => 'US Dollar', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'EUR', 'name' => 'Euro', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'RUB', 'name' => 'Ruble', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    #[Test]
    public function command_updates_rates_atomically(): void
    {
        $this->mock(CurrencyRateProvider::class, function ($mock): void {
            $mock->shouldReceive('getLatestRates')
                ->once()
                ->andReturn([
                    'USD' => '1',
                    'EUR' => '0.92',
                    'RUB' => '95.5',
                ]);
        });

        $this->artisan('currency:update-rates', ['--base' => 'USD'])
            ->assertSuccessful();

        $this->assertDatabaseHas('currency_rates', [
            'base_currency' => 'USD',
            'currency_code' => 'EUR',
            'rate' => '0.92',
        ]);
        $this->assertDatabaseHas('currency_rates', [
            'base_currency' => 'USD',
            'currency_code' => 'RUB',
            'rate' => '95.5',
        ]);
    }

    #[Test]
    public function command_replaces_old_rates(): void
    {
        $now = now();
        CurrencyRate::query()->insert([
            ['base_currency' => 'USD', 'currency_code' => 'EUR', 'rate' => '0.9', 'generation' => 1, 'updated_at' => $now],
        ]);

        $this->mock(CurrencyRateProvider::class, function ($mock): void {
            $mock->shouldReceive('getLatestRates')
                ->once()
                ->andReturn(['EUR' => '0.95']);
        });

        $this->artisan('currency:update-rates')->assertSuccessful();

        $this->assertDatabaseCount('currency_rates', 1);
        $this->assertDatabaseHas('currency_rates', ['currency_code' => 'EUR', 'rate' => '0.95']);
    }

    #[Test]
    public function command_fails_gracefully_on_provider_error(): void
    {
        $this->mock(CurrencyRateProvider::class, function ($mock): void {
            $mock->shouldReceive('getLatestRates')
                ->once()
                ->andThrow(new \RuntimeException('API error'));
        });

        $this->artisan('currency:update-rates')->assertFailed();
        $this->assertDatabaseCount('currency_rates', 0);
    }
}
