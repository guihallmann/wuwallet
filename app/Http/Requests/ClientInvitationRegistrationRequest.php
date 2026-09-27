<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ClientInvitationRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $email = (string) ($this->query('email') ?? $this->input('email', ''));

        if ($email !== '') {
            $this->merge(['email' => $email]);
        }

        if ($this->has('cpf')) {
            $this->merge([
                'cpf' => preg_replace('/\D+/', '', (string) $this->input('cpf')),
            ]);
        }
    }

    /**
     * @return array<string, array<int, mixed|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'cpf' => ['required', 'string', 'size:11', 'cpf', 'unique:users,cpf'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ];
    }
}
