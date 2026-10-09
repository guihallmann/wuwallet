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

class WalletManagementTest extends TestCase
{
    use RefreshDatabase;

    private function manager(): User
    {
        return User::factory()->create([
            'role' => UserRole::MANAGER,
            'manager_id' => null,
        ]);
    }

    private function analystFor(User $manager): User
    {
        return User::factory()->create([
            'role' => UserRole::ANALYST,
            'manager_id' => $manager->getKey(),
        ]);
    }

    private function clientFor(User $analyst): User
    {
        return User::factory()->create([
            'role' => UserRole::CLIENT,
            'manager_id' => $analyst->getKey(),
        ]);
    }

    public function test_client_sees_only_their_own_wallets(): void
    {
        $manager = $this->manager();
        $analyst = $this->analystFor($manager);
        $client = $this->clientFor($analyst);
        $wallet = Wallet::factory()->for($client)->create();

        $otherClient = $this->clientFor($analyst);
        Wallet::factory()->for($otherClient)->create();

        $response = $this->actingAs($client)->get(route('wallets.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('wallets/index')
            ->where('mode', 'manage')
            ->has('wallets', 1)
            ->where('wallets.0.id', $wallet->getKey()));
    }

    public function test_wallet_total_value_sums_assets(): void
    {
        $manager = $this->manager();
        $analyst = $this->analystFor($manager);
        $client = $this->clientFor($analyst);
        $wallet = Wallet::factory()->for($client)->create();

        $company = Company::query()->create([
            'name' => 'Petrobras',
            'code' => 'PETR4',
            'sector' => 'Petróleo, Gás e Biocombustíveis',
        ]);

        $asset = Asset::query()->create([
            'company_id' => $company->getKey(),
            'ticker' => 'PETR4',
            'asset_type' => 'pn',
            'current_price' => 30,
        ]);

        WalletAsset::query()->create([
            'wallet_id' => $wallet->getKey(),
            'asset_id' => $asset->getKey(),
            'target_percentage' => 100,
            'current_quantity' => 10,
        ]);

        $response = $this->actingAs($client)->get(route('wallets.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('wallets.0.total_value', 300)
            ->where('totalValue', 300));
    }

    public function test_client_can_create_edit_and_delete_own_wallet(): void
    {
        $manager = $this->manager();
        $analyst = $this->analystFor($manager);
        $client = $this->clientFor($analyst);

        $this->actingAs($client)->post(route('wallets.store'), [
            'name' => 'Aposentadoria',
            'objective_text' => 'Longo prazo',
        ])->assertRedirect(route('wallets.index'));

        $wallet = Wallet::query()->where('user_id', $client->getKey())->firstOrFail();

        $this->actingAs($client)->put(route('wallets.update', $wallet), [
            'name' => 'Aposentadoria 2',
            'objective_text' => 'Atualizado',
        ])->assertRedirect(route('wallets.index'));

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->getKey(),
            'name' => 'Aposentadoria 2',
        ]);

        $this->actingAs($client)->delete(route('wallets.destroy', $wallet))
            ->assertRedirect(route('wallets.index'));

        $this->assertDatabaseMissing('wallets', ['id' => $wallet->getKey()]);
    }

    public function test_client_cannot_edit_or_delete_others_wallet(): void
    {
        $manager = $this->manager();
        $analyst = $this->analystFor($manager);
        $client = $this->clientFor($analyst);
        $otherClient = $this->clientFor($analyst);
        $wallet = Wallet::factory()->for($otherClient)->create();

        $this->actingAs($client)->get(route('wallets.edit', $wallet))->assertForbidden();
        $this->actingAs($client)->put(route('wallets.update', $wallet), ['name' => 'x'])->assertForbidden();
        $this->actingAs($client)->delete(route('wallets.destroy', $wallet))->assertForbidden();
    }

    public function test_analyst_and_manager_cannot_access_client_wallet_crud(): void
    {
        $manager = $this->manager();
        $analyst = $this->analystFor($manager);
        $client = $this->clientFor($analyst);
        $wallet = Wallet::factory()->for($client)->create();

        $this->actingAs($analyst)->get(route('wallets.index'))->assertForbidden();
        $this->actingAs($analyst)->get(route('wallets.create'))->assertForbidden();
        $this->actingAs($analyst)->put(route('wallets.update', $wallet), ['name' => 'x'])->assertForbidden();

        $this->actingAs($manager)->get(route('wallets.index'))->assertForbidden();
    }

    public function test_analyst_can_view_a_responsible_clients_wallets(): void
    {
        $manager = $this->manager();
        $analyst = $this->analystFor($manager);
        $client = $this->clientFor($analyst);
        $wallet = Wallet::factory()->for($client)->create();

        $response = $this->actingAs($analyst)->get(route('clients.wallets.index', $client));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('wallets/index')
            ->where('mode', 'view')
            ->where('client.id', $client->getKey())
            ->has('wallets', 1)
            ->where('wallets.0.id', $wallet->getKey()));
    }

    public function test_analyst_cannot_view_a_client_they_do_not_manage(): void
    {
        $manager = $this->manager();
        $analyst = $this->analystFor($manager);
        $foreignAnalyst = $this->analystFor($manager);
        $foreignClient = $this->clientFor($foreignAnalyst);

        $this->actingAs($analyst)
            ->get(route('clients.wallets.index', $foreignClient))
            ->assertForbidden();
    }

    public function test_manager_cannot_view_client_wallets(): void
    {
        $manager = $this->manager();
        $analyst = $this->analystFor($manager);
        $client = $this->clientFor($analyst);

        $this->actingAs($manager)
            ->get(route('clients.wallets.index', $client))
            ->assertForbidden();
    }
}
