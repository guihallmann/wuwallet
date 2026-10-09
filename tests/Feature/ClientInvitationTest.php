<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\ClientInvitationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ClientInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_analyst_can_access_invitation_page(): void
    {
        $analyst = User::factory()->create([
            'role' => UserRole::ANALYST,
            'manager_id' => null,
        ]);

        $response = $this
            ->actingAs($analyst)
            ->get(route('analyst.invites.create'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('analyst/invites/create'));
    }

    public function test_analyst_can_send_invitation_email(): void
    {
        Mail::fake();

        $analyst = User::factory()->create([
            'role' => UserRole::ANALYST,
            'manager_id' => null,
        ]);

        $response = $this
            ->actingAs($analyst)
            ->post(route('analyst.invites.store'), [
                'email' => 'client@example.com',
            ]);

        $response->assertSessionHas('status');

        Mail::assertSent(ClientInvitationMail::class, function (ClientInvitationMail $mail) use ($analyst) {
            $this->assertSame('client@example.com', $mail->email);
            $this->assertStringContainsString('/register/invite/' . $analyst->getKey(), $mail->inviteUrl);

            return true;
        });
    }

    public function test_invitation_email_uses_the_markdown_template(): void
    {
        $mail = new ClientInvitationMail(
            'client@example.com',
            'https://example.com/register/invite/test?signature=valid',
        );

        $rendered = $mail->render();

        $this->assertStringContainsString('Você foi convidado', $rendered);
        $this->assertStringContainsString('Finalizar cadastro', $rendered);
        $this->assertStringContainsString('client@example.com', $rendered);
    }

    public function test_client_cannot_access_tampered_invitation_url(): void
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

        $parsed = parse_url($url);
        parse_str($parsed['query'] ?? '', $query);
        $query['email'] = 'other@example.com';
        $parsed['query'] = http_build_query($query);
        $tampered = ($parsed['scheme'] ?? 'http') . '://' . ($parsed['host'] ?? 'localhost') . ($parsed['path'] ?? '') . '?' . $parsed['query'];

        $response = $this->get($tampered);

        $response->assertForbidden();
    }

    public function test_client_cannot_access_expired_invitation_url(): void
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

        $this->travel(49)->hours();

        $response = $this->get($url);

        $response->assertForbidden();
    }

    public function test_client_can_register_via_valid_invitation_url(): void
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

        $this->post($url, [
            'nome' => 'Client User',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        $user = User::query()->where('email', 'client@example.com')->firstOrFail();

        $this->assertSame(UserRole::CLIENT->value, $user->role->value);
        $this->assertSame($analyst->getKey(), $user->manager_id);
        $this->assertNotSame('Password@123', $user->password);
    }

    public function test_gestor_and_cliente_cannot_send_invitations(): void
    {
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'manager_id' => null,
        ]);

        $client = User::factory()->create([
            'role' => UserRole::CLIENT,
            'manager_id' => $manager->getKey(),
        ]);

        $this->actingAs($manager)
            ->post(route('analyst.invites.store'), ['email' => 'manager@example.com'])
            ->assertForbidden();

        $this->actingAs($client)
            ->post(route('analyst.invites.store'), ['email' => 'client2@example.com'])
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('analyst.invites.create'))
            ->assertForbidden();

        $this->actingAs($client)
            ->get(route('analyst.invites.create'))
            ->assertForbidden();
    }
}
