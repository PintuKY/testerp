<?php

namespace App\Models;

use App\Models\SupplierProduct;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecipeItem extends Model
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
        return $query->where('recipe_items.status', 1);
    }

    public function ingredient()
    {
        return $this->hasOne(SupplierProduct::class, 'id', 'ingredient_id');
    }

}
