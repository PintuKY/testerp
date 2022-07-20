<?php

namespace App\Models;

use App\Models\SupplierProduct;
use Illuminate\Database\Eloquent\Model;

class StockAdjustmentLine extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function product()
    {
        return $this->belongsTo(SupplierProduct::class, 'product_id');
    }

    public function lot_details()
    {
        return $this->belongsTo(\App\Models\PurchaseLine::class, 'lot_no_line_id');
    }
}
