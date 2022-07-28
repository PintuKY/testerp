<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\SupplierProduct;
use Illuminate\Database\Eloquent\Model;
use App\Models\SupplierTransactionSellLine;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierTransaction extends Model
{   
    use SoftDeletes;
    
    protected $table = 'supplier_transactions';
    protected $guarded = ['id'];

    public function stock_adjustment_lines()
    {
        return $this->hasMany(SupplierStockAdjustmentLine::class,'supplier_transaction_id');
    }
    public function sell_lines()
    {
        return $this->hasMany(SupplierTransactionSellLine::class);
    }
    public function recurring_parent()
    {
        return $this->hasOne(SupplierTransaction::class, 'id', 'recur_parent_id');
    }

    public function product()
    {
        return $this->belongsTo(SupplierProduct::class,'product_id');
    }
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
        return $this->belongsTo(\App\Models\KitchenLocation::class, 'location_id');
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

    public function returnParent()
    {
        return $this->hasOne(\App\Models\SupplierTransaction::class, 'return_parent_id');
    }
}
