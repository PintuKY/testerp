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

    public function menu_items()
    {
        return $this->hasMany(MenuItem::class, 'menu_id', 'id');
    }

}
