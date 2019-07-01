<?php

namespace Koiiiey\Api;

use \App\Guest;

use Illuminate\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class ApiServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->registerGate();
        return parent::register();
    }

    public function boot()
    {
        $this->registerPolicies();

        $this->publishes([
            __DIR__.'/Models' => app_path()
        ], 'models');

        $this->publishes([
            __DIR__.'/Support' => app_path()
        ], 'support');

        $this->publishes([
            __DIR__.'/Policies' => app_path()
        ], 'policies');

        $this->publishes([
            __DIR__.'/database/migrations/' => database_path('migrations')
        ], 'migrations');

        require __DIR__ . '/Http/routes.php';
        require __DIR__ . '/Http/binds.php';
        require __DIR__ . '/Support/helpers.php';
    }

    protected function registerGate()
    {
        $this->app->singleton(GateContract::class, function ($app) {
            return new Gate($app, function () {
                if (!auth()->check()) {
                    return new Guest();
                }
                return auth()->user();
            });
        });
    }
}