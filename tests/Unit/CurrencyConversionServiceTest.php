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
        $this->repository->shouldReceive('getRatesForCodes')
            ->once()
            ->with('USD', ['EUR', 'RUB'])
            ->andReturn(['EUR' => '0.92', 'RUB' => '95.5']);

        $result = $this->service->convert('100', 'EUR', 'RUB');
        $expected = bcmul(bcdiv('100', '0.92', 18), '95.5', 18);
        $this->assertSame(rtrim(rtrim($expected, '0'), '.'), $result);
    }

    #[Test]
    public function throws_when_from_currency_rate_missing(): void
    {
        $this->repository->shouldReceive('getRatesForCodes')
            ->once()
            ->andReturn([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(__('currency.errors.rate_not_found', ['currency' => 'XXX']));
        $this->service->convert('100', 'XXX', 'USD');
    }

    #[Test]
    public function throws_when_to_currency_rate_missing(): void
    {
        $this->repository->shouldReceive('getRatesForCodes')
            ->once()
            ->andReturn(['USD' => '1']);

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
        $this->repository->shouldReceive('getRatesForCodes')
            ->once()
            ->andReturn(['EUR' => '0.92']);

        $result = $this->service->convert('1', 'USD', 'EUR');
        $this->assertSame('0.92', $result);
    }
}
