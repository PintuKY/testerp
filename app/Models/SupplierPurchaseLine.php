<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierPurchaseLine extends Model
{
    use HasFactory; use SoftDeletes;

    protected $table = 'supplier_purchase_lines';
    protected $guarded = ['id'];

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    public function variations()
    {
        return $this->belongsTo(\App\Models\Variation::class, 'variation_id');
    }

    public function subUnit()
    {
        return $this->belongsTo(\App\Models\Unit::class, 'sub_unit_id');
    }

    public function purchaseOrderLine()
    {
        return $this->belongsTo(\App\Models\SupplierPurchaseLine::class, 'purchase_order_line_id');
    }

}   
