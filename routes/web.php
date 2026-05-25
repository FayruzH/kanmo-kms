<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\PortalSsoController;
use App\Http\Controllers\Employee\SopPortalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [SopPortalController::class, 'dashboard'])->name('public.home');
Route::get('/sso/portal-login', [PortalSsoController::class, 'authenticate'])
    ->middleware('throttle:60,1')
    ->name('portal.sso.login');

// Guard against malformed links like "/https://domain/" that may come from
// browser/autocomplete or stale rewrites; always bring user back to public home.
Route::get('/https:/{rest?}', function () {
    return redirect('/employee/dashboard');
})->where('rest', '.*');

Route::get('/http:/{rest?}', function () {
    return redirect('/employee/dashboard');
})->where('rest', '.*');

Route::get('/dashboard', function () {
    if (auth()->check() && auth()->user()->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('employee.dashboard');
})->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/redirect', function () {
    if (auth()->check() && auth()->user()->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('employee.dashboard');
});

require __DIR__.'/auth.php';
