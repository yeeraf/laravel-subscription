<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePackagePlanPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('package_plan_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->string('currency', 3);
            $table->integer('day_duration');
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();


            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('package_plan_prices');
    }
}