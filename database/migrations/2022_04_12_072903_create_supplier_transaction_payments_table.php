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
        Schema::create('supplier_transaction_payments', function (Blueprint $table) {
            $table->id();
            $table->integer('supplier_transaction_id')->unsigned();
            $table->foreign('supplier_transaction_id')->references('id')->on('supplier_transactions')->onDelete('cascade');
            $table->decimal('amount', 22, 4)->default(0);
            $table->enum('method', ['cash', 'card', 'cheque', 'bank_transfer','custom_pay_1', 'custom_pay_2', 'custom_pay_3', 'other']);
            $table->string('card_transaction_number')->nullable();
            $table->string('card_number')->nullable();
            $table->enum('card_type', ['visa', 'master'])->nullable();
            $table->string('card_holder_name')->nullable();
            $table->string('card_month')->nullable();
            $table->string('card_year')->nullable();
            $table->string('card_security', 5)->nullable();
            $table->string('cheque_number')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('note')->nullable();
            $table->dateTime('paid_on')->nullable();
            $table->integer('created_by');
            $table->integer('payment_for')->nullable();
            $table->integer('parent_id')->nullable();
            $table->boolean('is_return')->default(false)->comment('Used during sales to return the change');
            $table->string('payment_ref_no')->nullable();
            $table->string('transaction_no')->nullable();
            $table->integer('account_id')->nullable();
            $table->integer('business_id')->nullable();
            $table->string('document')->nullable();
            $table->boolean('paid_through_link')->default(0);
            $table->string('gateway')->nullable();
            $table->timestamps();

            //Indexing
            $table->index('created_by');
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('supplier_transaction_payments');
    }
};
