<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
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

    public function test_manager_index_lists_only_their_own_analysts(): void
    {
        $manager = $this->manager();
        $ownAnalyst = $this->analystFor($manager);

        $otherManager = $this->manager();
        $foreignAnalyst = $this->analystFor($otherManager);

        $response = $this->actingAs($manager)->get(route('users.index'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('users/index')
            ->where('managedRole', 'analyst')
            ->has('users', 1)
            ->where('users.0.id', $ownAnalyst->getKey()));

        $this->assertStringNotContainsString($otherManager->getKey(), $response->getContent() ?: '');
        $this->assertStringNotContainsString($foreignAnalyst->getKey(), $response->getContent() ?: '');
    }

    public function test_analyst_index_lists_only_their_own_clients(): void
    {
        $manager = $this->manager();
        $analyst = $this->analystFor($manager);
        $ownClient = $this->clientFor($analyst);

        $otherAnalyst = $this->analystFor($manager);
        $this->clientFor($otherAnalyst);

        $response = $this->actingAs($analyst)->get(route('users.index'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('users/index')
            ->where('managedRole', 'client')
            ->has('users', 1)
            ->where('users.0.id', $ownClient->getKey()));
    }

    public function test_only_manager_can_access_user_creation_page(): void
    {
        $manager = $this->manager();
        $analyst = $this->analystFor($manager);

        $this->actingAs($manager)
            ->get(route('users.create'))
            ->assertOk()
            ->assertInertia(fn($page) => $page
                ->component('users/create')
                ->where('managedRole', 'analyst'));

        $this->actingAs($analyst)
            ->get(route('users.create'))
            ->assertForbidden();
    }

    public function test_client_is_unauthorized_on_every_user_route(): void
    {
        $manager = $this->manager();
        $analyst = $this->analystFor($manager);
        $client = $this->clientFor($analyst);
        $target = $this->clientFor($analyst);

        $this->actingAs($client)->get(route('users.index'))->assertUnauthorized();
        $this->actingAs($client)->get(route('users.create'))->assertUnauthorized();
        $this->actingAs($client)->post(route('users.store'), [])->assertUnauthorized();
        $this->actingAs($client)->get(route('users.edit', $target))->assertUnauthorized();
        $this->actingAs($client)->put(route('users.update', $target), [])->assertUnauthorized();
    }

    public function test_manager_can_create_an_analyst(): void
    {
        $manager = $this->manager();

        $response = $this->actingAs($manager)->post(route('users.store'), [
            'name' => 'New Analyst',
            'email' => 'analyst@example.com',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
            'active' => '1',
        ]);

        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'analyst@example.com',
            'manager_id' => $manager->getKey(),
            'role' => UserRole::ANALYST->value,
            'active' => true,
        ]);
    }

    public function test_analyst_can_create_a_client(): void
    {
        $manager = $this->manager();
        $analyst = $this->analystFor($manager);

        $response = $this->actingAs($analyst)->post(route('users.store'), [
            'name' => 'New Client',
            'email' => 'client@example.com',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
            'active' => '1',
        ]);

        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'client@example.com',
            'manager_id' => $analyst->getKey(),
            'role' => UserRole::CLIENT->value,
        ]);
    }

    public function test_store_rejects_duplicate_email(): void
    {
        $manager = $this->manager();
        $existing = $this->analystFor($manager);

        $response = $this->actingAs($manager)->post(route('users.store'), [
            'name' => 'Duplicate',
            'email' => $existing->email,
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_manager_can_update_and_disable_an_analyst(): void
    {
        $manager = $this->manager();
        $analyst = $this->analystFor($manager);

        $response = $this->actingAs($manager)->put(route('users.update', $analyst), [
            'name' => 'Updated Name',
            'email' => $analyst->email,
            'active' => '0',
        ]);

        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $analyst->getKey(),
            'name' => 'Updated Name',
            'active' => false,
        ]);
    }

    public function test_manager_cannot_edit_an_analyst_they_do_not_manage(): void
    {
        $manager = $this->manager();
        $foreignAnalyst = $this->analystFor($this->manager());

        $this->actingAs($manager)
            ->get(route('users.edit', $foreignAnalyst))
            ->assertForbidden();

        $this->actingAs($manager)
            ->put(route('users.update', $foreignAnalyst), [
                'name' => 'Hijack',
                'email' => $foreignAnalyst->email,
                'active' => '1',
            ])
            ->assertForbidden();
    }

    public function test_analyst_cannot_edit_a_client_they_do_not_manage(): void
    {
        $manager = $this->manager();
        $analyst = $this->analystFor($manager);
        $foreignClient = $this->clientFor($this->analystFor($manager));

        $this->actingAs($analyst)
            ->get(route('users.edit', $foreignClient))
            ->assertForbidden();
    }
}
