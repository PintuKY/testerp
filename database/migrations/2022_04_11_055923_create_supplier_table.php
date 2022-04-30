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
        Schema::create('supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->string('supplier_business_name')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('prefix')->nullable();
            $table->foreignId('supplier_id')->nullable();
            $table->foreign('supplier_id')->references('id')->on('supplier')->onDelete('cascade');
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->text('address_line_1')->nullable();
            $table->text('address_line_2')->nullable();
            $table->string('zip_code')->nullable();
            $table->date('dob')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('landmark')->nullable();
            $table->string('mobile');
            $table->string('landline')->nullable();
            $table->string('alternate_number')->nullable();
            $table->integer('pay_term_number')->nullable();
            $table->enum('pay_term_type', ['days', 'months'])->nullable();
            $table->decimal('credit_limit', 22, 4)->nullable();
            $table->string('supplier_status')->index()->default('active');
            $table->decimal('balance', 22, 4)->default(0);
            $table->foreignId('created_by');
            $table->boolean('is_default')->default(0);
            $table->softDeletes();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('supplier');
    }
};
