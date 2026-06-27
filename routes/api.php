<?php

use App\Http\Controllers\ProjectController;
use App\Http\Middleware\EnsureUserOwnsResource;

Route::middleware(['auth:api', EnsureUserOwnsResource::class])->group(function (): void {
    Route::patch('projects/{project}/entries', [ProjectController::class, 'syncEntries'])
        ->name('api.projects.entries.sync');
});
