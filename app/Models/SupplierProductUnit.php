<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierProductUnit extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table   = 'supplier_product_units';

    public function base_unit()
    {
        return $this->belongsTo(\App\Models\SupplierProductUnit::class, 'base_unit_id');
    }
    public static function forDropdown($business_id, $show_none = false, $only_base = true)
    {
        $query = SupplierProductUnit::where('business_id', $business_id);
        
        if ($only_base) {
            $query->whereNull('base_unit_id');
        }

        $units = $query->select('name', 'id')->get();
        $dropdown = $units->pluck('name', 'id');
        if ($show_none) {
            $dropdown->prepend(__('messages.please_select'), '');
        }
        return $dropdown;
    }
}
