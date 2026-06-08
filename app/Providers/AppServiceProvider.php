<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Project;
use App\Ssr\SSGGateway;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Sleep;
use Inertia\Ssr\Gateway;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(Gateway::class, SSGGateway::class);
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

        Date::use(CarbonImmutable::class);
        Vite::useAggressivePrefetching();
        Model::automaticallyEagerLoadRelationships();
        Model::shouldBeStrict();
        Model::unguard();

        if (app()->runningUnitTests()) {
            Http::preventStrayRequests();
            Sleep::fake();
        }

        if (app()->isProduction()) {
            URL::forceScheme('https');
            DB::prohibitDestructiveCommands();
        }
    }
}
