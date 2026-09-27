<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $managerEmail = (string) config('app.manager_master_email');

        if (! DB::table('users')->where('email', $managerEmail)->exists()) {
            DB::table('users')->insert([
                'id' => (string) Str::uuid(),
                'role' => UserRole::MANAGER->value,
                'name' => config('app.manager_master_name'),
                'tax_id' => config('app.manager_master_tax_id'),
                'password' => Hash::make((string) config('app.manager_master_password')),
                'email' => $managerEmail,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $analystEmail = 'analyst@example.com';

        if (! DB::table('users')->where('email', $analystEmail)->exists()) {
            DB::table('users')->insert([
                'id' => (string) Str::uuid(),
                'manager_id' => DB::table('users')->where('email', $managerEmail)->value('id'),
                'role' => UserRole::ANALYST->value,
                'name' => 'Analyst Seed User',
                'tax_id' => '12345678909',
                'password' => Hash::make('password'),
                'email' => $analystEmail,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
