<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    use SoftDeletes;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function scopeActive($query)
    {
        return $query->where('menus.status', 1);
    }
    public function location()
    {
        return $this->hasOne(\App\Models\BusinessLocation::class, 'id','business_location_id');
    }
    public function category()
    {
        return$this->hasOne(Category::class,'id','category_id');
    }
    public function recipe()
    {
        return$this->hasOne(Recipe::class,'id','recipe_id');
    }

}
