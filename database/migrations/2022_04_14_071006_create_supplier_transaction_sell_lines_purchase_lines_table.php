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
        Schema::create('supplier_transaction_sell_lines_purchase_lines', function (Blueprint $table) {
            $table->id();
            $table->integer('sell_line_id')->unsigned()->comment("id from suppplier_transaction_sell_lines")->nullable();
            $table->integer('stock_adjustment_line_id')->unsigned()->comment("id from suppplier_stock_adjustment_lines")->nullable();
            $table->integer('purchase_line_id')->unsigned()->comment("id from purchase_lines");
            $table->decimal('quantity', 22, 4);
            $table->decimal('qty_returned', 22, 4)->default(0);
            $table->text('purchase_order_ids')->nullable();
            $table->timestamps();

            $table->index('sell_line_id', 'sell_line_id');
            $table->index('stock_adjustment_line_id', 'stock_adjustment_line_id');
            $table->index('purchase_line_id', 'purchase_line_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('supplier_transaction_sell_lines_purchase_lines');
    }
};
