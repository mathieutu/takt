<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportsController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReceivedShareController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\SharedController;
use App\Http\Controllers\SyncProjectEntriesHandler;
use App\Http\Controllers\TimesheetHandler;
use App\Http\Controllers\UserController;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'show'])->name('login');

    if (config('auth.enabled')) {
        Route::get('login/redirect', [AuthController::class, 'redirect'])->name('login.redirect');
        Route::get('login/callback', [AuthController::class, 'callback'])->name('login.callback');
    } else {
        Route::post('login/disabled', [AuthController::class, 'disabled'])->name('login.disabled');
    }
});

Route::middleware('auth')->group(function () {
    Route::get('logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('profile', [UserController::class, 'edit'])->name('profile');
    Route::put('profile', [UserController::class, 'update'])->name('profile.update');
    Route::delete('profile', [UserController::class, 'destroy'])->name('profile.destroy');

    // Clients
    Route::resource('clients', ClientController::class)->only(['edit', 'update', 'destroy'])->withTrashed(['destroy']);
    Route::post('clients/{client}/restore', [ClientController::class, 'restore'])->name('clients.restore')->withTrashed();
    Route::post('clients/{client}/share', [ShareController::class, 'storeClient'])->name('clients.share.store');
    Route::delete('clients/{client}/share', [ShareController::class, 'destroyClient'])->name('clients.share.destroy');

    // Projects
    Route::resource('projects', ProjectController::class)->except(['show'])->withTrashed(['destroy']);
    Route::post('projects/{project}/restore', [ProjectController::class, 'restore'])->name('projects.restore')->withTrashed();
    Route::post('projects/{project}/duplicate', [ProjectController::class, 'duplicate'])->name('projects.duplicate')->withTrashed();
    Route::post('projects/{project}/share', [ShareController::class, 'storeProject'])->name('projects.share.store');
    Route::delete('projects/{project}/share', [ShareController::class, 'destroyProject'])->name('projects.share.destroy');
    Route::get('clients/{client}/billing', [BillingController::class, 'showClient'])->name('clients.billing.show');
    Route::get('projects/{project}/billing', [BillingController::class, 'show'])->name('projects.billing.show');
    Route::post('projects/{project}/billing', [BillingController::class, 'store'])->name('projects.billing.store');

    // Timesheet entries
    Route::patch('projects/{project}/entries', SyncProjectEntriesHandler::class)
        ->name('projects.entries.sync');

    // Invoices
    Route::put('invoices/{invoice}', [BillingController::class, 'update'])->name('invoices.update');
    Route::delete('invoices/{invoice}', [BillingController::class, 'destroy'])->name('invoices.destroy');

    // Received shares
    Route::delete('received-shares/{receivedShare}', [ReceivedShareController::class, 'destroy'])->name('received-shares.destroy');

    // Pages
    Route::get('timesheet', TimesheetHandler::class)->name('timesheet');

    Route::prefix('exports')->name('exports.')->group(function () {
        Route::get('/', [ExportsController::class, 'index'])->name('index');
        Route::get('csv', [ExportsController::class, 'exportCsv'])->name('csv');
        Route::get('xlsx', [ExportsController::class, 'exportXlsx'])->name('xlsx');
    });
});

Route::get('shares/{share}', SharedController::class)->name('shares.show');
