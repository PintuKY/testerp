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
        Schema::table('drivers', function (Blueprint $table) {
            $table->integer('kitchen_location_id')->unsigned()->nullable()->after('address_line_2');
            $table->foreign('kitchen_location_id')->references('id')->on('kitchens_locations');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropForeign('drivers_kitchen_location_id_foreign');
            $table->dropColumn('kitchen_location_id');
        });
    }
};
