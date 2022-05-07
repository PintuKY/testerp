<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Models\AccountTransaction;

use App\Utils\ModuleUtil;
use App\Utils\SupplierTransactionUtil;

class DeleteAccountTransaction
{
    protected $supplierTransactionUtil;
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param SupplierTransactionUtil $supplierTransactionUtil
     * @return void
     */
    public function __construct(SupplierTransactionUtil $supplierTransactionUtil, ModuleUtil $moduleUtil)
    {
        $this->supplierTransactionUtil = $supplierTransactionUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        //Add Supplier advance if exists
        if ($event->transactionPayment->method == 'advance') {
            $this->supplierTransactionUtil->updateSupplierBalance($event->transactionPayment->payment_for, $event->transactionPayment->amount);
        }

        if(!$this->moduleUtil->isModuleEnabled('account')){
            return true;
        }

        AccountTransaction::where('account_id', $event->transactionPayment->account_id)
                        ->where('transaction_payment_id', $event->transactionPayment->id)
                        ->delete();
    }
}
