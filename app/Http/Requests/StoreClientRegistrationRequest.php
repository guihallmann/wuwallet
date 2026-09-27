<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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

        if ($this->has('cpf')) {
            $this->merge([
                'cpf' => preg_replace('/\D+/', '', (string) $this->input('cpf')),
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
            'cpf' => ['required', 'string', 'size:11', 'cpf', 'unique:users,cpf'],
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
            'cpf.required' => 'O campo CPF é obrigatório.',
            'cpf.string' => 'O campo CPF deve conter apenas números.',
            'cpf.size' => 'O CPF deve conter 11 dígitos.',
            'cpf.cpf' => 'O CPF informado não é válido.',
            'cpf.unique' => 'Este CPF já está em uso.',
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
            'cpf' => 'CPF',
            'password' => 'senha',
        ];
    }
}
