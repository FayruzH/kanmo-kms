<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\SopManagementController;
use App\Http\Controllers\Admin\SopImportController;
use App\Http\Controllers\Admin\SopExpiredController;
use App\Http\Controllers\Admin\SopAnalyticsController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\AiSearchController;

Route::middleware(['auth','role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/dashboard', [SopManagementController::class, 'index'])->name('dashboard');
        Route::get('/overview', [SopManagementController::class, 'dashboard'])->name('overview');
        Route::get('/ai-search', [AiSearchController::class, 'index'])->name('ai.index');
        Route::post('/ai-search', [AiSearchController::class, 'ask'])->name('ai.ask');

        Route::get('/sop', [SopManagementController::class, 'index'])->name('sop.index');
        Route::get('/sop/create', [SopManagementController::class, 'create'])->name('sop.create');
        Route::post('/sop', [SopManagementController::class, 'store'])->name('sop.store');
        Route::get('/sop/{sop}', [SopManagementController::class, 'show'])->whereNumber('sop')->name('sop.show');
        Route::get('/sop/{sop}/edit', [SopManagementController::class, 'edit'])->whereNumber('sop')->name('sop.edit');
        Route::put('/sop/{sop}', [SopManagementController::class, 'update'])->whereNumber('sop')->name('sop.update');
        Route::delete('/sop/{sop}', [SopManagementController::class, 'destroy'])->whereNumber('sop')->name('sop.destroy');

        Route::get('/sop/import', [SopImportController::class, 'index'])->name('sop.import.index');
        Route::get('/sop/import/template', [SopImportController::class, 'template'])->name('sop.import.template');
        Route::post('/sop/import', [SopImportController::class, 'store'])->name('sop.import.store');

        Route::get('/sop/expired', [SopExpiredController::class, 'index'])->name('sop.expired.index');
        Route::post('/sop/{sop}/remind', [SopExpiredController::class, 'remind'])->whereNumber('sop')->name('sop.expired.remind');
        Route::post('/sop/{sop}/archive', [SopExpiredController::class, 'archive'])->whereNumber('sop')->name('sop.expired.archive');
        Route::get('/sop/expired/export', [SopExpiredController::class, 'export'])->name('sop.expired.export');

        Route::get('/analytics', [SopAnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/analytics/export', [SopAnalyticsController::class, 'export'])->name('analytics.export');

        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

    });
