<?php

namespace Tests\Unit;

use App\Models\CurrencyRate;
use App\Repositories\DatabaseCurrencyRateRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CurrencyRateRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseCurrencyRateRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new DatabaseCurrencyRateRepository();
    }

    #[Test]
    public function get_rate_returns_null_when_missing(): void
    {
        $this->assertNull($this->repository->getRate('USD', 'EUR'));
    }

    #[Test]
    public function get_rate_returns_one_for_same_currency(): void
    {
        $this->assertSame('1', $this->repository->getRate('USD', 'USD'));
    }

    #[Test]
    public function replace_rates_stores_and_retrieves(): void
    {
        $this->repository->replaceRates('USD', [
            'EUR' => '0.92',
            'RUB' => '95.5',
        ]);

        $this->assertSame('0.92', $this->repository->getRate('USD', 'EUR'));
        $this->assertSame('95.5', $this->repository->getRate('USD', 'RUB'));
    }

    #[Test]
    public function replace_rates_is_atomic_old_replaced_by_new(): void
    {
        $this->repository->replaceRates('USD', [
            'EUR' => '0.9',
        ]);
        $this->assertSame('0.9', $this->repository->getRate('USD', 'EUR'));

        $this->repository->replaceRates('USD', [
            'EUR' => '0.95',
            'RUB' => '100',
        ]);
        $this->assertSame('0.95', $this->repository->getRate('USD', 'EUR'));
        $this->assertSame('100', $this->repository->getRate('USD', 'RUB'));
    }

    #[Test]
    public function get_rates_by_base_returns_all(): void
    {
        $now = now();
        $gen = 1;
        CurrencyRate::query()->insert([
            ['base_currency' => 'USD', 'currency_code' => 'EUR', 'rate' => '0.92', 'generation' => $gen, 'updated_at' => $now],
            ['base_currency' => 'USD', 'currency_code' => 'RUB', 'rate' => '95.5', 'generation' => $gen, 'updated_at' => $now],
        ]);

        $rates = $this->repository->getRatesByBase('USD');
        $this->assertSame(['EUR' => '0.92', 'RUB' => '95.5'], $rates);
    }
}
