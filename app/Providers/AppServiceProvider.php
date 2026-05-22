<?php

namespace App\Providers;

use App\Models\ActivityTime;
use App\Models\Client;
use App\Models\Project;
use App\Policies\ActivityTimePolicy;
use App\Policies\ClientPolicy;
use App\Policies\ProjectPolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        Relation::enforceMorphMap([
            'projects' => Project::class,
            'clients' => Client::class,
        ]);

        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(ActivityTime::class, ActivityTimePolicy::class);
    }
}
