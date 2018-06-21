<?php

namespace Rennokki\Plans;

use Illuminate\Support\ServiceProvider;

class PlansServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/plans.php' => config_path('plans.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../database/migrations/2018_06_07_123211_plans.php' => database_path('migrations/2018_06_07_123211_plans.php'),
        ], 'migration');
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
