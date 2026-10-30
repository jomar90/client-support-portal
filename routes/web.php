<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ResetPasswordController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('guest');

Route::post('/register', [RegisterController::class, 'store'])
    ->middleware(['guest', 'throttle:6,1']);

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth');

Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('password.email');

Route::post('/reset-password', [ResetPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('password.update');

Route::view('/reset-password/{token}', 'welcome')
    ->middleware('guest')
    ->name('password.reset');

Route::view('/{any?}', 'welcome')
    ->where('any', '.*');
