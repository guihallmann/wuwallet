<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $model = $this->route('user');

        return $model instanceof User
            && ($this->user()?->can('update', $model) ?? false);
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
        $model = $this->route('user');
        $userId = $model instanceof User ? $model->getKey() : null;
        $cpfRequired = $model instanceof User && $model->isClient();

        return [
            'name' => ['required', 'string', 'max:255'],
            'cpf' => [$cpfRequired ? 'required' : 'nullable', 'string', 'size:11', 'cpf', Rule::unique('users', 'cpf')->ignore($userId)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'string', Password::default(), 'confirmed'],
            'active' => ['boolean'],
        ];
    }
}
