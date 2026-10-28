<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Asset;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\WalletAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Wallet $wallet): Response
    {
        Gate::authorize('viewTransactions', $wallet);

        $transactions = $wallet->transactions()
            ->with('asset:id,ticker,name')
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Transaction $transaction): array => [
                'id' => $transaction->getKey(),
                'ticker' => $transaction->asset->ticker,
                'asset_name' => $transaction->asset->name,
                'transaction_type' => $transaction->transaction_type->value,
                'quantity' => $transaction->quantity,
                'unit_price' => $transaction->unit_price,
                'transaction_date' => $transaction->transaction_date->toDateString(),
            ])
            ->values();

        return Inertia::render('transactions/index', [
            'wallet' => $wallet->only(['id', 'name']),
            'transactions' => $transactions,
        ]);
    }

    public function create(Wallet $wallet): Response
    {
        Gate::authorize('createTransaction', $wallet);

        $wallet->load('assets.asset:id,ticker,name');
        $walletAssets = $wallet->assets;
        $assets = $walletAssets
            ->map(fn (WalletAsset $walletAsset): array => [
                'id' => $walletAsset->asset->id,
                'ticker' => $walletAsset->asset->ticker,
                'name' => $walletAsset->asset->name,
            ])
            ->sortBy('ticker')
            ->values();

        return Inertia::render('transactions/create', [
            'wallet' => [
                'id' => $wallet->id,
                'name' => $wallet->name,
                'sellableAssetIds' => $walletAssets
                    ->filter(fn (WalletAsset $walletAsset): bool => $walletAsset->current_quantity > 0)
                    ->pluck('asset_id')
                    ->map(fn ($id): int => (int) $id)
                    ->all(),
            ],
            'assets' => $assets,
            'today' => today()->toDateString(),
        ]);
    }

    public function store(StoreTransactionRequest $request, Wallet $wallet): RedirectResponse
    {
        $data = $request->validated();
        $quantity = (int) $data['quantity'];
        $type = TransactionType::from($data['transaction_type']);

        DB::transaction(function () use ($data, $quantity, $type, $request, $wallet): void {
            $wallet = $request->user()
                ->wallets()
                ->whereKey($wallet->getKey())
                ->lockForUpdate()
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
                'transaction_date' => Carbon::createFromFormat('Y-m-d', $data['transaction_date'])->startOfDay(),
            ]);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Movimentação registrada'),
        ]);

        return to_route('wallets.transactions.index', $wallet);
    }
}
