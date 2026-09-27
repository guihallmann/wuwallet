<?php

declare(strict_types=1);

use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('wallets', WalletController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    Route::get('clients/{client}/wallets', [WalletController::class, 'clientWallets'])
        ->name('clients.wallets.index');
});
