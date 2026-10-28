<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TransactionType;
use App\Models\Wallet;
use App\Models\WalletAsset;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $wallet = $this->route('wallet');

        return $wallet instanceof Wallet
            && ($this->user()?->can('createTransaction', $wallet) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'transaction_type' => ['required', Rule::enum(TransactionType::class)],
            'quantity' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'transaction_date' => ['required', 'date_format:Y-m-d', 'date_equals:today'],
        ];
    }

    /**
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['asset_id', 'transaction_type'])) {
                    return;
                }

                $hasPosition = WalletAsset::query()
                    ->where('wallet_id', $this->route('wallet')->getKey())
                    ->where('asset_id', $this->integer('asset_id'))
                    ->exists();

                if (! $hasPosition) {
                    $validator->errors()->add(
                        'asset_id',
                        __('Este ativo não está cadastrado nesta carteira.'),
                    );
                }
            },
        ];
    }
}
