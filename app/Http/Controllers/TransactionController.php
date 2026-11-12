<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\WalletAsset;
use App\Services\TransactionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
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

        $walletAssets = WalletAsset::query()
            ->where('wallet_id', $wallet->getKey())
            ->whereHas('asset', fn (Builder $query) => $query->tradable())
            ->with('asset:id,ticker,name,current_price')
            ->get();
        $assets = $walletAssets
            ->map(fn (WalletAsset $walletAsset): array => [
                'id' => $walletAsset->asset->id,
                'ticker' => $walletAsset->asset->ticker,
                'name' => $walletAsset->asset->name,
                'current_price' => (float) $walletAsset->asset->current_price,
                'current_quantity' => $walletAsset->current_quantity,
            ])
            ->sortBy('ticker')
            ->values();

        return Inertia::render('transactions/create', [
            'wallet' => [
                'id' => $wallet->id,
                'name' => $wallet->name,
                'sellableAssetIds' => $walletAssets
                    ->filter(fn (WalletAsset $walletAsset): bool => $walletAsset->current_quantity >= 100)
                    ->pluck('asset_id')
                    ->map(fn ($id): int => (int) $id)
                    ->all(),
            ],
            'assets' => $assets,
            'today' => today()->toDateString(),
        ]);
    }

    public function store(
        StoreTransactionRequest $request,
        Wallet $wallet,
        TransactionService $transactions,
    ): RedirectResponse {
        $data = $request->validated();
        $transactions->register(
            $wallet,
            (int) $data['asset_id'],
            TransactionType::from((string) $data['transaction_type']),
            (int) $data['quantity'],
            (string) $data['transaction_date'],
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Movimentação registrada'),
        ]);

        return to_route('wallets.transactions.index', $wallet);
    }
}
