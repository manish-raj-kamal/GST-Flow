<?php

namespace App\Providers;

use App\Console\Commands\SyncComplianceUpdates;
use App\Console\Commands\SyncTaxNotifications;
use App\Console\Commands\SyncTaxSlabs;
use App\Console\Commands\SyncHsnCatalog;
use App\Models\PersonalAccessToken;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->commands([
            SyncHsnCatalog::class,
            SyncTaxSlabs::class,
            SyncComplianceUpdates::class,
            SyncTaxNotifications::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }
}
