<?php

namespace App\Models;

use App\Models\SupplierProduct;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierProductLocationDetail extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function product(Type $var = null)
    {
        return $this->belongsTo(SupplierProduct::class,'product_id');
    }
}
