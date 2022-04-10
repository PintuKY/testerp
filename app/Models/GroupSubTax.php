<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupSubTax extends Model
{
    public function tax_rate()
    {
        return $this->belongsTo(\App\Models\TaxRate::class, 'group_tax_id');
    }
}
