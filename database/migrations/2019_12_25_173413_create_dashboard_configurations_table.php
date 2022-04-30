<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDashboardConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dashboard_configurations', function (Blueprint $table) {
            $table->increments('id');

            $table->foreignId('business_id');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');

            $table->integer('created_by');
            $table->string('name');
            $table->string('color');
            $table->text('configuration')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dashboard_configurations');
    }
}
