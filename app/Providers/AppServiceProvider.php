<?php

namespace App\Providers;

use App\Console\Commands\MakeFilamentUser;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        Schema::defaultStringLength(191);

        Relation::enforceMorphMap([
            'projects' => Project::class,
            'clients' => Client::class,
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeFilamentUser::class
            ]);
        }
    }
}
