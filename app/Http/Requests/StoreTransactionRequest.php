<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TransactionType;
use App\Models\WalletAsset;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isClient() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'wallet_id' => [
                'required',
                'uuid',
                Rule::exists('wallets', 'id')->where('user_id', $this->user()?->getKey()),
            ],
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'transaction_type' => ['required', Rule::enum(TransactionType::class)],
            'quantity' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'transaction_date' => ['required', 'date_format:Y-m-d', 'date_equals:today'],
            'target_percentage' => ['nullable', 'numeric', 'between:0.01,100'],
        ];
    }

    /**
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['wallet_id', 'asset_id', 'transaction_type'])) {
                    return;
                }

                if ($this->input('transaction_type') !== TransactionType::BUY->value) {
                    return;
                }

                $hasPosition = WalletAsset::query()
                    ->where('wallet_id', $this->input('wallet_id'))
                    ->where('asset_id', $this->integer('asset_id'))
                    ->exists();

                if (! $hasPosition && $this->input('target_percentage') === null) {
                    $validator->errors()->add(
                        'target_percentage',
                        __('Informe a meta de alocação para adicionar um novo ativo à carteira.'),
                    );
                }
            },
        ];
    }
}
