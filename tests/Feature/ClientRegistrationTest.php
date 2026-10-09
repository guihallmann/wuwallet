<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ClientRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_renders_correctly_with_signed_url(): void
    {
        $analyst = User::factory()->create([
            'role' => UserRole::ANALYST,
            'manager_id' => null,
        ]);

        $url = URL::temporarySignedRoute(
            'register.invite',
            now()->addHours(48),
            [
                'analyst_id' => $analyst->getKey(),
                'email' => 'client@example.com',
            ],
        );

        $response = $this->get($url);

        $response->assertOk();
        $response->assertSee('client@example.com');
        $response->assertSee('Nome completo');
    }

    public function test_client_can_register_successfully(): void
    {
        $analyst = User::factory()->create([
            'role' => UserRole::ANALYST,
            'manager_id' => null,
        ]);

        $url = URL::temporarySignedRoute(
            'register.invite',
            now()->addHours(48),
            [
                'analyst_id' => $analyst->getKey(),
                'email' => 'client@example.com',
            ],
        );

        $response = $this->from($url)->post($url, [
            'nome' => 'Maria Silva',
            'recovery_email' => 'recovery@email.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', 'Cadastro realizado com sucesso. Faça seu login.');

        $user = User::query()->where('email', 'client@example.com')->firstOrFail();

        $this->assertSame('Maria Silva', $user->name);
        $this->assertSame('recovery@email.com', $user->recovery_email);
        $this->assertSame(UserRole::CLIENT->value, $user->role->value);
        $this->assertSame($analyst->getKey(), $user->manager_id);
        $this->assertNotSame('Password@123', $user->password);
    }

    public function test_registration_fails_with_invalid_data(): void
    {
        $analyst = User::factory()->create([
            'role' => UserRole::ANALYST,
            'manager_id' => null,
        ]);

        $existing = User::factory()->create([
            'role' => UserRole::CLIENT,
        ]);

        $url = URL::temporarySignedRoute(
            'register.invite',
            now()->addHours(48),
            [
                'analyst_id' => $analyst->getKey(),
                'email' => 'newclient@example.com',
            ],
        );

        $response = $this->from($url)->post($url, [
            'nome' => '',
            'password' => '123',
            'password_confirmation' => '456',
        ]);

        $response->assertSessionHasErrors(['nome', 'password']);
    }

    public function test_registration_fails_if_signature_is_invalid(): void
    {
        $analyst = User::factory()->create([
            'role' => UserRole::ANALYST,
            'manager_id' => null,
        ]);

        $url = URL::temporarySignedRoute(
            'register.invite',
            now()->addHours(48),
            [
                'analyst_id' => $analyst->getKey(),
                'email' => 'client@example.com',
            ],
        );

        $tampered = preg_replace('/signature=([^&]+)/', 'signature=invalid', $url, 1);

        $this->get($tampered ?? $url)->assertForbidden();
    }
}
