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

    public function down()
    {
        Schema::dropIfExists('model_package_plan_logs');
    }
}