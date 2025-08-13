<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModelPackagePlanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('model_package_plan', function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->foreignId('package_plan_id')->constrained()->restrictOnDelete();
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->string('status')->default('active');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('model_package_plan');
    }
}