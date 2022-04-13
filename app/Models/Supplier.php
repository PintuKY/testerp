<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Supplier extends Model
{
    use Notifiable;
    use SoftDeletes;

    protected $table = 'supplier';
    protected $guarded = ['id'];

    public function business()
    {
        return $this->belongsTo(\App\Models\Business::class);
    }
    

    /**
     * Return list of suppliers dropdown for a business
     *
     * @param $business_id int
     * @param $prepend_none = true (boolean)
     *
     * @return array users
    */

    public static function suppliersDropdown($business_id, $prepend_none = true, $append_id = true)
    {
        $all_supplier = SUpplier::where('business_id', $business_id)->active();

        if ($append_id) {
            $all_supplier->select('id',DB::raw("CONCAT(name, ' (', supplier_business_name, ')') as supplier"));
        }

        if (auth()->check() && !auth()->user()->can('supplier.view') && auth()->user()->can('supplier.view_own')) {
            $all_supplier->where('supplier.created_by', auth()->user()->id);
        }

        $suppliers = $all_supplier->pluck('supplier_id', 'id');

        //Prepend none
        if ($prepend_none) {
            $suppliers = $suppliers->prepend(__('lang_v1.none'), '');
        }

        return $suppliers;
    }

    public function scopeActive($query)
    {
        return $query->where('supplier.supplier_status', 'active');
    }
}
