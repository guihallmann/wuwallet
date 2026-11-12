<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TransactionManagementTest extends TestCase
{
    use RefreshDatabase;

    private function client(): User
    {
        return User::factory()->create([
            'role' => UserRole::CLIENT,
        ]);
    }

    private function analyst(): User
    {
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'manager_id' => null,
        ]);

        return User::factory()->create([
            'role' => UserRole::ANALYST,
            'manager_id' => $manager->getKey(),
        ]);
    }

    private function asset(string $price = '42.50'): Asset
    {
        return Asset::query()->create([
            'ticker' => 'TEST3',
            'name' => 'Empresa de teste',
            'sector' => 'Tecnologia',
            'current_price' => $price,
        ]);
    }

    public function test_client_can_open_transaction_form_for_a_selected_wallet(): void
    {
        $client = $this->client();
        $wallet = Wallet::factory()->for($client)->create();
        $asset = $this->asset();
        Asset::query()->create([
            'ticker' => 'OTHER3',
            'name' => 'Outro ativo',
            'sector' => 'Tecnologia',
            'current_price' => '15.00',
        ]);
        WalletAsset::query()->create([
            'wallet_id' => $wallet->getKey(),
            'asset_id' => $asset->getKey(),
            'target_percentage' => 100,
            'current_quantity' => 0,
        ]);

        $response = $this->actingAs($client)->get(route('wallets.transactions.create', $wallet));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('transactions/create')
            ->where('wallet.id', $wallet->getKey())
            ->where('assets.0.id', $asset->getKey())
            ->where('assets.0.name', 'Empresa de teste')
            ->where('assets.0.current_price', 42.5)
            ->where('assets.0.current_quantity', 0)
            ->has('assets', 1)
            ->has('today'));
    }

    public function test_search_and_transaction_form_exclude_inactive_and_fractional_assets(): void
    {
        $client = $this->client();
        $wallet = Wallet::factory()->for($client)->create();
        $tradableAsset = $this->asset();
        $inactiveAsset = Asset::query()->create([
            'ticker' => 'OFF3',
            'name' => 'Ativo inativo',
            'sector' => 'Tecnologia',
            'current_price' => '10.00',
            'is_active' => false,
        ]);
        $fractionalAsset = Asset::query()->create([
            'ticker' => 'FRAC3F',
            'name' => 'Ativo fracionário',
            'sector' => 'Tecnologia',
            'current_price' => '10.00',
        ]);

        foreach ([$tradableAsset, $inactiveAsset, $fractionalAsset] as $asset) {
            WalletAsset::query()->create([
                'wallet_id' => $wallet->getKey(),
                'asset_id' => $asset->getKey(),
                'target_percentage' => 100,
                'current_quantity' => $asset->is($tradableAsset) ? 150 : 100,
            ]);
        }

        $this->actingAs($client)
            ->get(route('assets.search'))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.ticker', 'TEST3');

        $this->actingAs($client)
            ->get(route('wallets.transactions.create', $wallet))
            ->assertInertia(fn ($page) => $page
                ->where('assets.0.id', $tradableAsset->getKey())
                ->where('assets.0.current_quantity', 150)
                ->where('wallet.sellableAssetIds', [$tradableAsset->getKey()])
                ->has('assets', 1));
    }

    public function test_transactions_shortcut_redirects_to_wallet_selection(): void
    {
        $client = $this->client();
        $response = $this->actingAs($client)->get(route('transactions.index'));

        $response->assertRedirect(route('wallets.index'));
        $this->actingAs($client)
            ->get(route('transactions.create'))
            ->assertRedirect(route('wallets.index'));
    }

    public function test_client_can_access_all_transactions_for_the_selected_wallet(): void
    {
        $client = $this->client();
        $wallet = Wallet::factory()->for($client)->create();
        $asset = $this->asset();
        $olderTransaction = $wallet->transactions()->create([
            'asset_id' => $asset->getKey(),
            'transaction_type' => 'buy',
            'quantity' => 2,
            'unit_price' => '40.00',
            'transaction_date' => today()->subDay(),
        ]);
        $newerTransaction = $wallet->transactions()->create([
            'asset_id' => $asset->getKey(),
            'transaction_type' => 'sell',
            'quantity' => 1,
            'unit_price' => '42.50',
            'transaction_date' => today(),
        ]);

        $response = $this->actingAs($client)->get(route('wallets.transactions.index', $wallet));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('transactions/index')
            ->where('wallet.id', $wallet->getKey())
            ->has('transactions', 2)
            ->where('transactions.0.id', $newerTransaction->getKey())
            ->where('transactions.0.unit_price', '42.50')
            ->where('transactions.1.id', $olderTransaction->getKey())
            ->where('transactions.1.unit_price', '40.00'));
    }

    public function test_client_can_buy_a_zero_quantity_asset_already_in_the_wallet_without_changing_its_allocation(): void
    {
        $client = $this->client();
        $wallet = Wallet::factory()->for($client)->create();
        $asset = $this->asset();
        WalletAsset::query()->create([
            'wallet_id' => $wallet->getKey(),
            'asset_id' => $asset->getKey(),
            'target_percentage' => 25,
            'current_quantity' => 0,
        ]);

        $response = $this->actingAs($client)->post(route('wallets.transactions.store', $wallet), [
            'asset_id' => $asset->getKey(),
            'transaction_type' => 'buy',
            'quantity' => 100,
            'transaction_date' => today()->toDateString(),
        ]);

        $response->assertRedirect(route('wallets.transactions.index', $wallet));
        $asset->update(['current_price' => '50.00']);

        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $wallet->getKey(),
            'asset_id' => $asset->getKey(),
            'transaction_type' => 'buy',
            'quantity' => 100,
            'unit_price' => '42.50',
        ]);
        $this->assertDatabaseHas('wallet_assets', [
            'wallet_id' => $wallet->getKey(),
            'asset_id' => $asset->getKey(),
            'current_quantity' => 100,
            'target_percentage' => '25.00',
        ]);
    }

    public function test_client_cannot_buy_an_asset_not_associated_with_the_wallet(): void
    {
        $client = $this->client();
        $wallet = Wallet::factory()->for($client)->create();
        $asset = $this->asset();

        $response = $this->from(route('wallets.transactions.create', $wallet))
            ->actingAs($client)
            ->post(route('wallets.transactions.store', $wallet), [
                'asset_id' => $asset->getKey(),
                'transaction_type' => 'buy',
                'quantity' => 100,
                'transaction_date' => today()->toDateString(),
            ]);

        $response->assertSessionHasErrors('asset_id');
        $this->assertSame(
            'Este ativo não está cadastrado nesta carteira.',
            $response->getSession()->get('errors')->first('asset_id'),
        );
        $this->assertDatabaseMissing('transactions', ['wallet_id' => $wallet->getKey()]);
        $this->assertDatabaseMissing('wallet_assets', [
            'wallet_id' => $wallet->getKey(),
            'asset_id' => $asset->getKey(),
        ]);
    }

    public function test_client_can_sell_an_asset_and_the_wallet_quantity_is_decreased(): void
    {
        $client = $this->client();
        $wallet = Wallet::factory()->for($client)->create();
        $asset = $this->asset();
        WalletAsset::query()->create([
            'wallet_id' => $wallet->getKey(),
            'asset_id' => $asset->getKey(),
            'target_percentage' => 25,
            'current_quantity' => 200,
        ]);

        $response = $this->actingAs($client)->post(route('wallets.transactions.store', $wallet), [
            'asset_id' => $asset->getKey(),
            'transaction_type' => 'sell',
            'quantity' => 100,
            'transaction_date' => today()->toDateString(),
        ]);

        $response->assertRedirect(route('wallets.transactions.index', $wallet));
        $this->assertDatabaseHas('wallet_assets', [
            'wallet_id' => $wallet->getKey(),
            'asset_id' => $asset->getKey(),
            'current_quantity' => 100,
        ]);
        $this->assertDatabaseHas('transactions', [
            'transaction_type' => 'sell',
            'quantity' => 100,
            'unit_price' => '42.50',
        ]);
    }

    public function test_client_cannot_sell_more_than_the_wallet_balance(): void
    {
        $client = $this->client();
        $wallet = Wallet::factory()->for($client)->create();
        $asset = $this->asset();
        WalletAsset::query()->create([
            'wallet_id' => $wallet->getKey(),
            'asset_id' => $asset->getKey(),
            'target_percentage' => 25,
            'current_quantity' => 200,
        ]);

        $response = $this->actingAs($client)->from(route('wallets.transactions.create', $wallet))
            ->post(route('wallets.transactions.store', $wallet), [
                'asset_id' => $asset->getKey(),
                'transaction_type' => 'sell',
                'quantity' => 300,
                'transaction_date' => today()->toDateString(),
            ]);

        $response->assertSessionHasErrors('quantity');
        $this->assertDatabaseMissing('transactions', ['wallet_id' => $wallet->getKey()]);
        $this->assertDatabaseHas('wallet_assets', [
            'wallet_id' => $wallet->getKey(),
            'asset_id' => $asset->getKey(),
            'current_quantity' => 200,
        ]);
    }

    /**
     * @return array<string, array{string, int, int, string}>
     */
    public static function invalidTransactionLots(): array
    {
        return [
            'below minimum' => ['buy', 50, 0, 'O campo quantidade deve ser pelo menos 100.'],
            'buy not in a lot' => ['buy', 150, 0, 'O campo quantidade deve ser um múltiplo de 100.'],
            'sell not in a lot' => ['sell', 150, 200, 'O campo quantidade deve ser um múltiplo de 100.'],
        ];
    }

    #[DataProvider('invalidTransactionLots')]
    public function test_client_cannot_register_a_transaction_with_a_quantity_outside_a_lot(
        string $type,
        int $quantity,
        int $openingQuantity,
        string $expectedError,
    ): void {
        $client = $this->client();
        $wallet = Wallet::factory()->for($client)->create();
        $asset = $this->asset();
        WalletAsset::query()->create([
            'wallet_id' => $wallet->getKey(),
            'asset_id' => $asset->getKey(),
            'target_percentage' => 25,
            'current_quantity' => $openingQuantity,
        ]);

        $response = $this->from(route('wallets.transactions.create', $wallet))
            ->actingAs($client)
            ->post(route('wallets.transactions.store', $wallet), [
                'asset_id' => $asset->getKey(),
                'transaction_type' => $type,
                'quantity' => $quantity,
                'transaction_date' => today()->toDateString(),
            ]);

        $response->assertSessionHasErrors(['quantity' => $expectedError]);
        $this->assertDatabaseMissing('transactions', ['wallet_id' => $wallet->getKey()]);
        $this->assertDatabaseHas('wallet_assets', [
            'wallet_id' => $wallet->getKey(),
            'asset_id' => $asset->getKey(),
            'current_quantity' => $openingQuantity,
        ]);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function unavailableAssets(): array
    {
        return [
            'inactive ticker' => ['OFF3', false],
            'fractional ticker' => ['FRAC3F', true],
        ];
    }

    #[DataProvider('unavailableAssets')]
    public function test_client_cannot_register_a_transaction_for_an_inactive_or_fractional_asset(
        string $ticker,
        bool $isActive,
    ): void {
        $client = $this->client();
        $wallet = Wallet::factory()->for($client)->create();
        $asset = Asset::query()->create([
            'ticker' => $ticker,
            'name' => 'Ativo não permitido',
            'sector' => 'Tecnologia',
            'current_price' => '42.50',
            'is_active' => $isActive,
        ]);
        WalletAsset::query()->create([
            'wallet_id' => $wallet->getKey(),
            'asset_id' => $asset->getKey(),
            'target_percentage' => 100,
            'current_quantity' => 200,
        ]);

        $response = $this->from(route('wallets.transactions.create', $wallet))
            ->actingAs($client)
            ->post(route('wallets.transactions.store', $wallet), [
                'asset_id' => $asset->getKey(),
                'transaction_type' => 'sell',
                'quantity' => 100,
                'transaction_date' => today()->toDateString(),
            ]);

        $response->assertSessionHasErrors([
            'asset_id' => 'Este ativo está inativo ou não está disponível para movimentações.',
        ]);
        $this->assertDatabaseMissing('transactions', ['wallet_id' => $wallet->getKey()]);
        $this->assertDatabaseHas('wallet_assets', [
            'wallet_id' => $wallet->getKey(),
            'asset_id' => $asset->getKey(),
            'current_quantity' => 200,
        ]);
    }

    public function test_client_cannot_change_allocation_through_a_transaction(): void
    {
        $client = $this->client();
        $wallet = Wallet::factory()->for($client)->create();
        $asset = $this->asset();
        WalletAsset::query()->create([
            'wallet_id' => $wallet->getKey(),
            'asset_id' => $asset->getKey(),
            'target_percentage' => 25,
            'current_quantity' => 0,
        ]);

        $response = $this->actingAs($client)->post(route('wallets.transactions.store', $wallet), [
            'asset_id' => $asset->getKey(),
            'transaction_type' => 'buy',
            'quantity' => 100,
            'transaction_date' => today()->toDateString(),
            'target_percentage' => 80,
        ]);

        $response->assertRedirect(route('wallets.transactions.index', $wallet));
        $this->assertDatabaseHas('wallet_assets', [
            'wallet_id' => $wallet->getKey(),
            'asset_id' => $asset->getKey(),
            'target_percentage' => '25.00',
            'current_quantity' => 100,
        ]);
    }

    public function test_transaction_date_must_be_today(): void
    {
        $client = $this->client();
        $wallet = Wallet::factory()->for($client)->create();
        $asset = $this->asset();

        $response = $this->from(route('wallets.transactions.create', $wallet))
            ->actingAs($client)
            ->post(route('wallets.transactions.store', $wallet), [
                'asset_id' => $asset->getKey(),
                'transaction_type' => 'buy',
                'quantity' => 100,
                'transaction_date' => today()->subDay()->toDateString(),
                'target_percentage' => 25,
            ]);

        $response->assertSessionHasErrors('transaction_date');
        $this->assertDatabaseMissing('transactions', ['wallet_id' => $wallet->getKey()]);
    }

    public function test_transaction_is_rejected_when_the_asset_has_no_current_quote(): void
    {
        $client = $this->client();
        $wallet = Wallet::factory()->for($client)->create();
        $asset = $this->asset('0.00');

        $response = $this->from(route('wallets.transactions.create', $wallet))
            ->actingAs($client)
            ->post(route('wallets.transactions.store', $wallet), [
                'asset_id' => $asset->getKey(),
                'transaction_type' => 'buy',
                'quantity' => 100,
                'transaction_date' => today()->toDateString(),
                'target_percentage' => 25,
            ]);

        $response->assertSessionHasErrors('asset_id');
        $this->assertDatabaseMissing('transactions', ['wallet_id' => $wallet->getKey()]);
        $this->assertDatabaseMissing('wallet_assets', [
            'wallet_id' => $wallet->getKey(),
            'asset_id' => $asset->getKey(),
        ]);
    }

    public function test_client_cannot_view_transactions_for_another_clients_wallet(): void
    {
        $client = $this->client();
        $otherClient = $this->client();
        $wallet = Wallet::factory()->for($otherClient)->create();

        $response = $this->actingAs($client)
            ->get(route('wallets.transactions.index', $wallet));

        $response->assertForbidden();
        $this->actingAs($client)
            ->get(route('wallets.transactions.create', $wallet))
            ->assertForbidden();
    }

    public function test_client_cannot_register_a_transaction_for_another_clients_wallet(): void
    {
        $client = $this->client();
        $otherClient = $this->client();
        $wallet = Wallet::factory()->for($otherClient)->create();
        $asset = $this->asset();

        $response = $this->actingAs($client)
            ->post(route('wallets.transactions.store', $wallet), [
                'asset_id' => $asset->getKey(),
                'transaction_type' => 'buy',
                'quantity' => 1,
                'transaction_date' => today()->toDateString(),
                'target_percentage' => 25,
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('transactions', ['wallet_id' => $wallet->getKey()]);
    }

    public function test_non_client_cannot_open_or_submit_transaction_form(): void
    {
        $analyst = $this->analyst();
        $wallet = Wallet::factory()->create();
        $asset = $this->asset();

        $this->actingAs($analyst)->get(route('wallets.transactions.create', $wallet))->assertForbidden();
        $this->actingAs($analyst)->post(route('wallets.transactions.store', $wallet), [
            'asset_id' => $asset->getKey(),
            'transaction_type' => 'buy',
            'quantity' => 1,
            'transaction_date' => today()->toDateString(),
            'target_percentage' => 25,
        ])->assertForbidden();

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_transaction_routes_require_authentication(): void
    {
        $wallet = Wallet::factory()->create();

        $this->get(route('wallets.transactions.index', $wallet))
            ->assertRedirect(route('login'));
        $this->get(route('wallets.transactions.create', $wallet))
            ->assertRedirect(route('login'));
        $this->post(route('wallets.transactions.store', $wallet))
            ->assertRedirect(route('login'));
    }
}
