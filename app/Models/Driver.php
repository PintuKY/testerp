<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Driver extends Model
{
    use HasFactory; use Notifiable; use SoftDeletes;

    protected $guarded = ['id'];


    /**
    * Get the Only Active Driver.
    */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
