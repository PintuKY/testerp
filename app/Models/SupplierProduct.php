<?php

namespace App\Models;

use App\Models\Supplier;
use App\Models\SupplierProductCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierProduct extends Model {
    
    use HasFactory,SoftDeletes;

    protected $guarded = [];
    protected $table   = 'supplier_products';

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    public function category()
    {
        return $this->belongsTo(SupplierProductCategory::class);
    }
    public function unit()
    {
        return $this->belongsTo(SupplierProductUnit::class);
    }
}
