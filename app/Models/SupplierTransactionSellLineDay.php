<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SupplierTransactionSellLine;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierTransactionSellLinesDay extends Model
{
    use SoftDeletes;
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];
    // protected $casts = [
    //     'day' => 'date:N',
    // ];

    public function transaction_sell_line()
    {
        return $this->belongsTo(SupplierTransactionSellLine::class);
    }

}
