<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionSellLinesVariants extends Model
{
    use SoftDeletes;

    protected $table = 'transaction_sell_lines_variants';
    protected $guarded = ['id'];

    public function transaction_sell_line()
    {
        return $this->belongsTo(\App\Models\TransactionSellLine::class);
    }
}
