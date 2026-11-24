<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BuySuggestionTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = User::factory()->create(['role' => UserRole::CLIENT]);
        $this->wallet = Wallet::factory()->for($this->client)->create();
    }

    /**
     * @param  array<string, mixed>  $assetAttributes
     */
    private function position(string $ticker, string $price, int $quantity, int $target, array $assetAttributes = []): Asset
    {
        $asset = Asset::query()->create([
            'ticker' => $ticker,
            'name' => "Empresa {$ticker}",
            'sector' => 'Tecnologia',
            'current_price' => $price,
            ...$assetAttributes,
        ]);

        WalletAsset::query()->create([
            'wallet_id' => $this->wallet->getKey(),
            'asset_id' => $asset->getKey(),
            'target_percentage' => $target,
            'current_quantity' => $quantity,
        ]);

        return $asset;
    }

    private function seedBalancedWalletMissingOneAsset(): void
    {
        $this->position('AAAA3', '10.00', 300, 30);
        $this->position('BBBB4', '25.00', 200, 50);
        $this->position('CCCC3', '5.00', 0, 20);
    }

    /**
     * @return array{summary: array<string, mixed>, items: list<array<string, mixed>>}
     */
    private function suggest(string $amount): array
    {
        $suggestion = null;

        $this->actingAs($this->client)
            ->get(route('wallets.show', [$this->wallet, 'amount' => $amount]))
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page) use (&$suggestion): void {
                $page->missing('suggestion')
                    ->reloadOnly('suggestion', function (AssertableInertia $reload) use (&$suggestion): void {
                        $suggestion = $reload->toArray()['props']['suggestion'];
                    });
            });

        $this->assertIsArray($suggestion);

        return $suggestion;
    }

    /**
     * @param  array{items: list<array<string, mixed>>}  $suggestion
     * @return array<string, mixed>
     */
    private function item(array $suggestion, string $ticker): array
    {
        return collect($suggestion['items'])->firstWhere('ticker', $ticker);
    }

    public function test_wallet_page_does_not_compute_a_suggestion_by_default(): void
    {
        $this->seedBalancedWalletMissingOneAsset();

        $this->actingAs($this->client)
            ->get(route('wallets.show', $this->wallet))
            ->assertOk()
            ->assertInertia(fn(AssertableInertia $page) => $page
                ->component('wallets/show')
                ->missing('suggestion'));
    }

    public function test_contribution_goes_to_the_asset_furthest_below_its_target(): void
    {
        $this->seedBalancedWalletMissingOneAsset();

        $suggestion = $this->suggest('2000');

        $this->assertSame(400, $this->item($suggestion, 'CCCC3')['quantity']);
        $this->assertSame(4, $this->item($suggestion, 'CCCC3')['lots']);
        $this->assertSame(0, $this->item($suggestion, 'AAAA3')['quantity']);
        $this->assertSame(0, $this->item($suggestion, 'BBBB4')['quantity']);
        $this->assertEquals(20, $this->item($suggestion, 'CCCC3')['after_percentage']);
        $this->assertEquals(2000, $suggestion['summary']['spent']);
        $this->assertEquals(0, $suggestion['summary']['leftover']);
        $this->assertEquals(10000, $suggestion['summary']['total_after']);
        $this->assertSame('CCCC3', $suggestion['items'][0]['ticker']);
    }

    public function test_only_whole_lots_are_suggested_and_leftover_is_below_the_cheapest_lot(): void
    {
        $this->seedBalancedWalletMissingOneAsset();

        $suggestion = $this->suggest('2300');

        foreach ($suggestion['items'] as $item) {
            $this->assertSame(0, $item['quantity'] % Asset::LOT_SIZE);
        }

        $this->assertSame(400, $this->item($suggestion, 'CCCC3')['quantity']);
        $this->assertEquals(2000, $suggestion['summary']['spent']);
        $this->assertEquals(300, $suggestion['summary']['leftover']);
        $this->assertSame(['ticker' => 'CCCC3', 'cost' => 500], [
            'ticker' => $suggestion['summary']['cheapest_lot']['ticker'],
            'cost' => (int) $suggestion['summary']['cheapest_lot']['cost'],
        ]);
    }

    public function test_it_keeps_buying_while_a_lot_still_fits_even_slightly_past_the_target(): void
    {
        $this->seedBalancedWalletMissingOneAsset();

        $suggestion = $this->suggest('2600');

        $this->assertSame(500, $this->item($suggestion, 'CCCC3')['quantity']);
        $this->assertEquals(2500, $suggestion['summary']['spent']);
        $this->assertEquals(100, $suggestion['summary']['leftover']);
    }

    public function test_overweight_assets_receive_nothing(): void
    {
        $this->position('AAAA3', '10.00', 600, 30);
        $this->position('BBBB4', '25.00', 200, 50);
        $this->position('CCCC3', '5.00', 0, 20);

        $suggestion = $this->suggest('1000');

        $this->assertSame('above_target', $this->item($suggestion, 'AAAA3')['status']);
        $this->assertSame(0, $this->item($suggestion, 'AAAA3')['quantity']);
        $this->assertSame(200, $this->item($suggestion, 'CCCC3')['quantity']);
    }

    public function test_amount_below_the_cheapest_lot_suggests_nothing(): void
    {
        $this->seedBalancedWalletMissingOneAsset();

        $suggestion = $this->suggest('100');

        $this->assertSame([0, 0, 0], array_column($suggestion['items'], 'quantity'));
        $this->assertEquals(0, $suggestion['summary']['spent']);
        $this->assertEquals(100, $suggestion['summary']['leftover']);
        $this->assertSame('lot_too_expensive', $this->item($suggestion, 'CCCC3')['status']);
    }

    public function test_non_tradable_assets_count_in_totals_but_are_never_suggested(): void
    {
        $this->position('AAAA3', '10.00', 300, 30);
        $this->position('OFFF3', '25.00', 200, 50, ['is_active' => false]);
        $this->position('CCCC3', '5.00', 0, 20);

        $suggestion = $this->suggest('2000');

        $this->assertSame('unavailable', $this->item($suggestion, 'OFFF3')['status']);
        $this->assertSame(0, $this->item($suggestion, 'OFFF3')['quantity']);
        $this->assertEquals(8000, $suggestion['summary']['total_before']);
        $this->assertSame(400, $this->item($suggestion, 'CCCC3')['quantity']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidAmounts(): array
    {
        return [
            'empty' => [''],
            'zero' => ['0'],
            'negative' => ['-10'],
            'not a number' => ['abc'],
            'too many decimals' => ['10.123'],
            'above the maximum' => ['100000000.01'],
        ];
    }

    #[DataProvider('invalidAmounts')]
    public function test_amount_is_validated(string $amount): void
    {
        $this->seedBalancedWalletMissingOneAsset();

        $this->actingAs($this->client)
            ->from(route('wallets.show', $this->wallet))
            ->get(route('wallets.show', [$this->wallet, 'amount' => $amount]))
            ->assertRedirect(route('wallets.show', $this->wallet))
            ->assertSessionHasErrors('amount');
    }

    public function test_other_clients_cannot_request_a_suggestion(): void
    {
        $this->seedBalancedWalletMissingOneAsset();
        $otherClient = User::factory()->create(['role' => UserRole::CLIENT]);

        $this->actingAs($otherClient)
            ->get(route('wallets.show', [$this->wallet, 'amount' => '1000']))
            ->assertForbidden();
    }
}
