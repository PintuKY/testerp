<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionSellLinesPurchaseLines extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function purchase_line()
    {
        return $this->belongsTo(\App\Models\PurchaseLine::class, 'purchase_line_id');
    }
}
