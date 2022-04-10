<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariation extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function variations()
    {
        return $this->hasMany(\App\Models\Variation::class);
    }

    public function variation_template()
    {
        return $this->belongsTo(\App\Models\VariationTemplate::class);
    }
}
