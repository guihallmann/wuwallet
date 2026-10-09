<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ClientRegistrationService
{
    /**
     * @param  array{nome: string, recovery_email: string, password: string}  $data
     */
    public function registerClient(array $data, string $analystId, string $email): User
    {
        $user = DB::transaction(function () use ($data, $analystId, $email) {
            $user = User::query()->create([
                'manager_id' => $analystId,
                'role' => UserRole::CLIENT,
                'name' => (string) $data['nome'],
                'email' => $email,
                'recovery_email' => $data['recovery_email'],
                'password' => Hash::make((string) $data['password']),
                'email_verified_at' => now(),
            ]);

            return $user;
        });

        event(new Registered($user));

        return $user;
    }
}
