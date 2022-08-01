<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierTransactionSellLinesPurchaseLines extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function supplierPurchaseLine()
    {
        return $this->belongsTo(\App\Models\SupplierPurchaseLine::class, 'purchase_line_id');
    }
}
