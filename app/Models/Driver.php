<?php

namespace App\Models;

use App\Utils\AppConstant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use App\Models\KitchenLocation;

class Driver extends Model
{
    use HasFactory; use Notifiable; use SoftDeletes;

    protected $guarded = ['id'];


    public function driverAttendance()
    {
        return $this->hasOne(DriverAttendance::class, 'driver_id', 'id');
    }
    /**
    * Get the Only Active Driver.
    */
    public function scopeActive($query)
    {
        return $query->where('status', AppConstant::STATUS_ACTIVE);
    }
    public function kitchenLocation()
    {
        return $this->belongsTo(KitchenLocation::class,'kitchen_location_id');
    }
}
