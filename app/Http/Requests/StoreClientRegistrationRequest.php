<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('nome')) {
            $this->merge([
                'nome' => trim((string) $this->input('nome')),
            ]);
        }
    }

    /**
     * @return array<string, array<int, string|mixed>>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'recovery_email' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'email'),
                Rule::unique('users', 'recovery_email'),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome.required' => 'O campo nome é obrigatório.',
            'nome.string' => 'O campo nome deve conter texto válido.',
            'nome.max' => 'O campo nome deve ter no máximo 255 caracteres.',

            'password.required' => 'O campo senha é obrigatório.',
            'password.string' => 'A senha deve conter texto válido.',
            'password.min' => 'A senha deve ter no mínimo 8 caracteres.',
            'password.confirmed' => 'As senhas não conferem.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nome' => 'nome',
            'recovery_email' => 'email de recuperação',
            'password' => 'senha',
        ];
    }
}
