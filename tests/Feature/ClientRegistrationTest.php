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
            'cpf' => '12345678909',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', 'Cadastro realizado com sucesso. Faça seu login.');

        $user = User::query()->where('email', 'client@example.com')->firstOrFail();

        $this->assertSame('Maria Silva', $user->name);
        $this->assertSame('12345678909', $user->tax_id);
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
            'tax_id' => '12345678909',
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
            'cpf' => $existing->tax_id,
            'password' => '123',
            'password_confirmation' => '456',
        ]);

        $response->assertSessionHasErrors(['nome', 'cpf', 'password']);
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
