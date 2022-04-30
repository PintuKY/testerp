<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('items', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamp('production_date')->nullable();
            $table->timestamp('time_start')->nullable();
            $table->timestamp('time_end')->nullable();
            $table->string('name_operator')->nullable();
            $table->string('name_supervisor')->nullable();
            $table->timestamp('updated_at')->nullable()->useCurrent();
            $table->softDeletes()->nullable()->useCurrent();
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('items');
    }
};
