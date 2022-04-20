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
        Schema::create('supplier_transaction_sell_lines', function (Blueprint $table) {
            $table->id();
            $table->integer('supplier_transaction_id')->unsigned();
            $table->foreign('supplier_transaction_id')->references('id')->on('supplier_transactions')->onDelete('cascade');
            $table->integer('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->integer('variation_id')->unsigned();
            $table->foreign('variation_id')->references('id')->on('variations')->onDelete('cascade');
            $table->decimal('quantity', 22, 4)->default(0);
            $table->decimal('unit_price', 22, 4)->comment("Sell price excluding tax")->nullable();
            $table->decimal('unit_price_inc_tax', 22, 4)->comment("Sell price including tax")->nullable();
            $table->decimal('item_tax', 22, 4)->comment("Tax for one quantity");
            $table->integer('tax_id')->unsigned()->nullable();
            $table->foreign('tax_id')->references('id')->on('tax_rates')->onDelete('cascade');
            $table->integer('parent_sell_line_id')->nullable();
            $table->integer('lot_no_line_id')->nullable();
            $table->enum('line_discount_type', ['fixed', 'percentage'])->nullable();
            $table->decimal('line_discount_amount', 22, 4)->default(0);
            $table->decimal('qty_returned', 22, 4)->default(0);
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
        Schema::dropIfExists('supplier_transaction_sell_lines');
    }
};
