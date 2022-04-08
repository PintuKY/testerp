<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustmentLine extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function variation()
    {
        return $this->belongsTo(\App\Models\Variation::class, 'variation_id');
    }

    public function lot_details()
    {
        return $this->belongsTo(\App\Models\PurchaseLine::class, 'lot_no_line_id');
    }
}
