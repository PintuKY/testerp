<?php

namespace App\Models;

use App\Events\TransactionPaymentDeleted;
use App\Events\TransactionPaymentUpdated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierTransactionPayments extends Model
{
    use HasFactory;use SoftDeletes;

     /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];


     /**
     * Get the phone record associated with the user.
     */
    public function paymentAccount()
    {
        return $this->belongsTo(\App\Models\Account::class, 'account_id');
    }

    /**
     * Get the transaction related to this payment.
     */
    public function transaction()
    {
        return $this->belongsTo(\App\Models\SupplierTransaction::class, 'supplier_transaction_id');
    }

    /**
     * Get the user.
     */
    public function created_user()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get child payments
     */
    public function child_payments()
    {
        return $this->hasMany(\App\Models\SupplierTransactionPayments::class, 'parent_id');
    }

    /**
     * Retrieves documents path if exists
     */
    public function getDocumentPathAttribute()
    {
        $path = !empty($this->document) ? asset('/uploads/documents/' . $this->document) : null;

        return $path;
    }

    /**
     * Removes timestamp from document name
     */
    public function getDocumentNameAttribute()
    {
        $document_name = !empty(explode("_", $this->document, 2)[1]) ? explode("_", $this->document, 2)[1] : $this->document ;
        return $document_name;
    }

    public static function deletePayment($payment)
    {
        //Update parent payment if exists
        if (!empty($payment->parent_id)) {
            $parent_payment = SupplierTransactionPayments::find($payment->parent_id);
            $parent_payment->amount -= $payment->amount;

            if ($parent_payment->amount <= 0) {
                $parent_payment->delete();
                event(new TransactionPaymentDeleted($parent_payment));
            } else {
                $parent_payment->save();
                //Add event to update parent payment account transaction
                event(new TransactionPaymentUpdated($parent_payment, null));
            }
        }

        $payment->delete();

        $supplierTransactionUtil = new \App\Utils\SupplierTransactionUtil();

        if(!empty($payment->supplier_transaction_id)) {
            //update payment status
            $transaction = $payment->load('transaction')->transaction;
            
            $transaction_before = $transaction->replicate();

            $payment_status = $supplierTransactionUtil->updatePaymentStatus($payment->supplier_transaction_id);

            $transaction->payment_status = $payment_status;

            $supplierTransactionUtil->activityLog($transaction, 'payment_edited', $transaction_before);
        }

        $log_properities = [
            'id' => $payment->id,
            'ref_no' => $payment->payment_ref_no
        ];
        $supplierTransactionUtil->activityLog($payment, 'payment_deleted', null, $log_properities);

        //Add event to delete account transaction
        event(new TransactionPaymentDeleted($payment));

    }
}
