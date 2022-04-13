<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierTransaction extends Model
{   
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

    public function location()
    {
        return $this->belongsTo(\App\Models\BusinessLocation::class, 'location_id');
    }
}
