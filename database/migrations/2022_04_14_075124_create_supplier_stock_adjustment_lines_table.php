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
        Schema::create('supplier_stock_adjustment_lines', function (Blueprint $table) {
            $table->id();
            $table->integer('supplier_transaction_id')->unsigned();
            $table->foreign('supplier_transaction_id')->references('id')->on('supplier_transactions')->onDelete('cascade');
            $table->integer('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->integer('variation_id')->unsigned();
            $table->foreign('variation_id')->references('id')->on('variations')
            ->onDelete('cascade');
            $table->decimal('quantity', 22, 4);
            $table->decimal('unit_price', 22, 4)->comment("Last purchase unit price")->nullable();
            $table->integer('removed_purchase_line')->nullable();
            $table->integer('lot_no_line_id')->nullable();
            $table->timestamps();

            //Indexing
            $table->index('supplier_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('supplier_stock_adjustment_lines');
    }
};
