<?php

namespace Rennokki\Plans\Test;

use Orchestra\Testbench\TestCase as Orchestra;

use Rennokki\Plans\Test\Models\User;
use Rennokki\Plans\Models\PlanFeatureModel;
use Rennokki\Plans\Models\PlanModel;
use Rennokki\Plans\Models\PlanSubscriptionModel;

abstract class TestCase extends Orchestra
{
    public function setUp()
    {
        parent::setUp();

        $this->resetDatabase();

        $this->loadLaravelMigrations(['--database' => 'sqlite']);
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->withFactories(__DIR__.'/../database/factories');

        $this->artisan('migrate', ['--database' => 'sqlite']);
    }

    protected function getPackageProviders($app)
    {
        return [
            \Rennokki\Plans\PlansServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => __DIR__.'/database.sqlite',
            'prefix' => '',
        ]);
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('app.key', 'wslxrEFGWY6GfGhvN9L3wH3KSRJQQpBD');
        $app['config']->set('plans.models.plan', PlanModel::class);
        $app['config']->set('plans.models.feature', PlanFeatureModel::class);
        $app['config']->set('plans.models.subscription', PlanSubscriptionModel::class);
    }

    protected function resetDatabase()
    {
        file_put_contents(__DIR__.'/database.sqlite', null);
    }
}
