<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConvertCurrencyRequest;
use App\Models\Currency;
use App\Services\Currency\CurrencyConversionService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class CurrencyConversionController extends Controller
{
    public function __construct(
        private readonly CurrencyConversionService $conversionService,
    ) {
    }

    /**
     * Список доступных валют для выбора.
     * GET /api/currencies
     */
    public function index(): JsonResponse
    {
        $currencies = Currency::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (Currency $c) => [
                'code' => $c->code,
                'name' => $c->name ?? $c->code,
            ]);

        return response()->json(['data' => $currencies->values()->all()]);
    }

    /**
     * Округление до заданного числа знаков после запятой (bcmath, без float).
     */
    private function roundToDecimals(string $value, int $decimals): string
    {
        $factor = bcpow('10', (string) $decimals, 0);
        $scaled = bcmul($value, $factor, $decimals + 2);
        $rounded = bcadd($scaled, '0.5', 0);
        return bcdiv($rounded, $factor, $decimals);
    }

    /**
     * Конвертировать сумму из одной валюты в другую.
     * GET /api/convert?amount=100&from=USD&to=EUR
     */
    public function convert(ConvertCurrencyRequest $request): JsonResponse
    {
        $amount = $request->validated('amount');
        $from = strtoupper($request->validated('from'));
        $to = strtoupper($request->validated('to'));

        try {
            $result = $this->conversionService->convert(
                (string) $amount,
                $from,
                $to,
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $resultRounded = $this->roundToDecimals($result, 2);

        return response()->json([
            'amount' => $amount,
            'from' => $from,
            'to' => $to,
            'result' => $resultRounded,
        ]);
    }
}
