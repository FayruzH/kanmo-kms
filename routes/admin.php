<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\SopManagementController;

Route::middleware(['auth','role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/dashboard', fn() => view('admin.dashboard'))->name('dashboard');

        Route::get('/sop', [SopManagementController::class, 'index'])->name('sop.index');
        Route::get('/sop/create', [SopManagementController::class, 'create'])->name('sop.create');
        Route::post('/sop', [SopManagementController::class, 'store'])->name('sop.store');
        Route::get('/sop/{sop}/edit', [SopManagementController::class, 'edit'])->name('sop.edit');
        Route::put('/sop/{sop}', [SopManagementController::class, 'update'])->name('sop.update');
    });
