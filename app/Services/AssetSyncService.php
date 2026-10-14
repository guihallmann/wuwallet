<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Asset;
use App\Services\Brapi\BrapiClient;
use App\Services\Brapi\BrapiException;
use Illuminate\Support\Facades\Log;

class AssetSyncService
{
    private const UPSERT_CHUNK_SIZE = 500;

    public function __construct(private readonly BrapiClient $client) {}

    /**
     * Imports active B3 stocks and refreshes their last price. Never deletes assets.
     *
     * @return array{fetched: int, upserted: int, skipped: int}
     *
     * @throws BrapiException
     */
    public function sync(): array
    {
        $items = $this->client->tickers(['type' => 'stock']);

        $now = now();
        $rows = [];

        foreach ($items as $item) {
            $row = $this->toRow($item);

            if ($row !== null) {
                $rows[$row['ticker']] = $row + ['price_updated_at' => $now];
            }
        }

        foreach (array_chunk(array_values($rows), self::UPSERT_CHUNK_SIZE) as $chunk) {
            Asset::query()->upsert($chunk, ['ticker'], ['name', 'sector', 'current_price', 'price_updated_at']);
        }

        $summary = [
            'fetched' => count($items),
            'upserted' => count($rows),
            'skipped' => count($items) - count($rows),
        ];

        Log::info('Asset sync finished.', $summary);

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{ticker: string, name: string, sector: string, current_price: string}|null
     */
    private function toRow(array $item): ?array
    {
        if (($item['isActive'] ?? false) !== true) {
            return null;
        }

        $ticker = $this->string($item['symbol'] ?? null);
        $price = is_array($item['quote'] ?? null) ? ($item['quote']['lastPrice'] ?? null) : null;
        $sector = $this->string($item['subsector'] ?? null) ?? $this->string($item['sector'] ?? null);

        if ($ticker === null || $sector === null || ! is_numeric($price) || (float) $price <= 0) {
            return null;
        }

        $ticker = strtoupper($ticker);

        return [
            'ticker' => $ticker,
            'name' => $this->string($item['name'] ?? null) ?? $this->string($item['longName'] ?? null) ?? $ticker,
            'sector' => $sector,
            'current_price' => number_format((float) $price, 2, '.', ''),
        ];
    }

    private function string(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
