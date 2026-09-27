<?php

declare(strict_types=1);

use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureManagesUsers;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', EnsureManagesUsers::class])->group(function () {
    Route::resource('users', UserController::class)
        ->only(['index', 'create', 'store', 'edit', 'update']);
});
