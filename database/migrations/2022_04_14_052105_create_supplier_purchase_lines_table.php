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
        Schema::create('supplier_purchase_lines', function (Blueprint $table) {
            $table->id();
            $table->integer('supplier_transactions_id')->unsigned();
            $table->foreign('supplier_transactions_id')->references('id')->on('supplier_transactions')->onDelete('cascade');
            $table->integer('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->integer('variation_id')->unsigned();
            $table->foreign('variation_id')->references('id')->on('variations')->onDelete('cascade');
            $table->decimal('quantity', 22, 4);
            $table->decimal('purchase_price', 22, 4);
            $table->decimal('purchase_price_inc_tax', 22, 4)->default(0);
            $table->decimal('item_tax', 22, 4)->comment("Tax for one quantity");
            $table->integer('tax_id')->unsigned()->nullable();
            $table->foreign('tax_id')->references('id')->on('tax_rates')->onDelete('cascade');
            $table->date('mfg_date')->nullable();
            $table->date('exp_date')->nullable();
            $table->decimal('quantity_sold', 22, 4)->default(0)->comment("Quanity sold from this purchase line");
            $table->decimal('quantity_adjusted', 22, 4)->default(0)->comment("Quanity adjusted in stock adjustment from this purchase line");
            $table->decimal('pp_without_discount', 22, 4)->default(0)->comment('Purchase price before inline discounts');
            $table->decimal('discount_percent', 5, 2)->default(0)->comment('Inline discount percentage');
            $table->decimal('quantity_returned', 22, 4)->default(0);
            $table->integer('sub_unit_id')->nullable();
            $table->decimal('mfg_quantity_used', 22, 4)->default(0);
            $table->integer('purchase_order_line_id')->nullable();
            $table->decimal('po_quantity_purchased', 22, 4)->default(0);
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
        Schema::dropIfExists('supplier_purchase_lines');
    }
};
