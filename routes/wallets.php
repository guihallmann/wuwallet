<?php

declare(strict_types=1);

use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('transactions', [TransactionController::class, 'create'])
        ->name('transactions.index');
    Route::get('transactions/create', [TransactionController::class, 'create'])
        ->name('transactions.create');
    Route::post('transactions', [TransactionController::class, 'store'])
        ->name('transactions.store');

    Route::resource('wallets', WalletController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    Route::get('clients/{client}/wallets', [WalletController::class, 'clientWallets'])
        ->name('clients.wallets.index');
});
