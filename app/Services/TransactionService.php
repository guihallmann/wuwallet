<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Asset;
use App\Models\Wallet;
use App\Models\WalletAsset;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function register(
        Wallet $targetWallet,
        int $assetId,
        TransactionType $type,
        int $quantity,
        string $transactionDate,
    ): void {
        DB::transaction(function () use ($assetId, $quantity, $type, $transactionDate, $targetWallet): void {
            $wallet = Wallet::query()
                ->whereKey($targetWallet->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $asset = Asset::query()
                ->tradable()
                ->lockForUpdate()
                ->whereKey($assetId)
                ->first();

            if ($asset === null) {
                throw ValidationException::withMessages([
                    'asset_id' => __('Este ativo está inativo ou não está disponível para movimentações.'),
                ]);
            }

            $walletAsset = WalletAsset::query()
                ->where('wallet_id', $wallet->getKey())
                ->where('asset_id', $asset->getKey())
                ->lockForUpdate()
                ->first();

            if ($walletAsset === null) {
                throw ValidationException::withMessages([
                    'asset_id' => __('Este ativo não está cadastrado nesta carteira.'),
                ]);
            }

            $currentQuantity = $walletAsset->current_quantity;

            if ($type === TransactionType::SELL && $quantity > $currentQuantity) {
                throw ValidationException::withMessages([
                    'quantity' => __('A quantidade da venda supera o saldo disponível na carteira.'),
                ]);
            }

            $newQuantity = $type === TransactionType::BUY
                ? $currentQuantity + $quantity
                : $currentQuantity - $quantity;

            if ($newQuantity > 4294967295) {
                throw ValidationException::withMessages([
                    'quantity' => __('A quantidade informada excede o limite permitido para esta carteira.'),
                ]);
            }

            $walletAsset->current_quantity = $newQuantity;
            $walletAsset->save();

            $wallet->transactions()->create([
                'asset_id' => $asset->getKey(),
                'transaction_type' => $type,
                'quantity' => $quantity,
                'unit_price' => $asset->current_price,
                'transaction_date' => Carbon::createFromFormat('Y-m-d', $transactionDate)->startOfDay(),
            ]);
        });
    }
}
