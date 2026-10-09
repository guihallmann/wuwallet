<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
                Rule::unique('users', 'recovery_email')->ignore($userId),
            ],
            'recovery_email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                'different:email',
                Rule::unique('users', 'email')->ignore($userId),
                Rule::unique('users', 'recovery_email')->ignore($userId),
            ],
            'active' => ['boolean'],
        ];
    }
}
