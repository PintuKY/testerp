<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterList extends Model
{
    use SoftDeletes;
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    protected $table = 'master_list';

    public function transaction_sell_lines()
    {
        return $this->hasOne(\App\Models\TransactionSellLine::class, 'id', 'transaction_sell_lines_id');
    }
    // transaction_sell_lines_id

}
