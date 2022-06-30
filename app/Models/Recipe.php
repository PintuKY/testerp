<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recipe extends Model
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
        return $query->where('recipes.status', 1);
    }

    public function recipe_items()
    {
        return $this->hasMany(RecipeItem::class, 'recipe_id', 'id');
    }

}
