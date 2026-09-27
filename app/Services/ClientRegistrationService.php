<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;

class ClientRegistrationService
{
    /**
     * @param  array{nome: string, cpf: string, password: string}  $data
     */
    public function registerClient(array $data, string $analystId, string $email): User
    {
        $user = User::query()->create([
            'manager_id' => $analystId,
            'role' => UserRole::CLIENT,
            'name' => (string) $data['nome'],
            'tax_id' => (string) $data['cpf'],
            'email' => $email,
            'password' => Hash::make((string) $data['password']),
            'email_verified_at' => now(),
        ]);

        event(new Registered($user));

        return $user;
    }
}
