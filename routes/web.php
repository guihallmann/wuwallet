<?php

use Illuminate\Support\Facades\Route;

Route::fallback(function () {
    return redirect('/');
});

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__ . '/invites.php';
require __DIR__ . '/settings.php';
require __DIR__ . '/users.php';
require __DIR__ . '/wallets.php';
