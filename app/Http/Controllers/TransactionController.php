<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Asset;
use App\Models\Wallet;
use App\Models\WalletAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function create(): Response
    {
        abort_unless(auth()->user()?->isClient(), 403);

        $wallets = auth()->user()
            ->wallets()
            ->with('assets:id,wallet_id,asset_id')
            ->orderBy('name')
            ->get()
            ->map(fn (Wallet $wallet): array => [
                'id' => $wallet->id,
                'name' => $wallet->name,
                'assetIds' => $wallet->assets->pluck('asset_id')->map(fn ($id): int => (int) $id)->all(),
                'sellableAssetIds' => $wallet->assets
                    ->filter(fn (WalletAsset $walletAsset): bool => $walletAsset->current_quantity > 0)
                    ->pluck('asset_id')
                    ->map(fn ($id): int => (int) $id)
                    ->all(),
            ])
            ->values();

        $assets = Asset::query()
            ->orderBy('ticker')
            ->get(['id', 'ticker', 'name'])
            ->map(fn (Asset $asset): array => [
                'id' => $asset->id,
                'ticker' => $asset->ticker,
                'name' => $asset->name,
            ])
            ->values();

        return Inertia::render('transactions/create', [
            'wallets' => $wallets,
            'assets' => $assets,
            'today' => today()->toDateString(),
        ]);
    }

    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $quantity = (int) $data['quantity'];
        $type = TransactionType::from($data['transaction_type']);

        DB::transaction(function () use ($data, $quantity, $type, $request): void {
            $wallet = Wallet::query()
                ->where('user_id', $request->user()->getKey())
                ->lockForUpdate()
                ->whereKey($data['wallet_id'])
                ->firstOrFail();

            $asset = Asset::query()
                ->lockForUpdate()
                ->whereKey($data['asset_id'])
                ->firstOrFail();

            if ((float) $asset->current_price <= 0) {
                throw ValidationException::withMessages([
                    'asset_id' => __('Não há cotação disponível para este ativo.'),
                ]);
            }

            $walletAsset = WalletAsset::query()
                ->where('wallet_id', $wallet->getKey())
                ->where('asset_id', $asset->getKey())
                ->lockForUpdate()
                ->first();

            if ($type === TransactionType::SELL && $walletAsset === null) {
                throw ValidationException::withMessages([
                    'asset_id' => __('Este ativo ainda não possui saldo nesta carteira.'),
                ]);
            }

            $currentQuantity = $walletAsset === null
                ? 0
                : $walletAsset->current_quantity;

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

            if ($walletAsset === null) {
                $walletAsset = new WalletAsset([
                    'asset_id' => $asset->getKey(),
                    'target_percentage' => $data['target_percentage'],
                ]);
                $wallet->assets()->save($walletAsset);
            }

            $walletAsset->current_quantity = $newQuantity;
            $walletAsset->save();

            $wallet->transactions()->create([
                'asset_id' => $asset->getKey(),
                'transaction_type' => $type,
                'quantity' => $quantity,
                'unit_price' => $asset->current_price,
                'transaction_date' => Carbon::createFromFormat('Y-m-d', $data['transaction_date'])->startOfDay(),
            ]);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Movimentação registrada'),
        ]);

        return to_route('wallets.index');
    }
}
