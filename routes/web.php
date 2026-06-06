<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectInvoiceController;
use App\Http\Controllers\ShowDashboardHandler;
use App\Http\Controllers\ShowSharedHandler;
use App\Http\Controllers\ShowTimesheetHandler;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureUserOwnsResource;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'show'])->name('login');

    if (config('auth.enabled')) {
        Route::get('login/redirect', [AuthController::class, 'redirect'])->name('login.redirect');
        Route::get('login/callback', [AuthController::class, 'callback'])->name('login.callback');
    } else {
        Route::post('login/disabled', [AuthController::class, 'disabled'])->name('login.disabled');
    }
});

Route::middleware(['auth', EnsureUserOwnsResource::class])->group(function () {
    Route::get('logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', ShowDashboardHandler::class)->name('dashboard');

    Route::get('profile', [UserController::class, 'edit'])->name('profile');
    Route::put('profile', [UserController::class, 'update'])->name('profile.update');
    Route::delete('profile', [UserController::class, 'destroy'])->name('profile.destroy');

    // Clients
    Route::resource('clients', ClientController::class)->only(['edit', 'update', 'destroy'])->withTrashed(['destroy']);
    Route::post('clients/{client}/restore', [ClientController::class, 'restore'])->name('clients.restore')->withTrashed();
    Route::post('clients/{client}/share', [ClientController::class, 'storeShare'])->name('clients.share.store');
    Route::delete('clients/{client}/share', [ClientController::class, 'destroyShare'])->name('clients.share.destroy');
    Route::get('clients/{client}/billing', [ClientController::class, 'showBilling'])->name('clients.billing.show')->withTrashed();

    // Projects
    Route::resource('projects', ProjectController::class)->except(['show'])->withTrashed(['edit', 'update', 'destroy']);
    Route::post('projects/{project}/restore', [ProjectController::class, 'restore'])->name('projects.restore')->withTrashed();
    Route::post('projects/{project}/duplicate', [ProjectController::class, 'duplicate'])->name('projects.duplicate')->withTrashed();
    Route::patch('projects/{project}/entries', [ProjectController::class, 'syncEntries'])->name('projects.entries.sync');

    // Project invoices
    Route::post('projects/{project}/invoices', [ProjectInvoiceController::class, 'store'])->name('invoices.store')->withTrashed();
    Route::put('invoices/{invoice}', [ProjectInvoiceController::class, 'update'])->name('invoices.update');
    Route::delete('invoices/{invoice}', [ProjectInvoiceController::class, 'destroy'])->name('invoices.destroy');

    // Pages
    Route::get('timesheet', ShowTimesheetHandler::class)->name('timesheet');
});

Route::get('shares/{token}', ShowSharedHandler::class)->name('shares.show');

Route::get('up', fn () => response()->json(['updated_at' => config('app.updated_at')]))->name('up');
