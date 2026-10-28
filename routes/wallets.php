<?php

declare(strict_types=1);

use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('assets/search', [WalletController::class, 'searchAssets'])
        ->name('assets.search');

    Route::get('transactions', fn() => to_route('wallets.index'))
        ->name('transactions.index');
    Route::get('transactions/create', fn() => to_route('wallets.index'))
        ->name('transactions.create');

    Route::get('wallets/{wallet}/transactions', [TransactionController::class, 'index'])
        ->name('wallets.transactions.index');
    Route::get('wallets/{wallet}/transactions/create', [TransactionController::class, 'create'])
        ->name('wallets.transactions.create');
    Route::post('wallets/{wallet}/transactions', [TransactionController::class, 'store'])
        ->name('wallets.transactions.store');

    Route::resource('wallets', WalletController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

    Route::get('clients/{client}/wallets', [WalletController::class, 'clientWallets'])
        ->name('clients.wallets.index');
});
