<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('admin/login', [AuthenticatedSessionController::class, 'create'])
                ->name('admin.login');

    Route::post('admin/login', [AuthenticatedSessionController::class, 'store'])
                ->name('admin.login.store');

    Route::get('login', function () {
        return redirect()->route('admin.login');
    })->name('login');

    Route::post('login', function () {
        return redirect()->route('admin.login');
    })->name('login.store');

    Route::get('register', [RegisteredUserController::class, 'create'])
                ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
                ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
                ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
                ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
                ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', fn () => redirect('/admin/dashboard'))->name('verification.notice');
    Route::get('verify-email/{id}/{hash}', fn () => redirect('/admin/dashboard'))->name('verification.verify');
    Route::post('email/verification-notification', fn () => redirect('/admin/dashboard'))->name('verification.send');
    Route::get('confirm-password', fn () => redirect('/admin/dashboard'))->name('password.confirm');
    Route::post('confirm-password', fn () => redirect('/admin/dashboard'));
    Route::put('password', fn () => redirect('/admin/dashboard'))->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
                ->name('logout');
});
