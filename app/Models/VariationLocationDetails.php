<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariationLocationDetails extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function productVariations()
    {
        return $this->belongsTo(\App\Models\ProductVariation::class,'product_variation_id');
    }
}
