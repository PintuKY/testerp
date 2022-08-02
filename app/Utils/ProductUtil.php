<?php

namespace App\Utils;

use Carbon\Carbon;
use App\Models\Unit;
use App\Models\Media;
use App\Models\Product;
use App\Models\TaxRate;
use App\Models\Business;
use App\Models\Discount;
use App\Models\Variation;
use App\Models\ProductRack;
use App\Models\Transaction;
use App\Models\PurchaseLine;
use App\Models\SupplierProduct;
use App\Models\BusinessLocation;
use App\Models\ProductVariation;
use App\Models\VariationTemplate;
use Illuminate\Support\Facades\DB;
use App\Models\SupplierTransaction;
use App\Models\TransactionSellLine;
use App\Models\VariationGroupPrice;
use Illuminate\Support\Facades\Log;
use App\Models\SupplierPurchaseLine;
use App\Models\VariationValueTemplate;
use App\Models\VariationLocationDetails;
use App\Models\SupplierProductLocationDetail;
use App\Models\TransactionSellLinesPurchaseLines;
use App\Models\SupplierTransactionSellLinesPurchaseLines;

class ProductUtil extends Util
{
    /**
     * Create single type product variation
     *
     * @param (int or object) $product
     * @param $sku
     * @param $purchase_price
     * @param $dpp_inc_tax (default purchase pric including tax)
     * @param $profit_percent
     * @param $selling_price
     * @param $combo_variations = []
     *
     * @return boolean
     */
    public function createSingleProductVariation($product, $sku, $purchase_price, $dpp_inc_tax, $profit_percent, $selling_price, $selling_price_inc_tax, $combo_variations = [])
    {
        if (!is_object($product)) {
            $product = Product::find($product);
        }

        //create product variations
        $product_variation_data = [
            'name' => 'DUMMY',
            'is_dummy' => 1
        ];
        $product_variation = $product->product_variations()->create($product_variation_data);

        //create variations
        $variation_data = [
            'name' => 'DUMMY',
            'product_id' => $product->id,
            'sub_sku' => $sku,
            /*'default_purchase_price' => $this->num_uf($purchase_price),
            'dpp_inc_tax' => $this->num_uf($dpp_inc_tax),
            'profit_percent' => $this->num_uf($profit_percent),
            */
            'default_sell_price' => $this->num_uf($selling_price),
            'sell_price_inc_tax' => $this->num_uf($selling_price_inc_tax),
            'combo_variations' => $combo_variations
        ];
        $variation = $product_variation->variations()->create($variation_data);

        Media::uploadMedia($product->business_id, $variation, request(), 'variation_images');

        return true;
    }

    /**
     * Create variable type product variation
     *
     * @param (int or object) $product
     * @param $input_variations
     *
     * @return boolean
     */
    public function createVariableProductVariations($product, $input_variations, $business_id = null)
    {
        if (!is_object($product)) {
            $product = Product::find($product);
        }

        //create product variations
        foreach ($input_variations as $key => $value) {
            $images = [];
            $variation_template_name = !empty($value['name']) ? $value['name'] : null;
            $variation_template_id = !empty($value['variation_template_id']) ? $value['variation_template_id'] : null;

            if (empty($variation_template_id)) {
                if ($variation_template_name != 'DUMMY') {
                    $variation_template = VariationTemplate::where('business_id', $business_id)
                        ->whereRaw('LOWER(name)="' . strtolower($variation_template_name) . '"')
                        ->with(['values'])
                        ->first();
                    if (empty($variation_template)) {
                        $variation_template = VariationTemplate::create([
                            'name' => $variation_template_name,
                            'business_id' => $business_id
                        ]);
                    }
                    $variation_template_id = $variation_template->id;
                }
            } else {
                $variation_template = VariationTemplate::with(['values'])->find($value['variation_template_id']);
                $variation_template_id = $variation_template->id;
                $variation_template_name = $variation_template->name;
            }

            $product_variation_data = [
                'name' => $variation_template_name,
                'product_id' => $product->id,
                'is_dummy' => 0,
                'variation_template_id' => $variation_template_id
            ];
            $product_variation = ProductVariation::create($product_variation_data);

            //create variations
            if (!empty($value['variations'])) {
                $variation_data = [];

                $c = Variation::withTrashed()
                        ->where('product_id', $product->id)
                        ->count() + 1;

                foreach ($value['variations'] as $k => $v) {
                    $sub_sku = empty($v['sub_sku']) ? $this->generateSubSku($product->sku, $c, $product->barcode_type) : $v['sub_sku'];
                    $variation_value_id = !empty($v['variation_value_id']) ? $v['variation_value_id'] : null;
                    $variation_value_name = !empty($v['value']) ? $v['value'] : null;
                    $variation_value_price = !empty($v['price']) ? $v['price'] : null;
                    if (!empty($variation_value_id)) {
                        $variation_value = $variation_template->values->filter(function ($item) use ($variation_value_id) {
                            return $item->id == $variation_value_id;
                        })->first();
                        $variation_value_name = $variation_value->name;
                        $variation_value->value = $variation_value_price;
                        $variation_value->save();
                    } else {
                        if (!empty($variation_template)) {
                            $variation_value = VariationValueTemplate::where('variation_template_id', $variation_template->id)
                                ->whereRaw('LOWER(name)="' . $variation_value_name . '"')
                                ->first();
                            if (empty($variation_value)) {
                                $variation_value = VariationValueTemplate::create([
                                    'name' => $variation_value_name,
                                    'value' => $variation_value_price,
                                    'variation_template_id' => $variation_template->id
                                ]);
                            }
                            $variation_value_id = $variation_value->id;
                            $variation_value_name = $variation_value->name;
                            $variation_value_price = $variation_value->value;
                        } else {
                            $variation_value_id = null;
                            $variation_value_name = $variation_value_name;
                            $variation_value_price = $variation_value_price;
                        }
                    }

                    $variation_data[] = [
                        'name' => $variation_value_name,
                        'price' => $variation_value_price,
                        'variation_value_id' => $variation_value_id,
                        'product_id' => $product->id,
                        'sub_sku' => $sub_sku,
                        /*'default_purchase_price' => $this->num_uf($v['default_purchase_price']),
                        'dpp_inc_tax' => $this->num_uf($v['dpp_inc_tax']),
                        'profit_percent' => $this->num_uf($v['profit_percent']),
                        */
                        'default_sell_price' => $this->num_uf($v['default_sell_price']),
                        'sell_price_inc_tax' => $this->num_uf($v['sell_price_inc_tax'])
                    ];
                    $c++;
                    /*$images[] = 'variation_images_' . $key . '_' . $k;*/
                }

                $variations = $product_variation->variations()->createMany($variation_data);

                /*$i = 0;
                foreach ($variations as $variation) {
                    Media::uploadMedia($product->business_id, $variation, request(), $images[$i]);
                    $i++;
                }*/
            }
        }
    }

    /**
     * Update variable type product variation
     *
     * @param $product_id
     * @param $input_variations_edit
     *
     * @return boolean
     */
    public function updateVariableProductVariations($product_id, $input_variations_edit)
    {
        $product = Product::find($product_id);

        //Update product variations
        $product_variation_ids = [];
        $variations_ids = [];

        foreach ($input_variations_edit as $key => $value) {
            $product_variation_ids[] = $key;

            $product_variation = ProductVariation::find($key);
            $product_variation->name = $value['name'];
            $product_variation->save();

            //Update existing variations
            if (!empty($value['variations_edit'])) {
                foreach ($value['variations_edit'] as $k => $v) {
                    $data = [
                        'name' => $v['value'],
                        'price' => $v['price'],
                        /*'default_purchase_price' => $this->num_uf($v['default_purchase_price']),
                        'dpp_inc_tax' => $this->num_uf($v['dpp_inc_tax']),
                        'profit_percent' => $this->num_uf($v['profit_percent']),
                        */
                        'default_sell_price' => $this->num_uf($v['default_sell_price']),
                        'sell_price_inc_tax' => $this->num_uf($v['sell_price_inc_tax'])
                    ];
                    if (!empty($v['sub_sku'])) {
                        $data['sub_sku'] = $v['sub_sku'];
                    }
                    $variation = Variation::where('id', $k)
                        ->where('product_variation_id', $key)
                        ->first();
                    $variation_value_templates = VariationValueTemplate::where('id', '=', $v['variation_value_id'])->first();
                    $variation_value_templates->value = $v['price'];
                    $variation_value_templates->save();
                    $variation->update($data);
                    /*Media::uploadMedia($product->business_id, $variation, request(), 'edit_variation_images_' . $key . '_' . $k);*/

                    $variations_ids[] = $k;
                }
            }

            //Add new variations
            if (!empty($value['variations'])) {
                echo "demo1";
                $variation_data = [];
                $c = Variation::withTrashed()
                        ->where('product_id', $product->id)
                        ->count() + 1;
                $media = [];
                foreach ($value['variations'] as $k => $v) {
                    $sub_sku = empty($v['sub_sku']) ? $this->generateSubSku($product->sku, $c, $product->barcode_type) : $v['sub_sku'];

                    $variation_value_name = !empty($v['value']) ? $v['value'] : null;
                    $variation_value_price = !empty($v['price']) ? $v['price'] : null;
                    $variation_value_id = null;
                    if (!empty($product_variation->variation_template_id)) {
                        $variation_value = VariationValueTemplate::where('variation_template_id', $product_variation->variation_template_id)
                            ->whereRaw('LOWER(name)="' . $v['value'] . '"')
                            ->first();
                        $variation_value->value = $variation_value_price;
                        $variation_value->save();
                        if (empty($variation_value)) {
                            $variation_value = VariationValueTemplate::create([
                                'name' => $v['value'],
                                'value' => $v['price'],
                                'variation_template_id' => $product_variation->variation_template_id
                            ]);
                        }

                        $variation_value_id = $variation_value->id;
                    }

                    $variation_data[] = [
                        'name' => $variation_value_name,
                        'variation_value_id' => $variation_value_id,
                        'product_id' => $product->id,
                        'sub_sku' => $sub_sku,
                        /*'default_purchase_price' => $this->num_uf($v['default_purchase_price']),
                        'dpp_inc_tax' => $this->num_uf($v['dpp_inc_tax']),
                        'profit_percent' => $this->num_uf($v['profit_percent']),
                        */
                        'default_sell_price' => $this->num_uf($v['default_sell_price']),
                        'sell_price_inc_tax' => $this->num_uf($v['sell_price_inc_tax'])
                    ];
                    $c++;
                    //$media[] = 'variation_images_' . $key . '_' . $k;
                }
                $new_variations = $product_variation->variations()->createMany($variation_data);

                $i = 0;
                foreach ($new_variations as $new_variation) {
                    $variations_ids[] = $new_variation->id;
                   // Media::uploadMedia($product->business_id, $new_variation, request(), $media[$i]);
                    $i++;
                }
            }
        }

        //Check if purchase or sell exist for the deletable variations
        $count_purchase = PurchaseLine::join(
            'transactions as T',
            'purchase_lines.transaction_id',
            '=',
            'T.id'
        )
            ->where('T.type', 'purchase')
            ->where('T.status', 'received')
            ->where('T.business_id', $product->business_id)
            ->where('purchase_lines.product_id', $product->id)
            ->whereNotIn('purchase_lines.variation_id', $variations_ids)
            ->count();

        $count_sell = TransactionSellLine::join(
            'transactions as T',
            'transaction_sell_lines.transaction_id',
            '=',
            'T.id'
        )
            ->where('T.type', 'sell')
            ->where('T.status', AppConstant::FINAL)
            ->orWhere('T.status', AppConstant::PROCESSING)
            ->orWhere('T.status', AppConstant::COMPLETED)
            ->where('T.business_id', $product->business_id)
            ->where('transaction_sell_lines.product_id', $product->id)
            ->whereNotIn('transaction_sell_lines.variation_id', $variations_ids)
            ->count();

        $is_variation_delatable = $count_purchase > 0 || $count_sell > 0 ? false : true;

        if ($is_variation_delatable) {
            Variation::whereNotIn('id', $variations_ids)
                ->where('product_variation_id', $key)
                ->delete();
        } else {
            throw new \Exception(__('lang_v1.purchase_already_exist'));
        }

        ProductVariation::where('product_id', $product_id)
            ->whereNotIn('id', $product_variation_ids)
            ->delete();
    }

    /**
     * Checks if products has manage stock enabled then Updates quantity for product and its
     * variations
     *
     * @param $location_id
     * @param $product_id
     * @param $variation_id
     * @param $new_quantity
     * @param $old_quantity = 0
     * @param $number_format = null
     * @param $uf_data = true, if false it will accept numbers in database format
     *
     * @return boolean
     */
    public function updateProductQuantity($location_id, $product_id, $variation_id, $new_quantity, $old_quantity = 0, $number_format = null, $uf_data = true)
    {
        if ($uf_data) {
            $qty_difference = $this->num_uf($new_quantity, $number_format) - $this->num_uf($old_quantity, $number_format);
        } else {
            $qty_difference = $new_quantity - $old_quantity;
        }

        $product = Product::find($product_id);

        //Check if stock is enabled or not.
        if ($qty_difference != 0) {
            $variation = Variation::where('id', $variation_id)
                ->where('product_id', $product_id)
                ->first();

            //Add quantity in VariationLocationDetails
            $variation_location_d = VariationLocationDetails
                ::where('variation_id', $variation->id)
                ->where('product_id', $product_id)
                ->where('product_variation_id', $variation->product_variation_id)
                ->where('location_id', $location_id)
                ->first();

            if (empty($variation_location_d)) {
                $variation_location_d = new VariationLocationDetails();
                $variation_location_d->variation_id = $variation->id;
                $variation_location_d->product_id = $product_id;
                $variation_location_d->location_id = $location_id;
                $variation_location_d->product_variation_id = $variation->product_variation_id;
                $variation_location_d->qty_available = 0;
            }

            $variation_location_d->qty_available += $qty_difference;
            $variation_location_d->save();
        }

        return true;
    }
    public function updateSupplierProductQuantity($location_id, $product_id, $new_quantity, $old_quantity = 0, $number_format = null, $uf_data = false)
    {
        if ($uf_data) {
            $qty_difference = $this->num_uf($new_quantity, $number_format) - $this->num_uf($old_quantity, $number_format);
        } else {
            $qty_difference = $new_quantity - $old_quantity;
        }

        $product = SupplierProduct::find($product_id);

        //Check if stock is enabled or not.
        if ($qty_difference != 0) {
            //Add quantity in SupplierProductLocationDetails
            $supplier_product_location_details = SupplierProductLocationDetail::where('product_id', $product->id)
            ->where('product_id', $product_id)
            ->where('location_id', $location_id)
            ->first();

            if (empty($supplier_product_location_details)) {
                $supplier_product_location_details = new SupplierProductLocationDetail();
                $supplier_product_location_details->product_id = $product_id;
                $supplier_product_location_details->location_id = $location_id;
                $supplier_product_location_details->qty_available = 0;
            }
            $supplier_product_location_details->qty_available += $qty_difference;
            $supplier_product_location_details->save();
        }

        return true;
    }
    public function decreaseSupplierProductQuantity($product_id, $location_id, $new_quantity, $old_quantity = 0)
    {
        $qty_difference = $new_quantity - $old_quantity;
        $product = SupplierProduct::find($product_id);

        //Check if stock is enabled or not.
        // if ($product->enable_stock == 1) {
            //Decrement Quantity in variations location table
            $details = SupplierProductLocationDetail::where('product_id', $product_id)
                ->where('location_id', $location_id)
                ->first();

            //If location details not exists create new one
            if (empty($details)) {
                $details = SupplierProductLocationDetail::create([
                            'product_id' => $product_id,
                            'location_id' => $location_id,
                            'qty_available' => 0
                          ]);
            }

            $details->decrement('qty_available', $qty_difference);

        return true;
    }
    /**
     * Get all details for a product from its variation id
     *
     * @param int $variation_id
     * @param int $business_id
     * @param int $location_id
     * @param bool $check_qty (If false qty_available is not checked)
     *
     * @return array
     */
    public function getDetailsFromVariation($variation_id, $business_id, $location_id = null, $check_qty = true)
    {
        $query = Variation::join('products AS p', 'variations.product_id', '=', 'p.id')
            ->join('product_variations AS pv', 'variations.product_variation_id', '=', 'pv.id')
            ->leftjoin('variation_location_details AS vld', 'variations.id', '=', 'vld.variation_id')
            ->leftjoin('variation_value_templates AS vvtv', 'variations.variation_value_id', '=', 'vvtv.id')
            ->leftjoin('units', 'p.unit_id', '=', 'units.id')
            ->leftjoin('brands', function ($join) {
                $join->on('p.brand_id', '=', 'brands.id')
                    ->whereNull('brands.deleted_at');
            })
            ->where('p.business_id', $business_id)
            ->where('variations.id', $variation_id);

        //Add condition for check of quantity. (if stock is not enabled or qty_available > 0)
        /*if ($check_qty) {
            $query->where(function ($query) use ($location_id) {
                $query->where('p.enable_stock', '!=', 1)
                    ->orWhere('vld.qty_available', '>', 0);
            });
        }*/

        if (!empty($location_id)) {
            //Check for enable stock, if enabled check for location id.
            $query->where(function ($query) use ($location_id) {
                $query->where('vld.location_id', $location_id);
            });
        }

        $product = $query->select(
            DB::raw("IF(pv.is_dummy = 0, CONCAT(p.name,
                    ' (', pv.name, ':',variations.name, ')'), p.name) AS product_name"),
            'p.id as product_id',
            'p.brand_id',
            'p.category_id',
            'p.tax as tax_id',
            'p.enable_sr_no',
            'p.type as product_type',
            'p.name as product_actual_name',
            'p.warranty_id',
            'pv.name as product_variation_name',
            'pv.is_dummy as is_dummy',
            'variations.name as variation_name',
            'vvtv.value as variation_value',
            'variations.sub_sku',
            'p.barcode_type',
            'vld.qty_available',
            'variations.default_sell_price',
            'variations.sell_price_inc_tax',
            'variations.id as variation_id',
            'variations.combo_variations',  //Used in combo products
            'units.short_name as unit',
            'units.id as unit_id',
            'units.allow_decimal as unit_allow_decimal',
            'brands.name as brand',
            DB::raw("(SELECT purchase_price_inc_tax FROM supplier_purchase_lines WHERE
                        variation_id=variations.id ORDER BY id DESC LIMIT 1) as last_purchased_price")
        )
            ->firstOrFail();

        if ($product->product_type == 'combo') {
            if ($check_qty) {
                $product->qty_available = $this->calculateComboQuantity($location_id, $product->combo_variations);
            }

            $product->combo_products = $this->calculateComboDetails($location_id, $product->combo_variations);
        }
        return $product;
    }
    public function getDetailsFromSupplierProduct($product_id, $business_id, $location_id = null, $check_qty = true)
    {
        $query = SupplierProduct::leftjoin('supplier_product_location_details', 'supplier_products.id', '=', 'supplier_product_location_details.product_id')
        ->leftJoin('supplier_product_units','supplier_products.unit_id','=','supplier_product_units.id')
            ->where('supplier_products.business_id', $business_id)
            ->where('supplier_products.id', $product_id)
            ->where('supplier_product_location_details.location_id',$location_id);

            //Add condition for check of quantity. (if stock is not enabled or qty_available > 0)
            // if ($check_qty) {
            //     $query->where(function ($query) use ($location_id) {
            //         $query->Where('supplier_product_location_details.qty_available', '>', 0);
            //     });
            // }

            // if (!empty($location_id)) {
            //     //Check for enable stock, if enabled check for location id.
            //     $query->where(function ($query) use ($location_id) {
            //         $query->where('supplier_product_location_details.location_id', $location_id);
            //     });
            // }
        $product = $query->select(
            'supplier_products.id as product_id',
            'supplier_products.name as product_name',
            'supplier_products.tax as tax_id',
            'supplier_product_location_details.qty_available as qty_available',
            'supplier_products.purchase_price',
            'supplier_products.purchase_price_inc_tax',
            'supplier_product_units.short_name as unit',
            DB::raw("(SELECT purchase_price_inc_tax FROM supplier_purchase_lines WHERE
            supplier_purchase_lines.product_id=supplier_products.id ORDER BY id DESC LIMIT 1) as last_purchased_price")
        )->first();
        return $product;
    }
    /**
     * Calculates the quantity of combo products based on
     * the quantity of variation items used.
     *
     * @param int $location_id
     * @param array $combo_variations
     *
     * @return int
     */
    public function calculateComboQuantity($location_id, $combo_variations)
    {
        //get stock of the items and calcuate accordingly.
        $combo_qty = 0;
        foreach ($combo_variations as $key => $value) {
            $variation = Variation::with(['product', 'variation_location_details' => function ($q) use ($location_id) {
                $q->where('location_id', $location_id);
            }])->findOrFail($value['variation_id']);

            $product = $variation->product;



            $vld = $variation->variation_location_details
                ->first();

            $variation_qty = !empty($vld) ? $vld->qty_available : 0;
            $multiplier = $this->getMultiplierOf2Units($product->unit_id, $value['unit_id']);

            if ($combo_qty == 0) {
                $combo_qty = ($variation_qty / $multiplier) / $combo_variations[$key]['quantity'];
            } else {
                $combo_qty = min($combo_qty, ($variation_qty / $multiplier) / $combo_variations[$key]['quantity']);
            }
        }

        return floor($combo_qty);
    }

    /**
     * Calculates the quantity of combo products based on
     * the quantity of variation items used.
     *
     * @param int $location_id
     * @param array $combo_variations
     *
     * @return int
     */
    public function calculateComboDetails($location_id, $combo_variations)
    {
        $details = [];

        foreach ($combo_variations as $key => $value) {
            $variation = Variation::with(['product', 'variation_location_details' => function ($q) use ($location_id) {
                $q->where('location_id', $location_id);
            }])->findOrFail($value['variation_id']);

            $vld = $variation->variation_location_details->first();

            $variation_qty = !empty($vld) ? $vld->qty_available : 0;
            $multiplier = $this->getMultiplierOf2Units($variation->product->unit_id, $value['unit_id']);

            $details[] = [
                'variation_id' => $value['variation_id'],
                'product_id' => $variation->product_id,
                'qty_required' => $this->num_uf($value['quantity']) * $multiplier,
            ];
        }

        return $details;
    }

    /**
     * Calculates the total amount of invoice
     *
     * @param array $products
     * @param int $tax_id
     * @param array $discount ['discount_type', 'discount_amount']
     *
     * @return Mixed (false, array)
     */
    public function calculateInvoiceTotal($discount,$products,$products_line, $tax_id,$total,$uf_number = true)
    {

        if (empty($products)) {
            return false;
        }

        $output = ['total_before_tax' => 0, 'tax' => 0, 'final_total' => 0];
        //Sub Total
        foreach ($products as $product) {
            $product_line = $products_line[$product['product_id']];


            /*$unit_price_inc_tax = $uf_number ? $this->num_uf($product_line['unit_price_inc_tax']) : $product_line['unit_price_inc_tax'];*/
            $unit_price_inc_tax = $uf_number ? $this->num_uf($product_line['total']) : $product_line['total'];


            $quantity = $uf_number ? $this->num_uf($product_line['quantity']) : $product_line['quantity'];

            //$output['total_before_tax'] += $quantity * $unit_price_inc_tax;
            $output['total_before_tax'] = $unit_price_inc_tax;


            //Add modifier price to total if exists
            if (!empty($product['modifier_price'])) {
                foreach ($product['modifier_price'] as $key => $modifier_price) {
                    $modifier_price = $uf_number ? $this->num_uf($modifier_price) : $modifier_price;
                    $uf_modifier_price = $uf_number ? $this->num_uf($modifier_price) : $modifier_price;
                    $modifier_qty = isset($product['modifier_quantity'][$key]) ? $product['modifier_quantity'][$key] : 0;
                    $modifier_total = $uf_modifier_price * $modifier_qty;
                    $output['total_before_tax'] += $modifier_total;
                }
            }

        }
        //Calculate discount
        if (is_array($discount)) {
            $discount_amount = $uf_number ? $this->num_uf($discount['discount_amount']) : $discount['discount_amount'];
            if ($discount['discount_type'] == 'fixed') {
                $output['discount'] = $discount_amount;
            } else {
                $output['discount'] = ($discount_amount / 100) * $output['total_before_tax'];
            }
        }

        //Tax
        $output['tax'] = 0;
        if (!empty($tax_id)) {
            $tax_details = TaxRate::find($tax_id);
            if (!empty($tax_details)) {
                $output['tax_id'] = $tax_id;
                $output['tax'] = ($tax_details->amount / 100) * ($output['total_before_tax'] - $output['discount']);
            }
        }
        //Calculate total
        $output['final_total'] = $output['total_before_tax'] + $output['tax'] - $output['discount'];
        return $output;
    }

    /**
     * Generates product sku
     *
     * @param string $string
     *
     * @return generated sku (string)
     */
    public function generateProductSku($string)
    {
        $business_id = request()->session()->get('user.business_id');
        $sku_prefix = Business::where('id', $business_id)->value('sku_prefix');

        return $sku_prefix . str_pad($string, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Gives list of trending products
     *
     * @param int $business_id
     * @param array $filters
     *
     * @return Obj
     */
    public function getTrendingProducts($business_id, $filters = [])
    {
        $query = Transaction::join(
            'transaction_sell_lines as tsl',
            'transactions.id',
            '=',
            'tsl.transaction_id'
        )
            ->join('products as p', 'tsl.product_id', '=', 'p.id')
            ->leftjoin('units as u', 'u.id', '=', 'p.unit_id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', AppConstant::FINAL)
            ->orWhere('transactions.status', AppConstant::PROCESSING)
            ->orWhere('transactions.status', AppConstant::COMPLETED);

        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('transactions.location_id', $permitted_locations);
        }
        if (!empty($filters['location_id'])) {
            $query->where('transactions.location_id', $filters['location_id']);
        }
        if (!empty($filters['category'])) {
            $query->where('p.category_id', $filters['category']);
        }
        if (!empty($filters['sub_category'])) {
            $query->where('p.sub_category_id', $filters['sub_category']);
        }
        if (!empty($filters['brand'])) {
            $query->where('p.brand_id', $filters['brand']);
        }
        if (!empty($filters['unit'])) {
            $query->where('p.unit_id', $filters['unit']);
        }
        if (!empty($filters['limit'])) {
            $query->limit($filters['limit']);
        } else {
            $query->limit(5);
        }

        if (!empty($filters['product_type'])) {
            $query->where('p.type', $filters['product_type']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween(DB::raw('date(transaction_date)'), [$filters['start_date'],
                $filters['end_date']]);
        }

        // $sell_return_query = "(SELECT SUM(TPL.quantity) FROM transactions AS T JOIN purchase_lines AS TPL ON T.id=TPL.transaction_id WHERE TPL.product_id=tsl.product_id AND T.type='sell_return'";
        // if ($permitted_locations != 'all') {
        //     $sell_return_query .= ' AND T.location_id IN ('
        //      . implode(',', $permitted_locations) . ') ';
        // }
        // if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
        //     $sell_return_query .= ' AND date(T.transaction_date) BETWEEN \'' . $filters['start_date'] . '\' AND \'' . $filters['end_date'] . '\'';
        // }
        // $sell_return_query .= ')';

        $products = $query->select(
            DB::raw("(SUM(tsl.quantity) - COALESCE(SUM(tsl.quantity_returned), 0)) as total_unit_sold"),
            'p.name as product',
            'u.short_name as unit',
            'p.sku'
        )->whereNull('tsl.parent_sell_line_id')
            ->groupBy('tsl.product_id')
            ->orderBy('total_unit_sold', 'desc')
            ->get();
        return $products;
    }

    /**
     * Gives list of products based on products id and variation id
     *
     * @param int $business_id
     * @param int $product_id
     * @param int $variation_id = null
     *
     * @return Obj
     */
    public function getDetailsFromProduct($business_id, $product_id, $variation_id = null)
    {
        $product = Product::leftjoin('variations as v', 'products.id', '=', 'v.product_id')
            ->whereNull('v.deleted_at')
            ->where('products.business_id', $business_id);

        if (!is_null($variation_id) && $variation_id !== '0') {
            $product->where('v.id', $variation_id);
        }

        $product->where('products.id', $product_id);

        $products = $product->select(
            'products.id as product_id',
            'products.name as product_name',
            'v.id as variation_id',
            'v.name as variation_name'
        )
            ->get();

        return $products;
    }

    /**
     * F => D (Previous product Increase)
     * D => F (All product decrease)
     * F => F (Newly added product drerease)
     *
     * @param object $transaction_before
     * @param object $transaction
     * @param array $input
     *
     * @return void
     */
    public function adjustProductStockForInvoice($status_before, $transaction, $input, $uf_data = true)
    {
        if ($status_before == AppConstant::FINAL || $status_before == AppConstant::COMPLETED || $status_before == AppConstant::PROCESSING) {
            if($transaction->status == AppConstant::PAYMENT_PENDING){
                foreach ($input['products'] as $product) {
                    if (!empty($product['transaction_sell_lines_id'])) {
                        //$this->updateProductQuantity($input['location_id'], $product['product_id'], $product['variation_id'], $product['quantity'], 0, null, false);

                        //Adjust quantity for combo items.
                        if (isset($product['product_type']) && $product['product_type'] == 'combo') {
                            //Giving quantity in minus will increase the qty
                            foreach ($product['combo'] as $value) {
                                //$this->updateProductQuantity($input['location_id'], $value['product_id'], $value['variation_id'], $value['quantity'], 0, null, false);
                            }

                            // $this->updateEditedSellLineCombo($product['combo'], $input['location_id']);
                        }
                    }
                }
            }
        } elseif ($status_before == AppConstant::PAYMENT_PENDING) {
            if ($transaction->status == AppConstant::FINAL || $transaction->status == AppConstant::COMPLETED || $transaction->status == AppConstant::PROCESSING) {
                foreach ($input['products'] as $product) {
                    $uf_quantity = $uf_data ? $this->num_uf($product['quantity']) : $product['quantity'];
                }
            }
        } elseif ($status_before == AppConstant::FINAL || $status_before == AppConstant::COMPLETED || $status_before == AppConstant::PROCESSING) {
            if ($transaction->status == AppConstant::FINAL || $transaction->status == AppConstant::COMPLETED || $transaction->status == AppConstant::PROCESSING) {
                foreach ($input['products'] as $product) {
                    if (empty($product['transaction_sell_lines_id'])) {
                        $uf_quantity = $uf_data ? $this->num_uf($product['quantity']) : $product['quantity'];

                    }
                }
            }
        }
    }

    /**
     * Updates variation from purchase screen
     *
     * @param array $variation_data
     *
     * @return void
     */
    public function updateProductFromPurchase($variation_data)
    {
        $variation_details = Variation::where('id', $variation_data['variation_id'])
            ->with(['product', 'product.product_tax'])
            ->first();
        $tax_rate = 0;
        if (!empty($variation_details->product->product_tax->amount)) {
            $tax_rate = $variation_details->product->product_tax->amount;
        }

        if (!isset($variation_data['sell_price_inc_tax'])) {
            $variation_data['sell_price_inc_tax'] = $variation_details->sell_price_inc_tax;
        }

        if (/*($variation_details->default_purchase_price != $variation_data['pp_without_discount']) ||*/
            ($variation_details->sell_price_inc_tax != $variation_data['sell_price_inc_tax'])
            ) {
            /*//Set default purchase price exc. tax
            $variation_details->default_purchase_price = $variation_data['pp_without_discount'];

            //Set default purchase price inc. tax
            $variation_details->dpp_inc_tax = $this->calc_percentage($variation_details->default_purchase_price, $tax_rate, $variation_details->default_purchase_price);*/

            //Set default sell price inc. tax
            $variation_details->sell_price_inc_tax = $variation_data['sell_price_inc_tax'];

            //set sell price inc. tax
            $variation_details->default_sell_price = $this->calc_percentage_base($variation_details->sell_price_inc_tax, $tax_rate);

            //set profit margin
            //$variation_details->profit_percent = $this->get_percent($variation_details->default_purchase_price, $variation_details->default_sell_price);

            $variation_details->save();
        }
    }

    public function updateSupplierProductFromPurchase($product_data)
    {
        $supplier_product = SupplierProduct::where('id', $product_data['product_id'])
            ->with(['product_tax'])
            ->first();
        $tax_rate = 0;
        if (!empty($supplier_product->product_tax->amount)) {
            $tax_rate = $supplier_product->product_tax->amount;
        }

        if (($supplier_product->purchase_price != $product_data['pp_without_discount'])) {
            //Set default purchase price exc. tax
            $supplier_product->purchase_price = $product_data['pp_without_discount'];
            //Set default purchase price inc. tax
            $supplier_product->purchase_price_inc_tax = $this->calc_percentage($supplier_product->purchase_price, $tax_rate, $supplier_product->purchase_price);
            $supplier_product->save();
        }

    }

    /**
     * Generated SKU based on the barcode type.
     *
     * @param string $sku
     * @param string $c
     * @param string $barcode_type
     *
     * @return void
     */
    public function generateSubSku($sku, $c, $barcode_type)
    {
        $sub_sku = $sku . $c;

        if (in_array($barcode_type, ['C128', 'C39'])) {
            $sub_sku = $sku . '-' . $c;
        }

        return $sub_sku;
    }

    /**
     * Add rack details.
     *
     * @param int $business_id
     * @param int $product_id
     * @param array $product_racks
     * @param array $product_racks
     *
     * @return void
     */
    public function addRackDetails($business_id, $product_id, $product_racks)
    {
        if (!empty($product_racks)) {
            $data = [];
            foreach ($product_racks as $location_id => $detail) {
                $data[] = ['business_id' => $business_id,
                    'location_id' => $location_id,
                    'product_id' => $product_id,
                    'rack' => !empty($detail['rack']) ? $detail['rack'] : null,
                    'row' => !empty($detail['row']) ? $detail['row'] : null,
                    'position' => !empty($detail['position']) ? $detail['position'] : null,
                    'created_at' => Carbon::now()->toDateTimeString(),
                    'updated_at' => Carbon::now()->toDateTimeString()
                ];
            }

            ProductRack::insert($data);
        }
    }

    /**
     * Get rack details.
     *
     * @param int $business_id
     * @param int $product_id
     *
     * @return void
     */
    public function getRackDetails($business_id, $product_id, $get_location = false)
    {
        $query = ProductRack::where('product_racks.business_id', $business_id)
            ->where('product_id', $product_id);

        if ($get_location) {
            $racks = $query->join('business_locations AS BL', 'product_racks.location_id', '=', 'BL.id')
                ->select(['product_racks.rack',
                    'product_racks.row',
                    'product_racks.position',
                    'BL.name'])
                ->get();
        } else {
            $racks = collect($query->select(['rack', 'row', 'position', 'location_id'])->get());

            $racks = $racks->mapWithKeys(function ($item, $key) {
                return [$item['location_id'] => $item->toArray()];
            })->toArray();
        }

        return $racks;
    }

    /**
     * Update rack details.
     *
     * @param int $business_id
     * @param int $product_id
     * @param array $product_racks
     *
     * @return void
     */
    public function updateRackDetails($business_id, $product_id, $product_racks)
    {
        if (!empty($product_racks)) {
            foreach ($product_racks as $location_id => $details) {
                ProductRack::where('business_id', $business_id)
                    ->where('product_id', $product_id)
                    ->where('location_id', $location_id)
                    ->update(['rack' => !empty($details['rack']) ? $details['rack'] : null,
                        'row' => !empty($details['row']) ? $details['row'] : null,
                        'position' => !empty($details['position']) ? $details['position'] : null
                    ]);
            }
        }
    }

    /**
     * Retrieves selling price group price for a product variation.
     *
     * @param int $variation_id
     * @param int $price_group_id
     * @param int $tax_id
     *
     * @return decimal
     */
    public function getVariationGroupPrice($variation_id, $price_group_id, $tax_id)
    {
        $price_inc_tax =
            VariationGroupPrice::where('variation_id', $variation_id)
                ->where('price_group_id', $price_group_id)
                ->value('price_inc_tax');

        $price_exc_tax = $price_inc_tax;
        if (!empty($price_inc_tax) && !empty($tax_id)) {
            $tax_amount = TaxRate::where('id', $tax_id)->value('amount');
            $price_exc_tax = $this->calc_percentage_base($price_inc_tax, $tax_amount);
        }
        return [
            'price_inc_tax' => $price_inc_tax,
            'price_exc_tax' => $price_exc_tax
        ];
    }

    /**
     * Creates new variation if not exists.
     *
     * @param int $business_id
     * @param string $name
     *
     * @return obj
     */
    public function createOrNewVariation($business_id, $name)
    {
        $variation = VariationTemplate::where('business_id', $business_id)
            ->where('name', 'like', $name)
            ->with(['values'])
            ->first();

        if (empty($variation)) {
            $variation = VariationTemplate::create([
                'business_id' => $business_id,
                'name' => $name
            ]);
        }
        return $variation;
    }

    /**
     * Adds opening stock to a single product.
     *
     * @param int $business_id
     * @param obj $product
     * @param array $input
     * @param obj $transaction_date
     * @param int $user_id
     *
     * @return void
     */
    public function addSingleProductOpeningStock($business_id, $product, $input, $transaction_date, $user_id)
    {
        $locations = BusinessLocation::forDropdown($business_id)->toArray();

        $tax_percent = !empty($product->product_tax->amount) ? $product->product_tax->amount : 0;
        $tax_id = !empty($product->product_tax->id) ? $product->product_tax->id : null;

        foreach ($input as $key => $value) {
            $location_id = $key;
            $purchase_total = 0;
            //Check if valid location
            if (array_key_exists($location_id, $locations)) {
                $purchase_lines = [];

                $purchase_price = $this->num_uf(trim($value['purchase_price']));
                $item_tax = $this->calc_percentage($purchase_price, $tax_percent);
                $purchase_price_inc_tax = $purchase_price + $item_tax;
                $qty = $this->num_uf(trim($value['quantity']));

                $exp_date = null;
                if (!empty($value['exp_date'])) {
                    $exp_date = Carbon::createFromFormat('d-m-Y', $value['exp_date'])->format('Y-m-d');
                }

                $lot_number = null;
                if (!empty($value['lot_number'])) {
                    $lot_number = $value['lot_number'];
                }

                if ($qty > 0) {
                    $qty_formated = $this->num_f($qty);
                    //Calculate transaction total
                    $purchase_total += ($purchase_price_inc_tax * $qty);
                    $variation_id = $product->variations->first()->id;

                    $purchase_line = new PurchaseLine();
                    $purchase_line->product_id = $product->id;
                    $purchase_line->variation_id = $variation_id;
                    $purchase_line->item_tax = $item_tax;
                    $purchase_line->tax_id = $tax_id;
                    $purchase_line->quantity = $qty;
                    $purchase_line->pp_without_discount = $purchase_price;
                    $purchase_line->purchase_price = $purchase_price;
                    $purchase_line->purchase_price_inc_tax = $purchase_price_inc_tax;
                    $purchase_line->exp_date = $exp_date;
                    $purchase_line->lot_number = $lot_number;
                    $purchase_lines[] = $purchase_line;

                    //$this->updateProductQuantity($location_id, $product->id, $variation_id, $qty_formated);
                }

                //create transaction & purchase lines
                if (!empty($purchase_lines)) {
                    $transaction = Transaction::create(
                        [
                            'type' => 'opening_stock',
                            'opening_stock_product_id' => $product->id,
                            'status' => 'received',
                            'business_id' => $business_id,
                            'transaction_date' => $transaction_date,
                            'total_before_tax' => $purchase_total,
                            'location_id' => $location_id,
                            'final_total' => $purchase_total,
                            'payment_status' => 'paid',
                            'created_by' => $user_id
                        ]
                    );
                    $transaction->purchase_lines()->saveMany($purchase_lines);
                }
            }
        }
    }

    /**
     * Add/Edit transaction purchase lines
     *
     * @param object $transaction
     * @param array $input_data
     * @param array $currency_details
     * @param boolean $enable_product_editing
     * @param string $before_status = null
     *
     * @return array
     */
    public function createOrUpdatePurchaseLines($transaction, $input_data, $currency_details, $enable_product_editing, $before_status = null)
    {
        $updated_purchase_lines = [];
        $updated_purchase_line_ids = [0];
        $exchange_rate = !empty($transaction->exchange_rate) ? $transaction->exchange_rate : 1;

        foreach ($input_data as $data) {
            $multiplier = 1;
            if (isset($data['sub_unit_id']) && $data['sub_unit_id'] == $data['product_unit_id']) {
                unset($data['sub_unit_id']);
            }

            if (!empty($data['sub_unit_id'])) {
                $unit = Unit::find($data['sub_unit_id']);
                $multiplier = !empty($unit->base_unit_multiplier) ? $unit->base_unit_multiplier : 1;
            }
            $new_quantity = $this->num_uf($data['quantity']) * $multiplier;

            $new_quantity_f = $this->num_f($new_quantity);
            $old_qty = 0;
            //update existing purchase line
            if (isset($data['purchase_line_id'])) {
                $purchase_line = PurchaseLine::findOrFail($data['purchase_line_id']);
                $updated_purchase_line_ids[] = $purchase_line->id;
                $old_qty = $purchase_line->quantity;

                $this->updateProductStock($before_status, $transaction, $data['product_id'], $data['variation_id'], $new_quantity, $purchase_line->quantity, $currency_details);
            } else {
                //create newly added purchase lines
                $purchase_line = new PurchaseLine();
                $purchase_line->product_id = $data['product_id'];
                $purchase_line->variation_id = $data['variation_id'];

                //Increase quantity only if status is received
                if ($transaction->status == 'received') {
                    //$this->updateProductQuantity($transaction->location_id, $data['product_id'], $data['variation_id'], $new_quantity_f, 0, $currency_details);
                }
            }

            $purchase_line->quantity = $new_quantity;
            $purchase_line->pp_without_discount = ($this->num_uf($data['pp_without_discount'], $currency_details) * $exchange_rate) / $multiplier;
            $purchase_line->discount_percent = $this->num_uf($data['discount_percent'], $currency_details);
            $purchase_line->purchase_price = ($this->num_uf($data['purchase_price'], $currency_details) * $exchange_rate) / $multiplier;
            $purchase_line->purchase_price_inc_tax = ($this->num_uf($data['purchase_price_inc_tax'], $currency_details) * $exchange_rate) / $multiplier;
            $purchase_line->item_tax = ($this->num_uf($data['item_tax'], $currency_details) * $exchange_rate) / $multiplier;
            $purchase_line->tax_id = $data['purchase_line_tax_id'];
            $purchase_line->lot_number = !empty($data['lot_number']) ? $data['lot_number'] : null;
            $purchase_line->mfg_date = !empty($data['mfg_date']) ? $this->uf_date($data['mfg_date']) : null;
            $purchase_line->exp_date = !empty($data['exp_date']) ? $this->uf_date($data['exp_date']) : null;
            $purchase_line->sub_unit_id = !empty($data['sub_unit_id']) ? $data['sub_unit_id'] : null;
            $purchase_line->purchase_order_line_id = !empty($data['purchase_order_line_id']) ? $data['purchase_order_line_id'] : null;

            $updated_purchase_lines[] = $purchase_line;

            //Edit product price
            if ($enable_product_editing == 1) {
                if (isset($data['default_sell_price'])) {
                    $variation_data['sell_price_inc_tax'] = ($this->num_uf($data['default_sell_price'], $currency_details)) / $multiplier;
                }
                $variation_data['pp_without_discount'] = ($this->num_uf($data['pp_without_discount'], $currency_details) * $exchange_rate) / $multiplier;
                $variation_data['variation_id'] = $purchase_line->variation_id;
                $variation_data['purchase_price'] = $purchase_line->purchase_price;

                $this->updateProductFromPurchase($variation_data);
            }

            //Update purchase order line quantity received
            $this->updatePurchaseOrderLine($purchase_line->purchase_order_line_id, $purchase_line->quantity, $old_qty);
        }

        //unset deleted purchase lines
        $delete_purchase_line_ids = [];
        $delete_purchase_lines = null;
        if (!empty($updated_purchase_line_ids)) {
            $delete_purchase_lines = PurchaseLine::where('transaction_id', $transaction->id)
                ->whereNotIn('id', $updated_purchase_line_ids)
                ->get();

            if ($delete_purchase_lines->count()) {
                foreach ($delete_purchase_lines as $delete_purchase_line) {
                    $delete_purchase_line_ids[] = $delete_purchase_line->id;

                    //If purchase order line set decrease quntity
                    if (!empty($delete_purchase_line->purchase_order_line_id)) {
                        $this->updatePurchaseOrderLine($delete_purchase_line->purchase_order_line_id, 0, $delete_purchase_line->quantity);
                    }
                }

                //unset if purchase order line from purchase lines if exists
                if ($transaction->type == 'purchase_order') {
                    PurchaseLine::whereIn('purchase_order_line_id', $delete_purchase_line_ids)
                        ->update(['purchase_order_line_id' => null]);
                }

                //Delete deleted purchase lines
                PurchaseLine::where('transaction_id', $transaction->id)
                    ->whereIn('id', $delete_purchase_line_ids)
                    ->delete();
            }
        }

        //update purchase lines
        if (!empty($updated_purchase_lines)) {
            $transaction->purchase_lines()->saveMany($updated_purchase_lines);
        }

        return $delete_purchase_lines;
    }

    /**
     * Add/Edit Supplier transaction purchase lines
     *
     * @param object $supplier_transaction
     * @param array $input_data
     * @param array $currency_details
     * @param boolean $enable_product_editing
     * @param string $before_status = null
     *
     * @return array
     */
    public function createOrUpdateSupplierPurchaseLines($supplier_transaction, $input_data, $currency_details, $enable_product_editing, $before_status = null)
    {
        $updated_purchase_lines = [];
        $updated_purchase_line_ids = [0];
        $exchange_rate = !empty($supplier_transaction->exchange_rate) ? $supplier_transaction->exchange_rate : 1;
        foreach ($input_data as $data) {
            $multiplier = 1;
            if (isset($data['sub_unit_id']) && $data['sub_unit_id'] == $data['product_unit_id']) {
                unset($data['sub_unit_id']);
            }

            if (!empty($data['sub_unit_id'])) {
                $unit = Unit::find($data['sub_unit_id']);
                $multiplier = !empty($unit->base_unit_multiplier) ? $unit->base_unit_multiplier : 1;
            }
            $new_quantity = $this->num_uf($data['quantity']) * $multiplier;

            $new_quantity_f = $this->num_f($new_quantity);
            $old_qty = 0;
            //update existing supplier purchase line
            if (isset($data['purchase_line_id'])) {
                $supplier_purchase_line = SupplierPurchaseLine::findOrFail($data['purchase_line_id']);
                $updated_purchase_line_ids[] = $supplier_purchase_line->id;
                $old_qty = $supplier_purchase_line->quantity;

                $this->updateSupplierProductStock($before_status, $supplier_transaction, $data['product_id'], $new_quantity, $supplier_purchase_line->quantity, $currency_details);
            } else {
                //create newly added supplier purchase lines
                $supplier_purchase_line = new SupplierPurchaseLine();

                $supplier_purchase_line->product_id = $data['product_id'];

                //Increase quantity only if status is received
                if ($supplier_transaction->status == 'received') {
                $this->updateSupplierProductQuantity($supplier_transaction->location_id, $data['product_id'], $new_quantity_f, 0, $currency_details);
                }
            }

            $supplier_purchase_line->quantity = $new_quantity;
            $supplier_purchase_line->pp_without_discount = ($this->num_uf($data['pp_without_discount'], $currency_details) * $exchange_rate) / $multiplier;
            $supplier_purchase_line->discount_percent = $this->num_uf($data['discount_percent'], $currency_details);
            $supplier_purchase_line->purchase_price = ($this->num_uf($data['purchase_price'], $currency_details) * $exchange_rate) / $multiplier;
            $supplier_purchase_line->purchase_price_inc_tax = ($this->num_uf($data['purchase_price_inc_tax'], $currency_details) * $exchange_rate) / $multiplier;
            $supplier_purchase_line->item_tax = ($this->num_uf($data['item_tax'], $currency_details) * $exchange_rate) / $multiplier;
            $supplier_purchase_line->tax_id = $data['purchase_line_tax_id'];
            $supplier_purchase_line->lot_number = !empty($data['lot_number']) ? $data['lot_number'] : null;
            $supplier_purchase_line->mfg_date = !empty($data['mfg_date']) ? $this->uf_date($data['mfg_date']) : null;
            $supplier_purchase_line->exp_date = !empty($data['exp_date']) ? $this->uf_date($data['exp_date']) : null;
            $supplier_purchase_line->sub_unit_id = !empty($data['sub_unit_id']) ? $data['sub_unit_id'] : null;
            $supplier_purchase_line->purchase_order_line_id = !empty($data['purchase_order_line_id']) ? $data['purchase_order_line_id'] : null;

            $updated_purchase_lines[] = $supplier_purchase_line;

            //Edit product price
            if ($enable_product_editing == 1) {
                if (isset($data['default_sell_price'])) {
                    $product_data['sell_price_inc_tax'] = ($this->num_uf($data['default_sell_price'], $currency_details)) / $multiplier;
                }
                $product_data['product_id'] = $supplier_purchase_line->product_id;
                $product_data['purchase_price'] = $supplier_purchase_line->purchase_price;
                $product_data['pp_without_discount'] = $supplier_purchase_line->pp_without_discount;

                $this->updateSupplierProductFromPurchase($product_data);
            }
            //Update purchase order line quantity received
            $this->updateSupplierPurchaseOrderLine($supplier_purchase_line->purchase_order_line_id, $supplier_purchase_line->quantity, $old_qty);
        }

        //unset deleted purchase lines
        $delete_purchase_line_ids = [];
        $delete_purchase_lines = null;
        if (!empty($updated_purchase_line_ids)) {
            $delete_purchase_lines = SupplierPurchaseLine::where('supplier_transactions_id', $supplier_transaction->id)
                ->whereNotIn('id', $updated_purchase_line_ids)
                ->get();

            if ($delete_purchase_lines->count()) {
                foreach ($delete_purchase_lines as $delete_purchase_line) {
                    $delete_purchase_line_ids[] = $delete_purchase_line->id;


                    //If purchase order line set decrease quntity
                    if (!empty($delete_purchase_line->purchase_order_line_id)) {
                        $this->updateSupplierPurchaseOrderLine($delete_purchase_line->purchase_order_line_id, 0, $delete_purchase_line->quantity);
                    }
                }

                //unset if purchase order line from purchase lines if exists
                if ($supplier_transaction->type == 'purchase_order') {
                    SupplierPurchaseLine::whereIn('purchase_order_line_id', $delete_purchase_line_ids)
                        ->update(['purchase_order_line_id' => null]);
                }

                //Delete deleted purchase lines
                SupplierPurchaseLine::where('supplier_transactions_id', $supplier_transaction->id)
                    ->whereIn('id', $delete_purchase_line_ids)
                    ->delete();
            }
        }

        //update purchase lines
        if (!empty($updated_purchase_lines)) {
            $supplier_transaction->supplierPurchaseLines()->saveMany($updated_purchase_lines);
        }

        return $delete_purchase_lines;
    }

    public function updatePurchaseOrderLine($purchase_order_line_id, $new_qty, $old_qty = 0)
    {
        $diff = $new_qty - $old_qty;
        if (!empty($purchase_order_line_id) && !empty($diff)) {
            $purchase_order_line = PurchaseLine::find($purchase_order_line_id);
            $purchase_order_line->po_quantity_purchased += ($diff);
            $purchase_order_line->save();
        }
    }

    public function updateSupplierPurchaseOrderLine($purchase_order_line_id, $new_qty, $old_qty = 0)
    {
        $diff = $new_qty - $old_qty;
        if (!empty($purchase_order_line_id) && !empty($diff)) {
            $purchase_order_line = SupplierPurchaseLine::find($purchase_order_line_id);
            $purchase_order_line->po_quantity_purchased += ($diff);
            $purchase_order_line->save();
        }
    }

    /**
     * Updates product stock after adding or updating purchase
     *
     * @param string $status_before
     * @param obj $transaction
     * @param integer $product_id
     * @param integer $variation_id
     * @param decimal $new_quantity in database format
     * @param decimal $old_quantity in database format
     * @param array $currency_details
     *
     */
    public function updateProductStock($status_before, $transaction, $product_id, $variation_id, $new_quantity, $old_quantity, $currency_details)
    {
        $new_quantity_f = $this->num_f($new_quantity);
        $old_qty = $this->num_f($old_quantity);
        //Update quantity for existing products
        if ($status_before == 'received' && $transaction->status == 'received') {
            //if status received update existing quantity
            //$this->updateProductQuantity($transaction->location_id, $product_id, $variation_id, $new_quantity_f, $old_qty, $currency_details);
        } elseif ($status_before == 'received' && $transaction->status != 'received') {

        } elseif ($status_before != 'received' && $transaction->status == 'received') {
            //$this->updateProductQuantity($transaction->location_id, $product_id, $variation_id, $new_quantity_f, 0, $currency_details);
        }
    }

    public function updateSupplierProductStock($status_before, $transaction, $product_id, $new_quantity, $old_quantity, $currency_details)
    {
        $new_quantity_f = $this->num_f($new_quantity);
        $old_qty = $this->num_f($old_quantity);
        //Update quantity for existing products
        if ($status_before == 'received' && $transaction->status == 'received') {
            //if status received update existing quantity
            $this->updateSupplierProductQuantity($transaction->location_id, $product_id, $new_quantity_f, $old_qty, $currency_details,false);
        } elseif ($status_before == 'received' && $transaction->status != 'received') {
            //decrease quantity only if status changed from received to not received
            $this->decreaseSupplierProductQuantity(
                $product_id,
                $transaction->location_id,
                $old_quantity
            );
        } elseif ($status_before != 'received' && $transaction->status == 'received') {
            Log::info('test');
            $this->updateSupplierProductQuantity($transaction->location_id, $product_id, $new_quantity_f, 0, $currency_details);
        }
    }
    /**
     * Recalculates purchase line data according to subunit data
     *
     * @param integer $purchase_line
     * @param integer $business_id
     *
     * @return array
     */
    public function changePurchaseLineUnit($purchase_line, $business_id)
    {
        $base_unit = $purchase_line->product->unit;
        $sub_units = $base_unit->sub_units;

        $sub_unit_id = $purchase_line->sub_unit_id;

        $sub_unit = $sub_units->filter(function ($item) use ($sub_unit_id) {
            return $item->id == $sub_unit_id;
        })->first();

        if (!empty($sub_unit)) {
            $multiplier = $sub_unit->base_unit_multiplier;
            $purchase_line->quantity = $purchase_line->quantity / $multiplier;
            $purchase_line->pp_without_discount = $purchase_line->pp_without_discount * $multiplier;
            $purchase_line->purchase_price = $purchase_line->purchase_price * $multiplier;
            $purchase_line->purchase_price_inc_tax = $purchase_line->purchase_price_inc_tax * $multiplier;
            $purchase_line->item_tax = $purchase_line->item_tax * $multiplier;
            $purchase_line->quantity_returned = $purchase_line->quantity_returned / $multiplier;
            $purchase_line->quantity_sold = $purchase_line->quantity_sold / $multiplier;
            $purchase_line->quantity_adjusted = $purchase_line->quantity_adjusted / $multiplier;
        }

        //SubUnits
        $purchase_line->sub_units_options = $this->getSubUnits($business_id, $base_unit->id, false, $purchase_line->product_id);

        return $purchase_line;
    }

    /**
     * Recalculates sell line data according to subunit data
     *
     * @param integer $unit_id
     *
     * @return array
     */
    public function changeSellLineUnit($business_id, $sell_line)
    {

        $unit_details = $this->getSubUnits($business_id, $sell_line->sub_unit_id, false, $sell_line->product_id);
        $sub_unit = null;
        $sub_unit_id = $sell_line->sub_unit_id;
        foreach ($unit_details as $key => $value) {
            if ($key == $sub_unit_id) {
                $sub_unit = $value;
            }
        }

        if (!empty($sub_unit)) {
            $multiplier = $sub_unit['multiplier'];
            $sell_line->quantity = $sell_line->quantity / $multiplier;
            $sell_line->item_tax = $sell_line->item_tax * $multiplier;
            $sell_line->default_sell_price = $sell_line->default_sell_price * $multiplier;
            $sell_line->unit_price_before_discount = $sell_line->unit_price_before_discount * $multiplier;
            $sell_line->sell_price_inc_tax = $sell_line->sell_price_inc_tax * $multiplier;
            $sell_line->sub_unit_multiplier = $multiplier;

            $sell_line->unit_details = $unit_details;
        }
        return $sell_line;
    }

    /**
     * Retrieves current stock of a variation for the given location
     *
     * @param int $variation_id , int location_id
     *
     * @return float
     */
    public function getCurrentStock($variation_id, $location_id)
    {
        $current_stock = VariationLocationDetails::where('variation_id', $variation_id)
            ->where('location_id', $location_id)
            ->value('qty_available');

        if (null == $current_stock) {
            $current_stock = 0;
        }

        return $current_stock;
    }

    /**
     * Adjusts stock over selling with purchases, opening stocks andstock transfers
     * Also maps with respective sells
     *
     * @param obj $transaction
     *
     * @return void
     */
    public function adjustStockOverSelling($transaction)
    {
        if ($transaction->status != 'received') {
            return false;
        }

        foreach ($transaction->purchase_lines as $purchase_line) {
            if ($purchase_line->product->enable_stock == 1) {

                //Available quantity in the purchase line
                $purchase_line_qty_avlbl = $purchase_line->quantity_remaining;

                if ($purchase_line_qty_avlbl <= 0) {
                    continue;
                }

                //update sell line purchase line mapping
                $sell_line_purchase_lines =
                    TransactionSellLinesPurchaseLines::where('purchase_line_id', 0)
                        ->join('transaction_sell_lines as tsl', 'tsl.id', '=', 'transaction_sell_lines_purchase_lines.sell_line_id')
                        ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
                        ->where('t.location_id', $transaction->location_id)
                        ->where('tsl.variation_id', $purchase_line->variation_id)
                        ->where('tsl.product_id', $purchase_line->product_id)
                        ->select('transaction_sell_lines_purchase_lines.*')
                        ->get();

                foreach ($sell_line_purchase_lines as $slpl) {
                    if ($purchase_line_qty_avlbl > 0) {
                        if ($slpl->quantity <= $purchase_line_qty_avlbl) {
                            $purchase_line_qty_avlbl -= $slpl->quantity;
                            $slpl->purchase_line_id = $purchase_line->id;
                            $slpl->save();
                            //update purchase line quantity sold
                            $purchase_line->quantity_sold += $slpl->quantity;
                            $purchase_line->save();
                        } else {
                            $diff = $slpl->quantity - $purchase_line_qty_avlbl;
                            $slpl->purchase_line_id = $purchase_line->id;
                            $slpl->quantity = $purchase_line_qty_avlbl;
                            $slpl->save();

                            //update purchase line quantity sold
                            $purchase_line->quantity_sold += $slpl->quantity;
                            $purchase_line->save();

                            TransactionSellLinesPurchaseLines::create([
                                'sell_line_id' => $slpl->sell_line_id,
                                'purchase_line_id' => 0,
                                'quantity' => $diff
                            ]);
                            break;
                        }
                    }
                }
            }
        }
    }

    public function adjustSupplierProductStockOverSelling($transaction)
    {
        if ($transaction->status != 'received') {
            return false;
        }
        foreach ($transaction->supplierPurchaseLines as $purchase_line) {
            // if ($purchase_line->product->enable_stock == 1) {
        //Available quantity in the purchase line
                $purchase_line_qty_avlbl = $purchase_line->getQuantityRemainingAttribute();
                if ($purchase_line_qty_avlbl <= 0) {
                    continue;
                }

                //update sell line purchase line mapping
                $sell_line_purchase_lines = SupplierTransactionSellLinesPurchaseLines::where('purchase_line_id', $purchase_line->id)
                ->join('supplier_transaction_sell_lines as stsl', 'stsl.id', '=', 'supplier_transaction_sell_lines_purchase_lines.sell_line_id')
                ->join('supplier_transactions as st', 'stsl.supplier_transaction_id', '=', 'st.id')
                ->where('st.location_id', $transaction->location_id)
                ->where('stsl.product_id', $purchase_line->product_id)
                ->select('supplier_transaction_sell_lines_purchase_lines.*')
                ->get();

                foreach ($sell_line_purchase_lines as $slpl) {
                    if ($purchase_line_qty_avlbl > 0) {
                        if ($slpl->quantity <= $purchase_line_qty_avlbl) {
                            $purchase_line_qty_avlbl -= $slpl->quantity;
                            $slpl->purchase_line_id = $purchase_line->id;
                            $slpl->save();
                            //update purchase line quantity sold
                            $purchase_line->quantity_sold += $slpl->quantity;
                            $purchase_line->save();
                        } else {
                            $diff = $slpl->quantity - $purchase_line_qty_avlbl;
                            $slpl->purchase_line_id = $purchase_line->id;
                            $slpl->quantity = $purchase_line_qty_avlbl;
                            $slpl->save();

                            //update purchase line quantity sold
                            $purchase_line->quantity_sold += $slpl->quantity;
                            $purchase_line->save();

                            SupplierTransactionSellLinesPurchaseLines::create([
                                'sell_line_id' => $slpl->sell_line_id,
                                'purchase_line_id' => 0,
                                'quantity' => $diff
                            ]);
                            break;
                        }
                    }
                }
            // }
        }
    }

    /**
     * Finds out most relevant descount for the product
     *
     * @param obj $product , int $business_id, int $location_id, bool $is_cg,
     * bool $is_spg
     *
     * @return obj discount
     */
    public function getProductDiscount($product, $business_id, $location_id, $is_cg = false, $price_group = null, $variation_id = null)
    {
        $now = Carbon::now()->toDateTimeString();

        //Search if both category and brand matches
        $query = Discount::where('business_id', $business_id)
            ->where('location_id', $location_id)
            ->where('is_active', 1)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->where(function ($q) use ($product, $variation_id) {
                $q->where(function ($sub_q) use ($product) {
                    if (!empty($product->brand_id)) {
                        $sub_q->where('brand_id', $product->brand_id);
                    }
                    if (!empty($product->category_id)) {
                        $sub_q->where('category_id', $product->category_id);
                    }
                })
                    ->orWhere(function ($sub_q) use ($product) {
                        $sub_q->whereRaw('(brand_id="' . $product->brand_id . '" AND category_id IS NULL)')
                            ->orWhereRaw('(category_id="' . $product->category_id . '" AND brand_id IS NULL)');
                    });

                if (!empty($variation_id)) {
                    $q->orWhereHas('variations', function ($sub_q) use ($variation_id) {
                        $sub_q->where('variation_id', $variation_id);
                    });
                }
            })
            ->orderBy('priority', 'desc')
            ->latest();
        if ($is_cg) {
            $query->where('applicable_in_cg', 1);
        }
        if (!is_null($price_group)) {
            $query->where(function ($q) use ($price_group) {
                $q->whereNull('spg')
                    ->orWhere('spg', (string)$price_group);
            });
        } else {
            $query->whereNull('spg');
        }

        $discount = $query->first();

        if (!empty($discount)) {
            $discount->formated_starts_at = $this->format_date($discount->starts_at->toDateTimeString(), true);
            $discount->formated_ends_at = $this->format_date($discount->ends_at->toDateTimeString(), true);
        }

        return $discount;
    }

    /**
     * Finds out most relevant descount for the product
     *
     * @param obj $product , int $business_id, int $location_id, bool $is_cg,
     * bool $is_spg
     *
     * @return obj discount
     */
    public function getSellProductDiscount($products, $business_id, $location_id, $is_cg = false, $price_group = null, $variation_id = null)
    {
        $now = Carbon::now()->toDateTimeString();
        $discount = '';
        foreach ($products as $product) {
            //Search if both category and brand matches
            $query = Discount::where('business_id', $business_id)
                ->where('location_id', $location_id)
                ->where('is_active', 1)
                ->where('starts_at', '<=', $now)
                ->where('ends_at', '>=', $now)
                ->where(function ($q) use ($product, $variation_id) {
                    $q->where(function ($sub_q) use ($product) {
                        if (!empty($product->product->brand_id)) {
                            $sub_q->where('brand_id', $product->product->brand_id);
                        }
                        if (!empty($product->product->category_id)) {
                            $sub_q->where('category_id', $product->product->category_id);
                        }
                    })
                        ->orWhere(function ($sub_q) use ($product) {
                            $sub_q->whereRaw('(brand_id="' . $product->product->brand_id . '" AND category_id IS NULL)')
                                ->orWhereRaw('(category_id="' . $product->product->category_id . '" AND brand_id IS NULL)');
                        });

                    if (!empty($variation_id)) {
                        $q->orWhereHas('variations', function ($sub_q) use ($variation_id) {
                            $sub_q->where('variation_id', $variation_id);
                        });
                    }
                })
                ->orderBy('priority', 'desc')
                ->latest();
            if ($is_cg) {
                $query->where('applicable_in_cg', 1);
            }
            if (!is_null($price_group)) {
                $query->where(function ($q) use ($price_group) {
                    $q->whereNull('spg')
                        ->orWhere('spg', (string)$price_group);
                });
            } else {
                $query->whereNull('spg');
            }

            $discount = $query->first();

            if (!empty($discount)) {
                $discount->formated_starts_at = $this->format_date($discount->starts_at->toDateTimeString(), true);
                $discount->formated_ends_at = $this->format_date($discount->ends_at->toDateTimeString(), true);
            }

        }
        return $discount;
    }

    /**
     * Filters product as per the given inputs and return the details.
     *
     * @param string $search_type (like or exact)
     *
     * @return object
     */
    public function filterProduct($business_id, $search_term, $location_id = null, $not_for_selling = null, $price_group_id = null, $product_types = [], $search_fields = [], $check_qty = false, $search_type = 'like')
    {

        $query = Product::join('variations', 'products.id', '=', 'variations.product_id')
            ->active()
            ->whereNull('variations.deleted_at')
            ->leftjoin('units as U', 'products.unit_id', '=', 'U.id')
            ->leftjoin(
                'variation_location_details AS VLD',
                function ($join) use ($location_id) {
                    $join->on('variations.id', '=', 'VLD.variation_id');

                    //Include Location
                    if (!empty($location_id)) {
                        $join->where(function ($query) use ($location_id) {
                            $query->where('VLD.location_id', '=', $location_id);
                            //Check null to show products even if no quantity is available in a location.
                            //TODO: Maybe add a settings to show product not available at a location or not.
                            $query->orWhereNull('VLD.location_id');
                        });;
                    }
                }
            );



        if (!empty($price_group_id)) {
            $query->leftjoin(
                'variation_group_prices AS VGP',
                function ($join) use ($price_group_id) {
                    $join->on('variations.id', '=', 'VGP.variation_id')
                        ->where('VGP.price_group_id', '=', $price_group_id);
                }
            );
        }

        $query->where('products.business_id', $business_id)
            ->where('products.type', '!=', 'modifier');

        if (!empty($product_types)) {
            $query->whereIn('products.type', $product_types);
        }

        if (in_array('lot', $search_fields)) {
            $query->leftjoin('purchase_lines as pl', 'variations.id', '=', 'pl.variation_id');
        }

        //Include search
        if (!empty($search_term)) {

            //Search with like condition
            if ($search_type == 'like') {
                $query->where(function ($query) use ($search_term, $search_fields) {

                    if (in_array('name', $search_fields)) {
                        $query->where('products.name', 'like', '%' . $search_term . '%');
                    }

                    if (in_array('sku', $search_fields)) {
                        $query->orWhere('sku', 'like', '%' . $search_term . '%');
                    }

                    if (in_array('sub_sku', $search_fields)) {
                        $query->orWhere('sub_sku', 'like', '%' . $search_term . '%');
                    }

                    if (in_array('lot', $search_fields)) {
                        $query->orWhere('pl.lot_number', 'like', '%' . $search_term . '%');
                    }
                });
            }

            //Search with exact condition
            if ($search_type == 'exact') {
                $query->where(function ($query) use ($search_term, $search_fields) {

                    if (in_array('name', $search_fields)) {
                        $query->where('products.name', $search_term);
                    }

                    if (in_array('sku', $search_fields)) {
                        $query->orWhere('sku', $search_term);
                    }

                    if (in_array('sub_sku', $search_fields)) {
                        $query->orWhere('sub_sku', $search_term);
                    }

                    if (in_array('lot', $search_fields)) {
                        $query->orWhere('pl.lot_number', $search_term);
                    }
                });
            }
        }


        //Include check for quantity
        if ($check_qty) {
            $query->where('VLD.qty_available', '>', 0);
        }

        if (!empty($location_id)) {
            $query->ForLocation($location_id);
        }

        $query->select(
            'products.id as product_id',
            'products.name',
            'products.type',
            'variations.id as variation_id',
            'variations.name as variation',
            'VLD.qty_available',
            'variations.sell_price_inc_tax as selling_price',
            'variations.sub_sku',
            'U.short_name as unit',
        );

        if (!empty($price_group_id)) {
            $query->addSelect('VGP.price_inc_tax as variation_group_price');
        }

        if (in_array('lot', $search_fields)) {
            $query->addSelect('pl.id as purchase_line_id', 'pl.lot_number');
        }

        $query->groupBy('variations.id');
        return $query->orderBy('VLD.qty_available', 'desc')
            ->get();
    }


    public function filterSupplierProduct($business_id, $search_term, $location_id = null, $search_fields = [], $check_qty = false, $search_type = 'like')
    {
        $query = SupplierProduct::whereNull('deleted_at')
        ->leftJoin('supplier_product_units','supplier_products.unit_id','=','supplier_product_units.id')
        ->leftJoin('supplier_product_location_details',
        function($join) use($location_id){
            $join->on('supplier_products.id','=','supplier_product_location_details.product_id');
             //Include Location
             if (!empty($location_id)) {
                $join->where(function ($query) use ($location_id) {
                    $query->where('supplier_product_location_details.location_id', '=', $location_id);
                    //Check null to show products even if no quantity is available in a location.
                    //TODO: Maybe add a settings to show product not available at a location or not.
                    $query->orWhereNull('supplier_product_location_details.location_id');
                });;
            }
        });

        // //Include search
        if (!empty($search_term)) {

            //Search with like condition
            if ($search_type == 'like') {
                $query->where(function ($query) use ($search_term, $search_fields) {

                    if (in_array('name', $search_fields)) {
                        $query->where('supplier_products.name', 'like', '%' . $search_term . '%');
                    }

                    if (in_array('sku', $search_fields)) {
                        $query->orWhere('supplier_products.sku', 'like', '%' . $search_term . '%');
                    }

                });
            }

        //     //Search with exact condition
            if ($search_type == 'exact') {
                $query->where(function ($query) use ($search_term, $search_fields) {

                    if (in_array('name', $search_fields)) {
                        $query->where('products.name', $search_term);
                    }

                    if (in_array('sku', $search_fields)) {
                        $query->orWhere('sku', $search_term);
                    }
                });
            }
        }


        $query->select(
            'supplier_products.id as product_id',
            'supplier_products.name',
            'supplier_product_location_details.qty_available',
            'supplier_products.purchase_price_inc_tax as selling_price',
            'supplier_products.sku',
            'supplier_product_units.short_name as unit',
        );
        $query->groupBy('supplier_products.id');
        $supplier_products =  $query->orderBy('supplier_product_location_details.qty_available', 'desc')
        ->get();
        Log::info($supplier_products);
        return $supplier_products;
    }

    /**
     * Gives the details of combo product
     *
     * @param array $combo_variations
     * @param int $business_id
     *
     * @return array
     */
    public function __getComboProductDetails($combo_variations, $business_id)
    {
        foreach ($combo_variations as $key => $value) {
            $combo_variations[$key]['variation'] =
                Variation::with(['product'])
                    ->find($value['variation_id']);

            $combo_variations[$key]['sub_units'] = $this->getSubUnits($business_id, $combo_variations[$key]['variation']['product']->unit_id, true);

            $combo_variations[$key]['multiplier'] = 1;

            if (!empty($combo_variations[$key]['sub_units'])) {
                if (isset($combo_variations[$key]['sub_units'][$combo_variations[$key]['unit_id']])) {
                    $combo_variations[$key]['multiplier'] = $combo_variations[$key]['sub_units'][$combo_variations[$key]['unit_id']]['multiplier'];
                    $combo_variations[$key]['unit_name'] = $combo_variations[$key]['sub_units'][$combo_variations[$key]['unit_id']]['name'];
                }
            }
        }

        return $combo_variations;
    }

    public function getVariationStockDetails($business_id, $variation_id, $location_id)
    {
        $purchase_details = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
            ->join('units', 'p.unit_id', '=', 'units.id')
            ->leftjoin('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
            ->leftjoin('purchase_lines as pl', 'pl.variation_id', '=', 'variations.id')
            ->leftjoin('transactions as t', 'pl.transaction_id', '=', 't.id')
            ->where('t.location_id', $location_id)
            //->where('t.status', 'received')
            ->where('p.business_id', $business_id)
            ->where('variations.id', $variation_id)
            ->select(
                DB::raw("SUM(IF(t.type='purchase' AND t.status='received', pl.quantity, 0)) as total_purchase"),
                DB::raw("SUM(IF(t.type='purchase' OR t.type='purchase_return', pl.quantity_returned, 0)) as total_purchase_return"),
                DB::raw("SUM(pl.quantity_adjusted) as total_adjusted"),
                DB::raw("SUM(IF(t.type='opening_stock', pl.quantity, 0)) as total_opening_stock"),
                DB::raw("SUM(IF(t.type='purchase_transfer', pl.quantity, 0)) as total_purchase_transfer"),
                'variations.sub_sku as sub_sku',
                'p.name as product',
                'p.type',
                'p.sku',
                'p.id as product_id',
                'units.short_name as unit',
                'pv.name as product_variation',
                'variations.name as variation_name',
                'variations.id as variation_id'
            )
            ->get()->first();

        $sell_details = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
            ->leftjoin('transaction_sell_lines as sl', 'sl.variation_id', '=', 'variations.id')
            ->join('transactions as t', 'sl.transaction_id', '=', 't.id')
            ->where('t.location_id', $location_id)
            ->where('t.status', AppConstant::FINAL)
            ->orWhere('t.status', AppConstant::PROCESSING)
            ->orWhere('t.status', AppConstant::COMPLETED)
            ->where('p.business_id', $business_id)
            ->where('variations.id', $variation_id)
            ->select(
                DB::raw("SUM(IF(t.type='sell', sl.quantity, 0)) as total_sold"),
                DB::raw("SUM(IF(t.type='sell', sl.quantity_returned, 0)) as total_sell_return"),
                DB::raw("SUM(IF(t.type='sell_transfer', sl.quantity, 0)) as total_sell_transfer")
            )
            ->get()->first();

        $current_stock = VariationLocationDetails::where('variation_id',
            $variation_id)
            ->where('location_id', $location_id)
            ->first();

        if ($purchase_details->type == 'variable') {
            $product_name = $purchase_details->product . ' - ' . $purchase_details->product_variation . ' - ' . $purchase_details->variation_name . ' (' . $purchase_details->sub_sku . ')';
        } else {
            $product_name = $purchase_details->product . ' (' . $purchase_details->sku . ')';
        }

        $output = [
            'variation' => $product_name,
            'unit' => $purchase_details->unit,
            'total_purchase' => $purchase_details->total_purchase,
            'total_purchase_return' => $purchase_details->total_purchase_return,
            'total_adjusted' => $purchase_details->total_adjusted,
            'total_opening_stock' => $purchase_details->total_opening_stock,
            'total_purchase_transfer' => $purchase_details->total_purchase_transfer,
            'total_sold' => $sell_details->total_sold,
            'total_sell_return' => $sell_details->total_sell_return,
            'total_sell_transfer' => $sell_details->total_sell_transfer,
            'current_stock' => $current_stock->qty_available ?? 0
        ];

        return $output;
    }

    public function getSupplierProductStockDetails($business_id, $product_id, $location_id)
    {
        $purchase_details = SupplierProduct::join('supplier_product_units', 'supplier_products.unit_id', '=', 'supplier_product_units.id')
            ->leftjoin('supplier_purchase_lines as spl', 'spl.product_id', '=', 'supplier_products.id')
            ->leftjoin('supplier_transactions as st', 'spl.supplier_transactions_id', '=', 'st.id')
            ->where('st.location_id', $location_id)
            ->where('st.status', 'received')
            ->where('supplier_products.business_id', $business_id)
            ->where('supplier_products.id', $product_id)
            ->select(
                DB::raw("SUM(IF(st.type='purchase' AND st.status='received', spl.quantity, 0)) as total_purchase"),
                DB::raw("SUM(IF(st.type='purchase' OR st.type='purchase_return', spl.quantity_returned, 0)) as total_purchase_return"),
                DB::raw("SUM(spl.quantity_adjusted) as total_adjusted"),
                DB::raw("SUM(IF(st.type='opening_stock', spl.quantity, 0)) as total_opening_stock"),
                DB::raw("SUM(IF(st.type='purchase_transfer', spl.quantity, 0)) as total_purchase_transfer"),
                'supplier_products.name as product',
                'supplier_products.sku',
                'supplier_products.id as product_id',
                'supplier_product_units.short_name as unit',
            )
            ->get()->first();

        $sell_details = SupplierProduct::leftjoin('supplier_transaction_sell_lines as sl', 'sl.product_id', '=', 'supplier_products.id')
            ->join('supplier_transactions as st', 'sl.supplier_transaction_id', '=', 'st.id')
            ->where('st.location_id', $location_id)
            ->where('st.status', 'final')
            ->where('supplier_products.business_id', $business_id)
            ->where('supplier_products.id', $product_id)

            ->select(
                DB::raw("SUM(IF(st.type='sell', sl.quantity, 0)) as total_sold"),
                DB::raw("SUM(IF(st.type='sell', sl.qty_returned, 0)) as total_sell_return"),
                DB::raw("SUM(IF(st.type='sell_transfer', sl.quantity, 0)) as total_sell_transfer")
            )
            ->get()->first();

        $current_stock = SupplierProductLocationDetail::where('product_id',
            $product_id)
            ->where('location_id', $location_id)
            ->first();

        $product_name = $purchase_details->product . ' (' . $purchase_details->sku . ')';

        $output = [
            'product' => $product_name,
            'unit' => $purchase_details->unit,
            'total_purchase' => $purchase_details->total_purchase,
            'total_purchase_return' => $purchase_details->total_purchase_return,
            'total_adjusted' => $purchase_details->total_adjusted,
            'total_opening_stock' => $purchase_details->total_opening_stock,
            'total_purchase_transfer' => $purchase_details->total_purchase_transfer,
            'total_sold' => $sell_details->total_sold,
            'total_sell_return' => $sell_details->total_sell_return,
            'total_sell_transfer' => $sell_details->total_sell_transfer,
            'current_stock' => $current_stock->qty_available ?? 0
        ];
        return $output;
    }
    public function getSupplierProductStockHistory($business_id, $product_id, $location_id)
    {
        $stock_history = SupplierTransaction::leftjoin('supplier_transaction_sell_lines as sl',
            'sl.supplier_transaction_id', '=', 'supplier_transactions.id')
                                ->leftjoin('supplier_purchase_lines as pl',
                                    'pl.supplier_transactions_id', '=', 'supplier_transactions.id')
                                ->leftjoin('supplier_stock_adjustment_lines as al',
                                    'al.supplier_transaction_id', '=', 'supplier_transactions.id')
                                ->leftjoin('supplier_transactions as return', 'supplier_transactions.return_parent_id', '=', 'supplier_transactions.id')
                                ->leftjoin('supplier_purchase_lines as rpl',
                                    'rpl.supplier_transactions_id', '=', 'return.id')
                                ->leftjoin('supplier_transaction_sell_lines as rsl',
                                        'rsl.supplier_transaction_id', '=', 'return.id')
                                ->where('supplier_transactions.location_id', $location_id)
                                ->where( function($q) use ($product_id){
                                    $q->where('sl.product_id', $product_id)
                                        ->orWhere('pl.product_id', $product_id)
                                        ->orWhere('al.product_id', $product_id)
                                        ->orWhere('rpl.product_id', $product_id)
                                        ->orWhere('rsl.product_id', $product_id);
                                })
                                ->whereIn('supplier_transactions.type', ['sell', 'purchase', 'stock_adjustment', 'opening_stock', 'sell_transfer', 'purchase_transfer', 'production_purchase', 'purchase_return', 'sell_return', 'production_sell'])
                                ->select(
                                    'supplier_transactions.id as transaction_id',
                                    'supplier_transactions.type as transaction_type',
                                    'sl.quantity as sell_line_quantity',
                                    'pl.quantity as purchase_line_quantity',
                                    'rsl.qty_returned as sell_return',
                                    'rpl.quantity_returned as purchase_return',
                                    'al.quantity as stock_adjusted',
                                    'pl.quantity_returned as combined_purchase_return',
                                    'supplier_transactions.return_parent_id',
                                    'supplier_transactions.transaction_date',
                                    'supplier_transactions.status',
                                    'supplier_transactions.invoice_no',
                                    'supplier_transactions.ref_no',
                                    'supplier_transactions.additional_notes'
                                )
                                ->orderBy('supplier_transactions.transaction_date', 'asc')
                                ->get();

        $stock_history_array = [];
        $stock = 0;
        foreach ($stock_history as $stock_line) {
            if ($stock_line->transaction_type == 'sell') {
                if ($stock_line->status != 'final') {
                    continue;
                }
                $quantity_change =  -1 * $stock_line->sell_line_quantity;
                $stock += $quantity_change;
                $stock_history_array[] = [
                    'date' => $stock_line->transaction_date,
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'sell',
                    'type_label' => __('sale.sale'),
                    'ref_no' => $stock_line->invoice_no,
                    'transaction_id' => $stock_line->transaction_id
                ];
            } elseif ($stock_line->transaction_type == 'purchase') {
                if ($stock_line->status != 'received') {
                    continue;
                }
                $quantity_change = $stock_line->purchase_line_quantity;
                $stock += $quantity_change;
                $stock_history_array[] = [
                    'date' => $stock_line->transaction_date,
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'purchase',
                    'type_label' => __('lang_v1.purchase'),
                    'ref_no' => $stock_line->ref_no,
                    'transaction_id' => $stock_line->transaction_id
                ];
            } elseif ($stock_line->transaction_type == 'stock_adjustment') {
                $quantity_change = -1 * $stock_line->stock_adjusted;
                $stock += $quantity_change;
                $stock_history_array[] = [
                    'date' => $stock_line->transaction_date,
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'stock_adjustment',
                    'type_label' => __('stock_adjustment.stock_adjustment'),
                    'ref_no' => $stock_line->ref_no,
                    'transaction_id' => $stock_line->transaction_id
                ];
            } elseif ($stock_line->transaction_type == 'opening_stock') {
                $quantity_change = $stock_line->purchase_line_quantity;
                $stock += $quantity_change;
                $stock_history_array[] = [
                    'date' => $stock_line->transaction_date,
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'opening_stock',
                    'type_label' => __('report.opening_stock'),
                    'ref_no' => $stock_line->ref_no ?? '',
                    'transaction_id' => $stock_line->transaction_id,
                    'additional_notes' => $stock_line->additional_notes
                ];
            } elseif ($stock_line->transaction_type == 'sell_transfer') {
                if ($stock_line->status != 'final') {
                    continue;
                }
                $quantity_change = -1 * $stock_line->sell_line_quantity;
                $stock += $quantity_change;
                $stock_history_array[] = [
                    'date' => $stock_line->transaction_date,
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'sell_transfer',
                    'type_label' => __('lang_v1.stock_transfers') . ' (' . __('lang_v1.out') . ')',
                    'ref_no' => $stock_line->ref_no,
                    'transaction_id' => $stock_line->transaction_id
                ];
            } elseif ($stock_line->transaction_type == 'purchase_transfer') {
                if ($stock_line->status != 'received') {
                    continue;
                }

                $quantity_change = $stock_line->purchase_line_quantity;
                $stock += $quantity_change;
                $stock_history_array[] = [
                    'date' => $stock_line->transaction_date,
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'purchase_transfer',
                    'type_label' => __('lang_v1.stock_transfers') . ' (' . __('lang_v1.in') . ')',
                    'ref_no' => $stock_line->ref_no,
                    'transaction_id' => $stock_line->transaction_id
                ];
            } elseif ($stock_line->transaction_type == 'production_sell') {
                if ($stock_line->status != 'final') {
                    continue;
                }
                $quantity_change =  -1 * $stock_line->sell_line_quantity;
                $stock += $quantity_change;
                $stock_history_array[] = [
                    'date' => $stock_line->transaction_date,
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'sell',
                    'type_label' => __('manufacturing::lang.ingredient'),
                    'ref_no' => '',
                    'transaction_id' => $stock_line->transaction_id
                ];
            } elseif ($stock_line->transaction_type == 'production_purchase') {
                $quantity_change = $stock_line->purchase_line_quantity;
                $stock += $quantity_change;
                $stock_history_array[] = [
                    'date' => $stock_line->transaction_date,
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'production_purchase',
                    'type_label' => __('manufacturing::lang.manufactured'),
                    'ref_no' => $stock_line->ref_no,
                    'transaction_id' => $stock_line->transaction_id
                ];
            } elseif ($stock_line->transaction_type == 'purchase_return') {
                $quantity_change =  -1 * ($stock_line->combined_purchase_return + $stock_line->purchase_return);
                $stock += $quantity_change;
                $stock_history_array[] = [
                    'date' => $stock_line->transaction_date,
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'purchase_return',
                    'type_label' => __('lang_v1.purchase_return'),
                    'ref_no' => $stock_line->ref_no,
                    'transaction_id' => $stock_line->transaction_id
                ];
            } elseif ($stock_line->transaction_type == 'sell_return') {
                $quantity_change = $stock_line->sell_return;
                $stock += $quantity_change;
                $stock_history_array[] = [
                    'date' => $stock_line->transaction_date,
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'purchase_transfer',
                    'type_label' => __('lang_v1.sell_return'),
                    'ref_no' => $stock_line->invoice_no,
                    'transaction_id' => $stock_line->transaction_id
                ];
            }
        }
        return array_reverse($stock_history_array);
    }

    public function getVariationStockHistory($business_id, $variation_id, $location_id)
    {
        $stock_history = Transaction::leftjoin('transaction_sell_lines as sl',
            'sl.transaction_id', '=', 'transactions.id')
            ->leftjoin('purchase_lines as pl',
                'pl.transaction_id', '=', 'transactions.id')
            ->leftjoin('stock_adjustment_lines as al',
                'al.transaction_id', '=', 'transactions.id')
            ->leftjoin('transactions as return', 'transactions.return_parent_id', '=', 'return.id')
            ->leftjoin('purchase_lines as rpl',
                'rpl.transaction_id', '=', 'return.id')
            ->leftjoin('transaction_sell_lines as rsl',
                'rsl.transaction_id', '=', 'return.id')
            ->where('transactions.location_id', $location_id)
            ->where(function ($q) use ($variation_id) {
                $q->where('sl.variation_id', $variation_id)
                    ->orWhere('pl.variation_id', $variation_id)
                    ->orWhere('al.variation_id', $variation_id)
                    ->orWhere('rpl.variation_id', $variation_id)
                    ->orWhere('rsl.variation_id', $variation_id);
            })
            ->whereIn('transactions.type', ['sell', 'purchase', 'stock_adjustment', 'opening_stock', 'sell_transfer', 'purchase_transfer', 'production_purchase', 'purchase_return', 'sell_return', 'production_sell'])
            ->select(
                'transactions.id as transaction_id',
                'transactions.type as transaction_type',
                'sl.quantity as sell_line_quantity',
                'pl.quantity as purchase_line_quantity',
                'rsl.quantity_returned as sell_return',
                'rpl.quantity_returned as purchase_return',
                'al.quantity as stock_adjusted',
                'pl.quantity_returned as combined_purchase_return',
                'transactions.return_parent_id',
                'transactions.transaction_date',
                'transactions.status',
                'transactions.invoice_no',
                'transactions.ref_no',
                'transactions.additional_notes'
            )
            ->orderBy('transactions.transaction_date', 'asc')
            ->get();

        $stock_history_array = [];
        $stock = 0;
        foreach ($stock_history as $stock_line) {
            if ($stock_line->transaction_type == 'sell') {
                if ($stock_line->status != AppConstant::FINAL || $stock_line->status != AppConstant::PROCESSING || $stock_line->status != AppConstant::COMPLETED) {
                    continue;
                }
                $quantity_change = -1 * $stock_line->sell_line_quantity;
                $stock += $quantity_change;
                $stock_history_array[] = [
                    'date' => $stock_line->transaction_date,
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'sell',
                    'type_label' => __('sale.sale'),
                    'ref_no' => $stock_line->invoice_no,
                    'transaction_id' => $stock_line->transaction_id
                ];
            } elseif ($stock_line->transaction_type == 'purchase') {
                if ($stock_line->status != 'received') {
                    continue;
                }
                $quantity_change = $stock_line->purchase_line_quantity;
                $stock += $quantity_change;
                $stock_history_array[] = [
                    'date' => $stock_line->transaction_date,
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'purchase',
                    'type_label' => __('lang_v1.purchase'),
                    'ref_no' => $stock_line->ref_no,
                    'transaction_id' => $stock_line->transaction_id
                ];
            } elseif ($stock_line->transaction_type == 'stock_adjustment') {
                $quantity_change = -1 * $stock_line->stock_adjusted;
                $stock += $quantity_change;
                $stock_history_array[] = [
                    'date' => $stock_line->transaction_date,
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'stock_adjustment',
                    'type_label' => __('stock_adjustment.stock_adjustment'),
                    'ref_no' => $stock_line->ref_no,
                    'transaction_id' => $stock_line->transaction_id
                ];
            } elseif ($stock_line->transaction_type == 'opening_stock') {
                $quantity_change = $stock_line->purchase_line_quantity;
                $stock += $quantity_change;
                $stock_history_array[] = [
                    'date' => $stock_line->transaction_date,
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'opening_stock',
                    'type_label' => __('report.opening_stock'),
                    'ref_no' => $stock_line->ref_no ?? '',
                    'transaction_id' => $stock_line->transaction_id,
                    'additional_notes' => $stock_line->additional_notes
                ];
            } elseif ($stock_line->transaction_type == 'sell_transfer') {
                if ($stock_line->status != AppConstant::FINAL || $stock_line->status != AppConstant::PROCESSING || $stock_line->status != AppConstant::COMPLETED) {
                    continue;
                }
                $quantity_change = -1 * $stock_line->sell_line_quantity;
                $stock += $quantity_change;
                $stock_history_array[] = [
                    'date' => $stock_line->transaction_date,
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'sell_transfer',
                    'type_label' => __('lang_v1.stock_transfers') . ' (' . __('lang_v1.out') . ')',
                    'ref_no' => $stock_line->ref_no,
                    'transaction_id' => $stock_line->transaction_id
                ];
            } elseif ($stock_line->transaction_type == 'purchase_transfer') {
                if ($stock_line->status != 'received') {
                    continue;
                }

                $quantity_change = $stock_line->purchase_line_quantity;
                $stock += $quantity_change;
                $stock_history_array[] = [
                    'date' => $stock_line->transaction_date,
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'purchase_transfer',
                    'type_label' => __('lang_v1.stock_transfers') . ' (' . __('lang_v1.in') . ')',
                    'ref_no' => $stock_line->ref_no,
                    'transaction_id' => $stock_line->transaction_id
                ];
            } elseif ($stock_line->transaction_type == 'production_sell') {
                if ($stock_line->status != AppConstant::FINAL || $stock_line->status != AppConstant::PROCESSING || $stock_line->status != AppConstant::COMPLETED) {
                    continue;
                }
                $quantity_change = -1 * $stock_line->sell_line_quantity;
                $stock += $quantity_change;
                $stock_history_array[] = [
                    'date' => $stock_line->transaction_date,
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'sell',
                    'type_label' => __('manufacturing::lang.ingredient'),
                    'ref_no' => '',
                    'transaction_id' => $stock_line->transaction_id
                ];
            } elseif ($stock_line->transaction_type == 'production_purchase') {
                $quantity_change = $stock_line->purchase_line_quantity;
                $stock += $quantity_change;
                $stock_history_array[] = [
                    'date' => $stock_line->transaction_date,
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'production_purchase',
                    'type_label' => __('manufacturing::lang.manufactured'),
                    'ref_no' => $stock_line->ref_no,
                    'transaction_id' => $stock_line->transaction_id
                ];
            } elseif ($stock_line->transaction_type == 'purchase_return') {
                $quantity_change = -1 * ($stock_line->combined_purchase_return + $stock_line->purchase_return);
                $stock += $quantity_change;
                $stock_history_array[] = [
                    'date' => $stock_line->transaction_date,
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'purchase_return',
                    'type_label' => __('lang_v1.purchase_return'),
                    'ref_no' => $stock_line->ref_no,
                    'transaction_id' => $stock_line->transaction_id
                ];
            } elseif ($stock_line->transaction_type == 'sell_return') {
                $quantity_change = $stock_line->sell_return;
                $stock += $quantity_change;
                $stock_history_array[] = [
                    'date' => $stock_line->transaction_date,
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'purchase_transfer',
                    'type_label' => __('lang_v1.sell_return'),
                    'ref_no' => $stock_line->invoice_no,
                    'transaction_id' => $stock_line->transaction_id
                ];
            }
        }

        return array_reverse($stock_history_array);
    }
}
