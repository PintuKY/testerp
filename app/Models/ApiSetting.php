<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BusinessLocation;

class ApiSetting extends Model
{
    use HasFactory;
    protected $table = "api_settings";
    protected $guarded = ['id'];

    public function businesslocation()
    {
        return $this->belongsTo(BusinessLocation::class, 'business_locations_id', 'id');
    }

}
