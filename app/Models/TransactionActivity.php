<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionActivity extends Model
{
    use SoftDeletes;

    protected $table = 'transactions_activity';
    protected $guarded = ['id'];
}
