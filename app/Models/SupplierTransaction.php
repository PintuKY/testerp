<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierTransaction extends Model
{   
    protected $table = 'supplier_transactions';
    protected $guarded = ['id'];
}
