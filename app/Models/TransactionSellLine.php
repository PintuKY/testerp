<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionSellLine extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function transaction()
    {
        return $this->belongsTo(\App\Models\Transaction::class);
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    public function variations()
    {
        return $this->belongsTo(\App\Models\Variation::class, 'variation_id');
    }

    public function modifiers()
    {
        return $this->hasMany(\App\Models\TransactionSellLine::class, 'parent_sell_line_id')
            ->where('children_type', 'modifier');
    }

    public function sell_line_purchase_lines()
    {
        return $this->hasMany(\App\Models\TransactionSellLinesPurchaseLines::class, 'sell_line_id');
    }

    public function transactionSellLinesVariants()
    {
        return $this->hasMany(TransactionSellLinesVariants::class, 'transaction_sell_lines_id');
    }

    /**
     * Get the quantity column.
     *
     * @param  string  $value
     * @return float $value
     */
    public function getQuantityAttribute($value)
    {
        return (float)$value;
    }

    public function lot_details()
    {
        return $this->belongsTo(\App\Models\PurchaseLine::class, 'lot_no_line_id');
    }

    public function get_discount_amount()
    {
        $discount_amount = 0;
        if (!empty($this->line_discount_type) && !empty($this->line_discount_amount)) {
            if ($this->line_discount_type == 'fixed') {
                $discount_amount = $this->line_discount_amount;
            } elseif ($this->line_discount_type == 'percentage') {
                $discount_amount = ($this->unit_price_before_discount * $this->line_discount_amount) / 100;
            }
        }
        return $discount_amount;
    }

    /**
     * Get the unit associated with the purchase line.
     */
    public function sub_unit()
    {
        return $this->belongsTo(\App\Models\Unit::class, 'sub_unit_id');
    }

    public function order_statuses()
    {
        $statuses = [
            'received',
            'cooked',
            'served'
        ];
    }

    public function service_staff()
    {
        return $this->belongsTo(\App\Models\User::class, 'res_service_staff_id');
    }

    /**
     * The warranties that belong to the sell lines.
     */
    public function warranties()
    {
        return $this->belongsToMany('App\Models\Warranty', 'sell_line_warranties', 'sell_line_id', 'warranty_id');
    }

    public function line_tax()
    {
        return $this->belongsTo(\App\Models\TaxRate::class, 'tax_id');
    }

    public function so_line()
    {
        return $this->belongsTo(\App\Models\TransactionSellLine::class, 'so_line_id');
    }
}
