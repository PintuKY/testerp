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
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('billing_phone', 12)->after('shipping_custom_field_details')->nullable();
            $table->string('billing_email', 100)->after('billing_phone')->nullable();
            $table->string('shipping_address_1', 225)->after('billing_email')->nullable();
            $table->string('shipping_address_2', 225)->after('shipping_address_1')->nullable();
            $table->string('shipping_city', 100)->after('shipping_address_2')->nullable();
            $table->string('shipping_state', 50)->after('shipping_city')->nullable();
            $table->string('shipping_country')->after('shipping_state')->nullable();
            $table->string('shipping_zipcode', 8)->after('shipping_country');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            //
        });
    }
};
