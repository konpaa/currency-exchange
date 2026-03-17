<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\CurrencyRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CurrencyConversionApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCurrencies();
        $this->seedRates();
    }

    private function seedCurrencies(): void
    {
        foreach ([['USD', 'US Dollar'], ['EUR', 'Euro'], ['RUB', 'Ruble']] as $item) {
            Currency::query()->create(['code' => $item[0], 'name' => $item[1], 'is_active' => true]);
        }
    }

    private function seedRates(): void
    {
        $now = now();
        $gen = 1;
        CurrencyRate::query()->insert([
            ['base_currency' => 'USD', 'currency_code' => 'USD', 'rate' => '1', 'generation' => $gen, 'updated_at' => $now],
            ['base_currency' => 'USD', 'currency_code' => 'EUR', 'rate' => '0.92', 'generation' => $gen, 'updated_at' => $now],
            ['base_currency' => 'USD', 'currency_code' => 'RUB', 'rate' => '95.5', 'generation' => $gen, 'updated_at' => $now],
        ]);
    }

    #[Test]
    public function convert_returns_result(): void
    {
        $response = $this->getJson('/api/convert?amount=100&from=USD&to=EUR');
        $response->assertOk();
        $response->assertJsonPath('from', 'USD');
        $response->assertJsonPath('to', 'EUR');
        $response->assertJsonPath('amount', '100');
        $this->assertArrayHasKey('result', $response->json());
    }

    #[Test]
    public function convert_same_currency_returns_same_amount(): void
    {
        $response = $this->getJson('/api/convert?amount=50.5&from=USD&to=USD');
        $response->assertOk();
        $response->assertJsonPath('result', '50.50');
    }

    #[Test]
    public function convert_missing_params_returns_422(): void
    {
        $this->getJson('/api/convert?amount=100')->assertStatus(422);
        $this->getJson('/api/convert?from=USD&to=EUR')->assertStatus(422);
    }

    #[Test]
    public function convert_invalid_amount_returns_422(): void
    {
        $response = $this->getJson('/api/convert?amount=-1&from=USD&to=EUR');
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);
    }

    #[Test]
    public function convert_currency_not_in_list_returns_422(): void
    {
        $response = $this->getJson('/api/convert?amount=100&from=XXX&to=EUR');
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['from']);
    }

    #[Test]
    public function convert_unknown_rate_returns_422(): void
    {
        Currency::query()->create(['code' => 'XXX', 'name' => 'Unknown', 'is_active' => true]);
        $response = $this->getJson('/api/convert?amount=100&from=XXX&to=EUR');
        $response->assertStatus(422);
        $response->assertJsonFragment(['error' => __('currency.errors.rate_not_found', ['currency' => 'XXX'])]);
    }
}
