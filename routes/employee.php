<?php

use App\Http\Controllers\Employee\SopPortalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:employee,admin'])
    ->prefix('employee')
    ->name('employee.')
    ->group(function () {
        Route::get('/dashboard', [SopPortalController::class, 'dashboard'])->name('dashboard');

        Route::get('/ai-search', [SopPortalController::class, 'index'])->name('ai.index');
        Route::post('/ai-search', [SopPortalController::class, 'ask'])->name('ai.ask');
        Route::redirect('/sop', '/employee/dashboard')->name('sop.index');
        Route::get('/sop/{sop}', [SopPortalController::class, 'show'])->name('sop.show');
        Route::post('/sop/{sop}/open', [SopPortalController::class, 'open'])->name('sop.open');
        Route::post('/sop/{sop}/download', [SopPortalController::class, 'download'])->name('sop.download');
        Route::post('/sop/{sop}/like', [SopPortalController::class, 'like'])->name('sop.like');
        Route::delete('/sop/{sop}/like', [SopPortalController::class, 'unlike'])->name('sop.unlike');
        Route::post('/sop/{sop}/comment', [SopPortalController::class, 'comment'])->name('sop.comment');
    });
