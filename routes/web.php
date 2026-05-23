<?php

use App\Http\Controllers\ActivitiesController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportsController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ShareController;
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

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('profile', [UserController::class, 'edit'])->name('profile');
    Route::put('profile', [UserController::class, 'update'])->name('profile.update');
    Route::delete('profile', [UserController::class, 'destroy'])->name('profile.destroy');

    // Clients
    Route::apiResource('clients', ClientController::class)->only(['store', 'update', 'destroy']);

    // Projects + sous-ressources
    Route::apiResource('projects', ProjectController::class)->except(['show']);
    Route::apiResource('projects.reports', ActivitiesController::class)
        ->only(['store', 'update', 'destroy'])
        ->shallow();
    Route::get('projects/{project}/reports', [ActivitiesController::class, 'projectReports'])->name('projects.reports.index');
    Route::post('projects/{project}/billing', [TrackingController::class, 'storeBilling'])->name('projects.billing.store');
    Route::put('projects/{project}/budget', [TrackingController::class, 'updateBudget'])->name('projects.budget');

    Route::post('projects/{project}/share', [ShareController::class, 'generate'])->name('projects.share');
    Route::delete('projects/{project}/share', [ShareController::class, 'revoke'])->name('projects.share.revoke');

    // Billing (shallow - pas besoin du project pour update)
    Route::put('billing/{entry}', [TrackingController::class, 'updateBilling'])->name('billing.update');

    // Pages
    Route::get('reports', [ActivitiesController::class, 'index'])->name('reports.index');

    Route::get('tracking', [TrackingController::class, 'index'])->name('tracking.index');
    Route::prefix('exports')->name('exports.')->group(function () {
        Route::get('/', [ExportsController::class, 'index'])->name('index');
        Route::get('csv', [ExportsController::class, 'exportCsv'])->name('csv');
        Route::get('xlsx', [ExportsController::class, 'exportXlsx'])->name('xlsx');
    });
});

Route::get('share/{share}', [ShareController::class, 'apply'])->name('share.apply');
