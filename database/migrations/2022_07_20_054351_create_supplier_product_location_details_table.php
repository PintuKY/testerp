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
        Schema::create('supplier_product_location_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('supplier_products');
            $table->integer('location_id')->unsigned();
            $table->foreign('location_id')->references('id')->on('kitchens_locations');
            $table->decimal('qty_available', 22, 4)->default(0);
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
        Schema::dropIfExists('supplier_product_location_details');
    }
};
