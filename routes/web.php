<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportsController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectShareController;
use App\Http\Controllers\SharedTimesheetController;
use App\Http\Controllers\SyncProjectEntriesHandler;
use App\Http\Controllers\TimesheetHandler;
use App\Http\Controllers\TrackingController;
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

    // Projects
    Route::resource('projects', ProjectController::class)->except(['show'])->withTrashed(['destroy']);
    Route::post('projects/{project}/restore', [ProjectController::class, 'restore'])->name('projects.restore')->withTrashed();

    Route::post('projects/{project}/share', [ProjectShareController::class, 'store'])->name('projects.share.store');
    Route::delete('projects/{project}/share', [ProjectShareController::class, 'destroy'])->name('projects.share.destroy');
    Route::post('projects/{project}/billing', [TrackingController::class, 'storeBilling'])->name('projects.billing.store');
    Route::put('projects/{project}/budget', [TrackingController::class, 'updateBudget'])->name('projects.budget');

    // Timesheet entries
    Route::patch('projects/{project}/entries', SyncProjectEntriesHandler::class)
        ->name('projects.entries.sync');

    // Billing
    Route::put('billing/{entry}', [TrackingController::class, 'updateBilling'])->name('billing.update');

    // Pages
    Route::get('timesheet', TimesheetHandler::class)->name('timesheet');
    Route::get('tracking', [TrackingController::class, 'index'])->name('tracking.index');

    Route::prefix('exports')->name('exports.')->group(function () {
        Route::get('/', [ExportsController::class, 'index'])->name('index');
        Route::get('csv', [ExportsController::class, 'exportCsv'])->name('csv');
        Route::get('xlsx', [ExportsController::class, 'exportXlsx'])->name('xlsx');
    });
});

Route::get('share/{share}', SharedTimesheetController::class)->name('share.apply');
