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
        Schema::create('kitchens_locations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->text('landmark');
            $table->string('country',100);
            $table->string('state',100);
            $table->string('city',100);
            $table->char('zip_code',7);
            $table->string('mobile')->nullable();
            $table->string('alternate_number')->nullable();
            $table->string('email')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kitchens_locations');
    }
};
