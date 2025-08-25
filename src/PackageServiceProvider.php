<?php

namespace Yeeraf\LaravelSubscription;

use Illuminate\Support\ServiceProvider;

class PackageServiceProvider extends ServiceProvider {
    public function boot() {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'subscription-migrations');
        }
    }

    public function register() {
    }
}