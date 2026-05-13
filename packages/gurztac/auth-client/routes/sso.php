<?php

use Illuminate\Support\Facades\Route;
use Gurztac\AuthClient\Http\Controllers\SsoController;

Route::middleware(['web'])->group(function () {
    Route::get(config('gurztac-auth.sso.login_path'), [SsoController::class, 'login'])
        ->name('gurztac.sso.login');

    Route::get(config('gurztac-auth.sso.callback_path'), [SsoController::class, 'callback'])
        ->name('gurztac.sso.callback');

    Route::match(['get', 'post'], config('gurztac-auth.sso.logout_path'), [SsoController::class, 'logout'])
        ->name('gurztac.sso.logout');
});
