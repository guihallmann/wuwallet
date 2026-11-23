<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TransactionType;
use App\Models\Asset;
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
            'quantity' => ['required', 'integer', 'min:100', 'multiple_of:100', 'max:4294967295'],
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

                $wallet = $this->route('wallet');

                if (! $wallet instanceof Wallet) {
                    return;
                }

                $hasPosition = WalletAsset::query()
                    ->where('wallet_id', $wallet->getKey())
                    ->where('asset_id', $this->integer('asset_id'))
                    ->exists();

                if (! $hasPosition) {
                    $validator->errors()->add(
                        'asset_id',
                        __('Este ativo não está cadastrado nesta carteira.'),
                    );

                    return;
                }

                $isTradable = Asset::query()
                    ->tradable()
                    ->whereKey($this->integer('asset_id'))
                    ->exists();

                if (! $isTradable) {
                    $validator->errors()->add(
                        'asset_id',
                        __('Este ativo está inativo ou não está disponível para movimentações.'),
                    );
                }
            },
        ];
    }
}
