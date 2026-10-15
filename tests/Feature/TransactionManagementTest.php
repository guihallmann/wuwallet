<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Company;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $company = Company::query()->create([
            'name' => 'Empresa de teste',
            'code' => 'TEST',
            'sector' => 'Tecnologia',
        ]);

        return Asset::query()->create([
            'company_id' => $company->getKey(),
            'ticker' => 'TEST3',
            'asset_type' => 'pn',
            'current_price' => $price,
        ]);
    }

    public function test_client_can_open_transaction_form_with_their_wallets_and_assets(): void
    {
        $client = $this->client();
        $wallet = Wallet::factory()->for($client)->create();
        $asset = $this->asset();

        $response = $this->actingAs($client)->get(route('transactions.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('transactions/create')
            ->where('wallets.0.id', $wallet->getKey())
            ->where('assets.0.id', $asset->getKey())
            ->has('today'));
    }

    public function test_transactions_root_opens_the_registration_form(): void
    {
        $response = $this->actingAs($this->client())->get(route('transactions.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('transactions/create'));
    }

    public function test_client_can_buy_a_new_asset_and_save_the_current_price_snapshot(): void
    {
        $client = $this->client();
        $wallet = Wallet::factory()->for($client)->create();
        $asset = $this->asset();

        $response = $this->actingAs($client)->post(route('transactions.store'), [
            'wallet_id' => $wallet->getKey(),
            'asset_id' => $asset->getKey(),
            'transaction_type' => 'buy',
            'quantity' => 5,
            'transaction_date' => today()->toDateString(),
            'target_percentage' => 25,
        ]);

        $response->assertRedirect(route('wallets.index'));
        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $wallet->getKey(),
            'asset_id' => $asset->getKey(),
            'transaction_type' => 'buy',
            'quantity' => 5,
            'unit_price' => '42.50',
        ]);
        $this->assertDatabaseHas('wallet_assets', [
            'wallet_id' => $wallet->getKey(),
            'asset_id' => $asset->getKey(),
            'current_quantity' => 5,
            'target_percentage' => '25.00',
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
            'current_quantity' => 8,
        ]);

        $response = $this->actingAs($client)->post(route('transactions.store'), [
            'wallet_id' => $wallet->getKey(),
            'asset_id' => $asset->getKey(),
            'transaction_type' => 'sell',
            'quantity' => 3,
            'transaction_date' => today()->toDateString(),
        ]);

        $response->assertRedirect(route('wallets.index'));
        $this->assertDatabaseHas('wallet_assets', [
            'wallet_id' => $wallet->getKey(),
            'asset_id' => $asset->getKey(),
            'current_quantity' => 5,
        ]);
        $this->assertDatabaseHas('transactions', [
            'transaction_type' => 'sell',
            'quantity' => 3,
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
            'current_quantity' => 2,
        ]);

        $response = $this->actingAs($client)->from(route('transactions.create'))
            ->post(route('transactions.store'), [
                'wallet_id' => $wallet->getKey(),
                'asset_id' => $asset->getKey(),
                'transaction_type' => 'sell',
                'quantity' => 3,
                'transaction_date' => today()->toDateString(),
            ]);

        $response->assertSessionHasErrors('quantity');
        $this->assertDatabaseMissing('transactions', ['wallet_id' => $wallet->getKey()]);
        $this->assertDatabaseHas('wallet_assets', [
            'wallet_id' => $wallet->getKey(),
            'asset_id' => $asset->getKey(),
            'current_quantity' => 2,
        ]);
    }

    public function test_buying_a_new_asset_requires_a_target_percentage(): void
    {
        $client = $this->client();
        $wallet = Wallet::factory()->for($client)->create();
        $asset = $this->asset();

        $response = $this->from(route('transactions.create'))
            ->actingAs($client)
            ->post(route('transactions.store'), [
                'wallet_id' => $wallet->getKey(),
                'asset_id' => $asset->getKey(),
                'transaction_type' => 'buy',
                'quantity' => 1,
                'transaction_date' => today()->toDateString(),
            ]);

        $response->assertSessionHasErrors('target_percentage');
        $this->assertDatabaseMissing('transactions', ['wallet_id' => $wallet->getKey()]);
    }

    public function test_transaction_date_must_be_today(): void
    {
        $client = $this->client();
        $wallet = Wallet::factory()->for($client)->create();
        $asset = $this->asset();

        $response = $this->from(route('transactions.create'))
            ->actingAs($client)
            ->post(route('transactions.store'), [
                'wallet_id' => $wallet->getKey(),
                'asset_id' => $asset->getKey(),
                'transaction_type' => 'buy',
                'quantity' => 1,
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

        $response = $this->from(route('transactions.create'))
            ->actingAs($client)
            ->post(route('transactions.store'), [
                'wallet_id' => $wallet->getKey(),
                'asset_id' => $asset->getKey(),
                'transaction_type' => 'buy',
                'quantity' => 1,
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

    public function test_client_cannot_register_a_transaction_for_another_clients_wallet(): void
    {
        $client = $this->client();
        $otherClient = $this->client();
        $wallet = Wallet::factory()->for($otherClient)->create();
        $asset = $this->asset();

        $response = $this->from(route('transactions.create'))
            ->actingAs($client)
            ->post(route('transactions.store'), [
                'wallet_id' => $wallet->getKey(),
                'asset_id' => $asset->getKey(),
                'transaction_type' => 'buy',
                'quantity' => 1,
                'transaction_date' => today()->toDateString(),
                'target_percentage' => 25,
            ]);

        $response->assertSessionHasErrors('wallet_id');
        $this->assertDatabaseMissing('transactions', ['wallet_id' => $wallet->getKey()]);
    }

    public function test_non_client_cannot_open_or_submit_transaction_form(): void
    {
        $analyst = $this->analyst();
        $wallet = Wallet::factory()->create();
        $asset = $this->asset();

        $this->actingAs($analyst)->get(route('transactions.create'))->assertForbidden();
        $this->actingAs($analyst)->post(route('transactions.store'), [
            'wallet_id' => $wallet->getKey(),
            'asset_id' => $asset->getKey(),
            'transaction_type' => 'buy',
            'quantity' => 1,
            'transaction_date' => today()->toDateString(),
            'target_percentage' => 25,
        ])->assertForbidden();

        $this->assertDatabaseCount('transactions', 0);
    }
}
