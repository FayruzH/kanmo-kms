<?php

use App\Http\Controllers\Employee\ChatbotFeedbackController;
use App\Http\Controllers\Employee\SopPortalController;
use Illuminate\Support\Facades\Route;

Route::prefix('employee')
    ->name('employee.')
    ->middleware('employee.sso')
    ->group(function () {
        Route::get('/dashboard', [SopPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/dashboard/stats-detail', [SopPortalController::class, 'statDetails'])->name('dashboard.stats-detail');
        Route::view('/chatbot', 'employee.chatbot')->name('chatbot');
        Route::get('/feedback', [ChatbotFeedbackController::class, 'create'])->name('feedback.create');
        Route::post('/feedback', [ChatbotFeedbackController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('feedback.store');

        Route::redirect('/sop', '/employee/dashboard')->name('sop.index');
        Route::get('/sop/{sop}', [SopPortalController::class, 'show'])->name('sop.show');
        Route::post('/sop/{sop}/open', [SopPortalController::class, 'open'])->name('sop.open');
        Route::post('/sop/{sop}/download', [SopPortalController::class, 'download'])->name('sop.download');
        Route::middleware('throttle:30,1')->group(function () {
            Route::post('/sop/{sop}/like', [SopPortalController::class, 'like'])->name('sop.like');
            Route::delete('/sop/{sop}/like', [SopPortalController::class, 'unlike'])->name('sop.unlike');
            Route::post('/sop/{sop}/comment', [SopPortalController::class, 'comment'])->name('sop.comment');
        });
    });
