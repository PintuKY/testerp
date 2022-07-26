<?php

namespace App\Models;

use App\Models\TaxRate;
use App\Models\SupplierProductCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierProduct extends Model {
    
    use HasFactory,SoftDeletes;

    protected $guarded = [];
    protected $table   = 'supplier_products';
    protected $appends = ['image_url','image_path'];



    public function category()
    {
        return $this->belongsTo(SupplierProductCategory::class);
    }
    public function unit()
    {
        return $this->belongsTo(SupplierProductUnit::class);
    }
    public function product_tax()
    {
        return $this->belongsTo(\App\Models\TaxRate::class, 'tax', 'id');
    }
    public function media()
    {
        return $this->morphMany(\App\Models\Media::class, 'model');
    }

    public function getImageUrlAttribute()
    {
        if (!empty($this->image)) {
            $image_url = asset('/uploads/img/' . rawurlencode($this->image));
        } else {
            $image_url = asset('/img/default.png');
        }
        return $image_url;
    }
    public function getImagePathAttribute()
    {
        if (!empty($this->image)) {
            $image_path = public_path('uploads') . '/' . config('constants.product_img_path') . '/' . $this->image;
        } else {
            $image_path = null;
        }
        return $image_path;
    }
}
