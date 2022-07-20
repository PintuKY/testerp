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
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ref_no');
            $table->integer('business_id')->unsigned();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->enum('type', ['stock_adjustment', 'stock_transfer']);
            $table->decimal('total_amount_recovered', 22, 4)->nullable();
            $table->decimal('final_total', 22, 4)->default(0);
            $table->integer('location_id')->unsigned();
            $table->foreign('location_id')->references('id')->on('kitchens_locations');
            $table->enum('adjustment_type', ['normal', 'abnormal'])->nullable();
            $table->text('additional_notes')->nullable();
            $table->integer('created_by')->unsigned();
            $table->foreign('created_by')->references('id')->on('users');
            $table->date('transaction_date');
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
        Schema::dropIfExists('stock_transactions');
    }
};
