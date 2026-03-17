<?php

namespace App\Services\Currency;

use App\Contracts\CurrencyRateProvider;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Log;
use RuntimeException;

readonly class FreeCurrencyApiProvider implements CurrencyRateProvider
{
    public function __construct(
        private HttpFactory $http,
        private string      $baseUrl,
        private string      $apiKey,
    ) {
    }

    public function getLatestRates(string $baseCurrency, ?array $currencies = null): array
    {
        $params = [
            'apikey' => $this->apiKey,
            'base_currency' => $baseCurrency,
        ];
        if ($currencies !== null && $currencies !== []) {
            $params['currencies'] = implode(',', $currencies);
        }

        $response = $this->http->withHeaders([
            'apikey' => $this->apiKey,
        ])->get("$this->baseUrl/latest", $params);

        if (! $response->successful()) {
            Log::warning('FreeCurrencyAPI request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException(
                'FreeCurrencyAPI request failed: ' . $response->status() . ' ' . $response->body()
            );
        }

        $data = $response->json('data');
        if (! is_array($data)) {
            throw new RuntimeException('FreeCurrencyAPI invalid response: missing data');
        }

        $result = [];
        foreach ($data as $code => $rate) {
            $result[(string) $code] = (string) $rate;
        }

        return $result;
    }
}
