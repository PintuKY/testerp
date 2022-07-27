<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierProductBrand extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $guarded = [];

    public static function forDropdown($business_id, $show_none = false, $filter_use_for_repair = false)
    {
        $query = SupplierProductBrand::where('business_id', $business_id);

        if ($filter_use_for_repair) {
            $query->where('use_for_repair', 1);
        }

        $brands = $query->orderBy('name', 'asc')
                    ->pluck('name', 'id');

        if ($show_none) {
            $brands->prepend(__('lang_v1.none'), '');
        }

        return $brands;
    }
    
}
