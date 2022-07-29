<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class KitchenLocation extends Model
{
    use HasFactory;
    protected $table = 'kitchens_locations';
    protected $guarded = ['id'];

     public static function forDropdown()
     {
         $dropdown = KitchenLocation::pluck('name', 'id');

         return $dropdown;
     }

}
