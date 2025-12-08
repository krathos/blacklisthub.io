<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\BlacklistReport;
use App\Observers\BlacklistReportObserver;

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
        BlacklistReport::observe(BlacklistReportObserver::class);
    }
}
