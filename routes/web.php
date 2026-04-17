<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ActivitiesController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ExportsController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SettingsController;

Route::prefix('/dashboard')->name('dashboard')->middleware('auth')->group(function () {
    Route::view('/', 'dashboard.index');

    Route::prefix('/settings')->name('.settings')->group(function () {
        Route::get('/', [SettingsController::class, 'edit']);
        Route::put('/', [SettingsController::class, 'update']);
    });

    Route::name('.activity_reports.')->group(function () {
        Route::resource(
            'reports',
            ActivitiesController::class
        )
            ->names('')
            ->except([
                'edit',
                'show',
                'create'
            ]);

        Route::get('/reports/{report}', [ActivitiesController::class, 'get'])->name('get');
    });

    Route::prefix('/clients')->name('.clients')->group(function () {
        Route::get('/', [ClientController::class, 'index']);
        Route::post('/', [ClientController::class, 'store'])->name('.store');
        Route::put('/{client}', [ClientController::class, 'update'])->name('.update');
        Route::delete('/{client}', [ClientController::class, 'destroy'])->name('.destroy');
    });

    Route::prefix('/projects')->name('.projects')->group(function () {
        Route::get('/', [ProjectController::class, 'index']);
        Route::post('/', [ProjectController::class, 'store'])->name('.store');
        Route::put('/{project}', [ProjectController::class, 'update'])->name('.update');
        Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('.destroy');
    });

    Route::prefix('/exports')->name('.exports.')->group(function () {
        Route::get('/csv', [ExportsController::class, 'exportCsv'])->name('csv');
        Route::resource('/', ExportsController::class);
    });
});

Route::prefix('/login')->name('login')->middleware('guest')->group(function () {
    Route::view('/', 'auth.login');
    Route::post('/', [AuthController::class, 'login']);
});
Route::prefix('/register')->name('register')->middleware('guest')->group(function () {
    Route::view('/', 'auth.register');
    Route::post('/', [AuthController::class, 'register']);
});
Route::prefix("/me")->name('account')->middleware('auth')->group(function () {
    Route::delete('/', [AccountController::class, 'delete'])->name('.delete');
    Route::get('/logout', [AuthController::class, 'logout']);
});
