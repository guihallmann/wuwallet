<?php

declare(strict_types=1);

use App\Http\Controllers\ClientInvitationController;
use App\Http\Controllers\ClientRegistrationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/analyst/invites', [ClientInvitationController::class, 'create'])
        ->name('analyst.invites.create');

    Route::post('/analyst/invites', [ClientInvitationController::class, 'store'])
        ->name('analyst.invites.store');
});

Route::get('/register/invite/{analyst_id}', [ClientRegistrationController::class, 'show'])
    ->middleware('signed')
    ->name('register.invite');

Route::post('/register/invite/{analyst_id}', [ClientRegistrationController::class, 'store'])
    ->middleware('signed')
    ->name('register.invite.store');
