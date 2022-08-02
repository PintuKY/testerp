<?php

namespace App\Models;

use App\Models\SupplierProduct;
use App\Models\SupplierTransaction;
use Illuminate\Database\Eloquent\Model;
use App\Models\SupplierTransactionSellLine;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\SupplierTransactionSellLinesDay;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\SupllierTransactionSellLinesPurchaseLines;

class SupplierTransactionSellLine extends Model
{
    use HasFactory;
    protected $guarded = ['id'];


    public function transaction()
    {
        return $this->belongsTo(SupplierTransaction::class);
    }

    public function product()
    {
        return $this->belongsTo(SupplierProduct::class, 'product_id');
    }

    public function modifiers()
    {
        return $this->hasMany(SupplierTransactionSellLine::class, 'parent_sell_line_id')
            ->where('children_type', 'modifier');
    }

    public function sell_line_purchase_lines()
    {
        return $this->hasMany(SupllierTransactionSellLinesPurchaseLines::class, 'sell_line_id');
    }

    public function sell_lines_days()
    {
        return $this->hasMany(SupplierTransactionSellLinesDay::class , 'transaction_sell_lines_id' , 'id');

    }
}
