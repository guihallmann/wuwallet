<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Wallet;
use App\Models\WalletAsset;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class AssetSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sleep::fake();

        config(['services.brapi.base_url' => 'https://brapi.test', 'services.brapi.key' => null]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function ticker(string $symbol, array $overrides = []): array
    {
        return array_replace_recursive([
            'symbol' => $symbol,
            'name' => "Company {$symbol}",
            'longName' => "Long {$symbol}",
            'assetType' => 'stock',
            'subType' => 'stock',
            'sector' => 'Energy Minerals',
            'subsector' => 'Petróleo, Gás e Biocombustíveis',
            'isActive' => true,
            'quote' => ['lastPrice' => 36.6549],
        ], $overrides);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function fakeTickers(array $items): void
    {
        Http::fake(['brapi.test/*' => Http::response([
            'results' => $items,
            'pagination' => ['hasNextPage' => false],
        ])]);
    }

    public function test_it_maps_subsector_to_sector_and_stores_price(): void
    {
        $this->fakeTickers([$this->ticker('PETR4')]);

        $this->artisan('assets:sync')->assertSuccessful();

        $asset = Asset::query()->where('ticker', 'PETR4')->firstOrFail();
        $this->assertSame('Company PETR4', $asset->name);
        $this->assertSame('Petróleo, Gás e Biocombustíveis', $asset->sector);
        $this->assertSame('36.65', $asset->current_price);
        $this->assertNotNull($asset->price_updated_at);
    }

    public function test_it_requests_stocks_sorted_by_symbol_for_stable_pagination(): void
    {
        $this->fakeTickers([$this->ticker('PETR4')]);

        $this->artisan('assets:sync')->assertSuccessful();

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/api/v2/tickers')
            && $request['type'] === 'stock'
            && $request['sortBy'] === 'symbol'
            && $request['sortOrder'] === 'asc');
    }

    public function test_it_aborts_without_deactivating_the_catalog_when_no_ticker_is_tradable(): void
    {
        Asset::query()->create([
            'ticker' => 'PETR4',
            'name' => 'Petrobras',
            'sector' => 'Energy Minerals',
            'current_price' => 30,
        ]);
        $this->fakeTickers([$this->ticker('PETR4F')]);

        $this->artisan('assets:sync')->assertFailed();

        $this->assertTrue(Asset::query()->where('ticker', 'PETR4')->value('is_active'));
        $this->assertFalse(Asset::query()->where('ticker', 'PETR4F')->exists());
    }

    public function test_it_clears_the_cached_sector_list(): void
    {
        Cache::put(Asset::SECTORS_CACHE_KEY, ['Stale'], now()->addMinutes(5));
        $this->fakeTickers([$this->ticker('PETR4')]);

        $this->artisan('assets:sync')->assertSuccessful();

        $this->assertFalse(Cache::has(Asset::SECTORS_CACHE_KEY));
    }

    public function test_it_falls_back_to_sector_when_subsector_is_null(): void
    {
        $this->fakeTickers([$this->ticker('VALE3', ['subsector' => null])]);

        $this->artisan('assets:sync')->assertSuccessful();

        $this->assertSame('Energy Minerals', Asset::query()->where('ticker', 'VALE3')->value('sector'));
    }

    public function test_it_skips_inactive_unpriced_unclassified_and_fractional_tickers(): void
    {
        $this->fakeTickers([
            $this->ticker('OK3'),
            $this->ticker('OFF3', ['isActive' => false]),
            $this->ticker('NULL3', ['quote' => ['lastPrice' => null]]),
            $this->ticker('NOSEC3', ['sector' => null, 'subsector' => null]),
            $this->ticker('FRAC3F'),
        ]);

        $this->artisan('assets:sync')->expectsOutput('Fetched 5, upserted 1, skipped 4.')->assertSuccessful();

        $this->assertSame(['OK3'], Asset::query()->pluck('ticker')->all());
    }

    public function test_it_updates_existing_assets_keeping_their_ids_and_wallet_links(): void
    {
        $asset = Asset::query()->create([
            'ticker' => 'PETR4',
            'name' => 'Old',
            'sector' => 'Old',
            'current_price' => 10,
        ]);
        $wallet = Wallet::factory()->create();
        WalletAsset::query()->create([
            'wallet_id' => $wallet->getKey(),
            'asset_id' => $asset->getKey(),
            'target_percentage' => 100,
        ]);

        $this->fakeTickers([$this->ticker('PETR4', ['quote' => ['lastPrice' => 40]])]);

        $this->artisan('assets:sync')->assertSuccessful();

        $fresh = Asset::query()->where('ticker', 'PETR4')->firstOrFail();
        $this->assertSame($asset->getKey(), $fresh->getKey());
        $this->assertSame('40.00', $fresh->current_price);
        $this->assertSame(1, WalletAsset::query()->count());
    }

    public function test_it_never_deletes_assets_missing_from_the_response(): void
    {
        Asset::query()->create([
            'ticker' => 'GONE3',
            'name' => 'Gone',
            'sector' => 'X',
            'current_price' => 5,
        ]);
        $this->fakeTickers([$this->ticker('PETR4')]);

        $this->artisan('assets:sync')->assertSuccessful();

        $this->assertSame('5.00', Asset::query()->where('ticker', 'GONE3')->value('current_price'));
        $this->assertFalse(Asset::query()->where('ticker', 'GONE3')->value('is_active'));
    }

    public function test_command_fails_gracefully_when_the_api_is_down(): void
    {
        config(['services.brapi.max_attempts' => 2]);
        Http::fake(['brapi.test/*' => Http::response(['error' => true], 503)]);

        $this->artisan('assets:sync')->assertFailed();

        $this->assertSame(0, Asset::query()->count());
    }

    public function test_it_is_scheduled_daily_at_midnight_sao_paulo(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn (Event $e): bool => str_contains($e->command, 'assets:sync'));

        $this->assertNotNull($event);
        $this->assertSame('0 0 * * *', $event->expression);
        $this->assertSame('America/Sao_Paulo', (string) $event->timezone);
    }
}
