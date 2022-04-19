<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class Supplier extends Model
{
    use Notifiable; use SoftDeletes;

    protected $table = 'supplier';
    protected $guarded = ['id'];

    public function business()
    {
        return $this->belongsTo(\App\Models\Business::class);
    }
    
    protected $appends = ['supplier_address'];

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
        $all_supplier = Supplier::where('business_id', $business_id)->active();
        if ($append_id) {
            $all_supplier->Select(
                DB::raw("IF(supplier_id IS NULL OR supplier_id='', name, CONCAT(name, ' - ', COALESCE(supplier_business_name, ''), '(', supplier_id, ')')) AS supplier"),
                'id'
                    );
        } else {
            $all_supplier->Select(
                'id',
                DB::raw("CONCAT(name, ' (', supplier_business_name, ')') as supplier")
                );
        }
        
        if (auth()->check() && !auth()->user()->can('supplier.view') && auth()->user()->can('supplier.view_own')) {
            $all_supplier->where('supplier.created_by', auth()->user()->id);
        }
        
        $suppliers = $all_supplier->pluck('supplier', 'id');

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

    public function documentsAndnote()
    {
        return $this->morphMany('App\Models\DocumentAndNote', 'notable');
    }

    public function getSupplierAddressAttribute()
    {
        $address_array = [];
        if (!empty($this->supplier_business_name)) {
            $address_array[] = $this->supplier_business_name;
        }
        if (!empty($this->name)) {
            $address_array[] = !empty($this->supplier_business_name) ? '<br>' . $this->name : $this->name;
        }
        if (!empty($this->address_line_1)) {
            $address_array[] = '<br>' . $this->address_line_1;
        }
        if (!empty($this->address_line_2)) {
            $address_array[] =  '<br>' . $this->address_line_2;
        }
        if (!empty($this->city)) {
            $address_array[] = '<br>' . $this->city;
        }
        if (!empty($this->state)) {
            $address_array[] = $this->state;
        }
        if (!empty($this->country)) {
            $address_array[] = $this->country;
        }

        $address = '';
        if (!empty($address_array)) {
            $address = implode(', ', $address_array);
        }
        if (!empty($this->zip_code)) {
            $address .= ',<br>' . $this->zip_code;
        }

        return $address;
    }
}
