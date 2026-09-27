<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Concerns\PasswordValidationRules;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'active' => $this->boolean('active'),
        ]);

        if ($this->has('cpf')) {
            $this->merge([
                'cpf' => preg_replace('/\D+/', '', (string) $this->input('cpf')),
            ]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $cpfRequired = $this->managedRole() === UserRole::CLIENT;

        return [
            'name' => ['required', 'string', 'max:255'],
            'cpf' => [$cpfRequired ? 'required' : 'nullable', 'string', 'size:11', 'cpf', 'unique:users,cpf'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => $this->passwordRules(),
            'active' => ['boolean'],
        ];
    }

    private function managedRole(): ?UserRole
    {
        $manager = $this->user();

        if (! $manager instanceof User) {
            return null;
        }

        return $manager->isManager() ? UserRole::ANALYST : UserRole::CLIENT;
    }
}
