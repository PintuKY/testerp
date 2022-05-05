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
        Schema::create('master_list', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('transaction_sell_lines_id');
            $table->integer('transaction_id');
            $table->integer('contacts_id')->nullable();
            $table->string('contacts_name');
            $table->text('shipping_address_line_1');
            $table->text('shipping_address_line_2')->nullable();
            $table->string('shipping_city');
            $table->string('shipping_state');
            $table->string('shipping_country');
            $table->string('shipping_zip_code');
            $table->text('additional_notes');
            $table->text('delivery_note');
            $table->string('shipping_phone');
            $table->enum('status',['0','1'])->default('1');
            $table->string('staff_notes');
            $table->integer('created_by');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
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
        Schema::dropIfExists('master_list');
    }
};
