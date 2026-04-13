<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SettingsController;

Route::prefix('/dashboard')->name('dashboard')->middleware('auth')->group(function () {
    Route::view('/', 'dashboard.index');

    Route::prefix('/settings')->name('.settings')->group(function () {
        Route::get('/', [SettingsController::class, 'edit']);
        Route::put('/', [SettingsController::class, 'update']);
    });
    Route::prefix("/activities")->name('.activity')->group(function () {

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
