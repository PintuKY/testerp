<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableGroupSubTaxes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_sub_taxes', function (Blueprint $table) {
            $table->foreignId('group_tax_id');
            $table->foreign('group_tax_id')->references('id')->on('tax_rates')->onDelete('cascade');
            $table->foreignId('tax_id');
            $table->foreign('tax_id')->references('id')->on('tax_rates')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
