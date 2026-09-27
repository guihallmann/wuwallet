<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiveUserMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabled_user_is_logged_out_and_redirected_to_login(): void
    {
        $user = User::factory()->inactive()->create([
            'role' => UserRole::MANAGER,
            'manager_id' => null,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
        $this->assertGuest();
    }

    public function test_active_user_can_reach_authenticated_routes(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::MANAGER,
            'manager_id' => null,
        ]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }
}
