<?php

namespace App\Events;

use App\Models\SupplierTransactionPayments;
use Illuminate\Queue\SerializesModels;
use App\Models\TransactionPayment;

class TransactionPaymentUpdated
{
    use SerializesModels;

    public $supplierTransactionPayments;

    public $transactionType;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(SupplierTransactionPayments $supplierTransactionPayments, $transactionType)
    {
        $this->supplierTransactionPayments = $supplierTransactionPayments;
        $this->transactionType = $transactionType;
    }
}
