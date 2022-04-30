<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_lines', function (Blueprint $table) {
            $table->increments('id');
            $table->foreignId('transaction_id');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            $table->foreignId('product_id');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreignId('variation_id');
            $table->foreign('variation_id')->references('id')->on('variations')->onDelete('cascade');
            $table->decimal('quantity', 22, 4);
            $table->decimal('purchase_price', 22, 4);
            $table->decimal('purchase_price_inc_tax', 22, 4)->default(0);
            $table->decimal('item_tax', 22, 4)->comment("Tax for one quantity");
            $table->foreignId('tax_id')->nullable();
            $table->foreign('tax_id')->references('id')->on('tax_rates')->onDelete('cascade');
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
        Schema::dropIfExists('purchase_lines');
    }
}
