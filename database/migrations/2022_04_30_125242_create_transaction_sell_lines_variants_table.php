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
        Schema::create('transaction_sell_lines_variants', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('transaction_sell_lines_id');
            $table->integer('variation_templates_id');
            $table->integer('variation_value_templates_id');
            $table->string('name');
            $table->string('value');
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
        Schema::dropIfExists('transaction_sell_lines_variants');
    }
};
