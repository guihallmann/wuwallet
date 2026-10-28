<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Concerns\WalletAssetValidationRules;
use App\Models\Wallet;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWalletRequest extends FormRequest
{
    use WalletAssetValidationRules;

    public function authorize(): bool
    {
        $wallet = $this->route('wallet');

        return $wallet instanceof Wallet
            && ($this->user()?->can('update', $wallet) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge([
            'name' => ['required', 'string', 'max:255'],
            'objective_text' => ['nullable', 'string', 'max:1000'],
        ], $this->walletAssetRules());
    }
}
