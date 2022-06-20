<?php

return [
    'admin_columns' => [
        ['data'=>'id' , 'name' => 'Id'],
        ['data'=>'contacts_name' , 'name' => 'Name'],
        ['data'=>'address' , 'name' => 'Address'],
        ['data'=>'postal' , 'name' => 'Postal Code'],
        ['data'=>'pax' , 'name' => 'Pax'],
        ['data'=>'addon' , 'name' => 'Addon'],
        ['data'=>'remark' , 'name' => 'Remark'],
        ['data'=>'delivery_note' , 'name' => 'Delivery Note'],
        ['data'=>'hp_number' , 'name' => 'Hp Number'],
        ['data'=>'driver_name' , 'name' => 'Driver Name'],
    ],
    'saif_columns' => [
        ['data'=>'id' , 'name' => 'Id'],
        ['data'=>'contacts_name' , 'name' => 'Name'],
        ['data'=>'pax' , 'name' => 'Pax'],
        ['data'=>'addon' , 'name' => 'Addon'],
    ],
    'user_columns' => [
        ['data'=>'id' , 'name' => 'Id'],
        ['data'=>'contacts_name' , 'name' => 'Name'],
        ['data'=>'pax' , 'name' => 'Pax'],
        ['data'=>'addon' , 'name' => 'Addon'],
        ['data'=>'address' , 'name' => 'Address'],
        ['data'=>'postal' , 'name' => 'Postal Code'],
    ],

    'product_type' => [
        0 => 'Not Applicable',
        1 => 'Lunch',
        2 => 'Dinner',
        3 => 'Both',
    ],

];
