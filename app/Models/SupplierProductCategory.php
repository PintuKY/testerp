<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierProductCategory extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table   = 'supplier_product_categories';
}
