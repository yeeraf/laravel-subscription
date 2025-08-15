<?php

namespace DerFlohwalzer\LaravelSubscription\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use DerFlohwalzer\LaravelSubscription\PackageServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            PackageServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Use in-memory sqlite
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function setUpDatabase(): void
    {
        // Core support table
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        // Domain tables
        Schema::create('package_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('package_plan_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_plan_id')->constrained('package_plans')->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->string('currency', 3);
            $table->integer('day_duration');
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->timestamps();
        });

        Schema::create('benefits', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('type');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('benefit_package_plan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_plan_id')->constrained('package_plans')->cascadeOnDelete();
            $table->foreignId('benefit_id')->constrained('benefits')->cascadeOnDelete();
            $table->string('value');
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->timestamps();
        });

        Schema::create('model_package_plans', function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->foreignId('package_plan_id')->nullable()->constrained('package_plans')->nullOnDelete();
            $table->foreignId('package_plan_price_id')->nullable()->constrained('package_plan_prices')->nullOnDelete();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->string('status')->default('pending');
            $table->text('remark')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('model_package_plan_logs', function (Blueprint $table) {
            $table->id();
            $table->string('model')->nullable();
            $table->foreignId('model_package_plan_id')->constrained('model_package_plans')->cascadeOnDelete();
            $table->string('action');
            $table->text('description')->nullable();
            $table->json('changes')->nullable();
            $table->timestamp('logged_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('benefit_package_plan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('benefit_package_plan_id')->constrained('benefit_package_plan')->cascadeOnDelete();
            $table->string('action');
            $table->text('description')->nullable();
            $table->json('changes')->nullable();
            $table->timestamp('logged_at')->useCurrent();
            $table->timestamps();
        });
    }
}