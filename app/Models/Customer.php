<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'id',
        'name',
        'business_id',
        'customer_id',
        'first_name',
        'last_name',
        'email',
        'mobile',
        'city',
        'state',
        'country',
        'zip_code',
        'address_line_1',
        'address_line_2',
        'shipping_address',
        'type',
        'position',
        'customer_id',
        'created_by',

        'contact_id',
        'business_location_id',
        'billing_phone',
        'billing_email',
        'shipping_address_1',
        'shipping_address_2',
        'shipping_city',
        'shipping_state',
        'shipping_zipcode' ,

        'customer_group_id',
    ];
    protected $table = "contacts";

    public function store($customers)
    {
        $this->insert($customers);
    }

}
