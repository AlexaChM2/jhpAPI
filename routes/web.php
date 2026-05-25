<?php

use App\Http\Controllers\API\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('api')->group(function () {
    Route::post('password/forgot', [PasswordResetController::class, 'requestToken']);
    Route::post('password/validate-token', [PasswordResetController::class, 'validateToken']);
    Route::post('password/reset', [PasswordResetController::class, 'reset']);
    Route::post('auth/password-reset/request', [PasswordResetController::class, 'requestToken']);
    Route::post('auth/password-reset/validate', [PasswordResetController::class, 'validateToken']);
    Route::post('auth/password-reset/reset', [PasswordResetController::class, 'reset']);
});
