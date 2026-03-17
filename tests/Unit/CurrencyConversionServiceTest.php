<?php

namespace Tests\Unit;

use App\Contracts\CurrencyRateRepository;
use App\Services\Currency\CurrencyConversionService;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CurrencyConversionServiceTest extends TestCase
{
    private CurrencyRateRepository $repository;

    private CurrencyConversionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(CurrencyRateRepository::class);
        $this->service = new CurrencyConversionService($this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function same_currency_returns_same_amount(): void
    {
        $result = $this->service->convert('100.50', 'USD', 'USD');
        $this->assertSame('100.5', $result);
    }

    #[Test]
    public function converts_using_repository_rates(): void
    {
        $this->repository->shouldReceive('getRate')
            ->with('USD', 'EUR')
            ->andReturn('0.92');
        $this->repository->shouldReceive('getRate')
            ->with('USD', 'USD')
            ->never();
        $this->repository->shouldReceive('getRate')
            ->with('USD', 'RUB')
            ->andReturn('95.5');

        $result = $this->service->convert('100', 'EUR', 'RUB');
        // 100 EUR -> 100/0.92 USD -> * 95.5 RUB
        $expected = bcmul(bcdiv('100', '0.92', 18), '95.5', 18);
        $this->assertSame(rtrim(rtrim($expected, '0'), '.'), $result);
    }

    #[Test]
    public function throws_when_from_currency_rate_missing(): void
    {
        $this->repository->shouldReceive('getRate')
            ->with('USD', 'XXX')
            ->andReturn(null);
        $this->repository->shouldReceive('getRate')
            ->with('USD', 'USD')
            ->andReturn('1');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(__('currency.errors.rate_not_found', ['currency' => 'XXX']));
        $this->service->convert('100', 'XXX', 'USD');
    }

    #[Test]
    public function throws_when_to_currency_rate_missing(): void
    {
        $this->repository->shouldReceive('getRate')
            ->with('USD', 'USD')
            ->andReturn('1');
        $this->repository->shouldReceive('getRate')
            ->with('USD', 'YYY')
            ->andReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(__('currency.errors.rate_not_found', ['currency' => 'YYY']));
        $this->service->convert('100', 'USD', 'YYY');
    }

    #[Test]
    public function throws_on_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(__('currency.errors.amount_invalid'));
        $this->service->convert('-1', 'USD', 'EUR');
    }

    #[Test]
    public function precise_arithmetic_no_float_errors(): void
    {
        $this->repository->shouldReceive('getRate')
            ->with('USD', 'USD')
            ->andReturn('1');
        $this->repository->shouldReceive('getRate')
            ->with('USD', 'EUR')
            ->andReturn('0.92');

        $result = $this->service->convert('1', 'USD', 'EUR');
        $this->assertSame('0.92', $result);
    }
}
