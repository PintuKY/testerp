<?php

namespace App\Models;

use App\Models\Business;
use App\Models\KitchenLocation;
use App\Models\StockAdjustmentLine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockTransaction extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table   = 'stock_transactions';

    public function stock_adjustment_lines()
    {
        return $this->hasMany(StockAdjustmentLine::class,'transaction_id');
    }
    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function location()
    {
        return $this->belongsTo(KitchenLocation::class,'location_id');
    }
}
