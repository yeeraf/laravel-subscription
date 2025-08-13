<?php

namespace DerFlohwalzer\LaravelSubscription;

use Illuminate\Support\ServiceProvider;

class PackageServiceProvider extends ServiceProvider {
    public function boot() {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    public function register() {
    }
}