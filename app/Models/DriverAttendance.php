<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class DriverAttendance extends Model
{
    use HasFactory; use Notifiable; use SoftDeletes;

    protected $guarded = ['id'];

    public function driver()
    {
        return $this->hasOne(Driver::class, 'id', 'driver_id');
    }
}
