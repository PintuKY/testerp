<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class KitchenLocation extends Model
{
    use HasFactory;
    protected $table = 'kitchens_locations';
    protected $guarded = ['id'];

    // public static function forDropdown($business_locations_id)
    // {
    //     $dropdown = KitchenLocation::where('business_locations_id', $business_locations_id)
    //                             ->pluck('name', 'id');

    //     return $dropdown;
    // }

}
