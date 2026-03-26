<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

Route::middleware(['web', \Filament\Http\Middleware\Authenticate::class])
    ->post('/api/clients', [\App\Http\Controllers\Api\ClientController::class, 'store']);
