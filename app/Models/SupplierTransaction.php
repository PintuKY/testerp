<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierTransaction extends Model
{   
    use SoftDeletes;
    
    protected $table = 'supplier_transactions';
    protected $guarded = ['id'];


    public static function supplierTransactionTypes()
    {
        return  [
                'sell' => __('sale.sale'),
                'purchase' => __('lang_v1.purchase'),
                'sell_return' => __('lang_v1.sell_return'),
                'purchase_return' =>  __('lang_v1.purchase_return'),
                'opening_balance' => __('lang_v1.opening_balance'),
                'payment' => __('lang_v1.payment')
            ];
    }

    public function business()
    {
        return $this->belongsTo(\App\Models\Business::class, 'business_id');
    }
    
    public function location()
    {
        return $this->belongsTo(\App\Models\BusinessLocation::class, 'location_id');
    }

    public function supplierPurchaseLines()
    {
        return $this->hasMany(\App\Models\SupplierPurchaseLine::class, 'supplier_transactions_id');
    }

    public static function getPaymentStatus($transaction)
    {
        $payment_status = $transaction->payment_status;

        if (in_array($payment_status, ['partial', 'due']) && !empty($transaction->pay_term_number) && !empty($transaction->pay_term_type)) {
            $transaction_date = Carbon::parse($transaction->transaction_date);
            $due_date = $transaction->pay_term_type == 'days' ? $transaction_date->addDays($transaction->pay_term_number) : $transaction_date->addMonths($transaction->pay_term_number);
            $now = Carbon::now();
            if ($now->gt($due_date)) {
                $payment_status = $payment_status == 'due' ? 'overdue' : 'partial-overdue';
            }
        }

        return $payment_status;
    }

    public function supplier()
    {
        return $this->belongsTo(\App\Models\Supplier::class, 'supplier_id');
    }

    public function paymentLines()
    {
        return $this->hasMany(\App\Models\SupplierTransactionPayments::class, 'supplier_transaction_id');
    }

    public function transactionFor()
    {
        return $this->belongsTo(\App\Models\User::class, 'expense_for');
    }

    public function tax()
    {
        return $this->belongsTo(\App\Models\TaxRate::class, 'tax_id');
    }
}
