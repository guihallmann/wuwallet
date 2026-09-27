<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Wallet;
use Illuminate\Foundation\Http\FormRequest;

class StoreWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Wallet::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'objective_text' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
