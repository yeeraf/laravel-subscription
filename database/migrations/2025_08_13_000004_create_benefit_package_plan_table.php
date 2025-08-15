<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBenefitPackagePlanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('benefit_package_plan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('benefit_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('benefit_package_plan');
    }
}