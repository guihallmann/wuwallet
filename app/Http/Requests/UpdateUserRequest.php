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
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $model = $this->route('user');
        $userId = $model instanceof User ? $model->getKey() : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'tax_id' => ['required', 'string', 'size:11', 'regex:/^[0-9]{11}$/', Rule::unique('users', 'tax_id')->ignore($userId)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'string', Password::default(), 'confirmed'],
            'active' => ['boolean'],
        ];
    }
}
