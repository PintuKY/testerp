<?php

namespace App\Console\Commands;

use App\Models\ApiSetting;
use App\Models\BusinessLocation;
use Illuminate\Console\Command;
use App\Models\Category;
use App\Models\Contact;
use App\Models\MasterList;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Transaction;
use App\Models\TransactionPayment;
use App\Models\TransactionSellLine;
use App\Models\TransactionSellLinesDay;
use App\Models\TransactionSellLinesVariant;
use App\Models\Variation;
use Carbon\Carbon;

use App\Utils\ContactUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Exception;

class SyncOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:order {business_location_id=all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Order Sync According to Business Location if Business Location is not set then it will sync all orders';

    /**
     * Execute the console command.
     *
     * @return int
     */
    protected $contactUtil;
    protected $businessUtil;
    protected $transactionUtil;
    protected $productUtil;

    public function handle(ContactUtil $contactUtil, TransactionUtil $transactionUtil, ProductUtil $productUtil)
    {
        $this->contactUtil = $contactUtil;
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        $business_location_id = $this->argument('business_location_id');
        if ($business_location_id !== 'all') {
            $this->syncOrderDetails($business_location_id);
        } else {
            $apiSettings = ApiSetting::get();
            foreach ($apiSettings as $apiSetting) {
                $this->syncOrderDetails($apiSetting->business_locations_id);
            }
        }
        return true;
    }

    /**
     * syncOrderDetails
     *
     * @return void
     */
    public function syncOrderDetails($bussiness_location_id)
    {
        $i = 1;
        while (true) {
            try {

                $orderEndpoint = (config('api.is_order_first_time_sync') == true) ? config("api.order_endpoint") . '?page=' . $i . '&orderby=date&order=desc' : config("api.order_endpoint") . '?page=' . $i . '&orderby=date&order=desc&after=' . now()->subDays(config("api.api_setting_90_days_to_sync_order"));

                $orders = getData(getConfiguration($bussiness_location_id), $orderEndpoint);

                if (count($orders) <= 0) {
                    break;
                }
                $customerId = null;
                $transection = null;
                foreach ($orders as $order) {
                    if (Transaction::where('web_order_id', $order->id)->exists()) {
                        $transection = Transaction::where('web_order_id', $order->id)->first();
                        Transaction::where('web_order_id', $order->id)->update([
                            'status' => getOrderStatusNumber($order->status),
                        ]);
                        $this->transactionUtil->activityLog(Transaction::where('web_order_id', $order->id)->first() , 'edited' , $transection);
                    } else {
                        if (Contact::where('contact_id', $order->customer_id)->exists()) {
                            $customerId = Contact::where('contact_id', $order->customer_id)->value('id');
                        } else {
                            if ($order->customer_id != 0) {
                                $customerId = $this->createCustomer($order->customer_id, $bussiness_location_id);
                            }
                        }

                        $transection = $this->createTransection($order, $bussiness_location_id, $customerId);
                        $this->transactionUtil->activityLog($transection, 'added');

                        $timeSlot = null;
                        $startDate = null;
                        $lineItems = $order->line_items ?? [];
                        $product_id = null;
                        $isBothLunchOrDinner = false;
                        foreach ($lineItems as $lineItem) {
                            if (Product::where('product_id', $lineItem->product_id)->exists()) {
                                $product_id = Product::where('product_id', $lineItem->product_id)->value('id');
                            } else {
                                $product_id = $this->createProduct($lineItem->product_id, $bussiness_location_id);
                            }
                            $metaData = $lineItem->meta_data ?? [];
                            if ($this->checkStartDateExistOrNot($lineItem->meta_data)) {

                                foreach ($metaData as $meta) {
                                    if ($meta->key == 'Time Slot') {
                                        $isBothLunchOrDinner = true;
                                        if (str_contains($meta->value, 'Lunch')) {
                                            $timeSlot = '1';
                                        } elseif(str_contains($meta->value, 'Dinner')) {
                                            $timeSlot = '2';
                                        }
                                    }
                                    if ($meta->key == 'Start Date') {
                                        $startDate = $meta->value;
                                    }
                                }
                                if (!$isBothLunchOrDinner)
                                {
                                    $timeSlot = '3';
                                }

                            } else {
                                $timeSlot = '0';
                            }

                            $transactionSellLine = $this->createTransactionSellLine($transection, $product_id, $lineItem, $timeSlot, $startDate);

                                foreach ($metaData as $meta) {
                                    if ($meta->key == 'Delivery Days') {
                                        $deliveryDays = explode(', ', $meta->value);

                                        foreach (array_unique($deliveryDays) as $deliveryDay) {
                                            TransactionSellLinesDay::create(
                                                [
                                                    'transaction_sell_lines_id' => $transactionSellLine->id,
                                                    'day' => $this->getDayNumber($deliveryDay),
                                                ]
                                            );
                                        }
                                    }

                                    if ($meta->key != 'Delivery Days' && $meta->key != 'Time Slot' && $meta->key != 'Start Date' && $meta->key != 'Delivery Time' && $meta->key != 'Delivery Date') {
                                        if (gettype($meta->value) === 'string' || gettype($meta->value) === 'integer' ) {
                                            $name = '';
                                            $value = '';
                                            if ($meta->key === 'Serving Pax') {
                                                $value_string = $this->separateStringPrice($meta->value , '(');
                                                $name = isset(explode("-", $value_string)[0]) ? explode("-", $value_string)[0] : '';
                                                $value = isset(explode("-", $value_string)[1]) ? explode("-", $value_string)[1] : '';
                                            } else if ($meta->key === 'Residential Delivery Type') {
                                                $value_string = $this->separateStringPrice($meta->value , '(');
                                                $name = isset(explode("-", $value_string)[0]) ? explode("-", $value_string)[0] : '';
                                                $value = isset(explode("-", $value_string)[1]) ? explode("-", $value_string)[1] : '';
                                            }

                                            TransactionSellLinesVariant::create(
                                                [
                                                    'transaction_sell_lines_id' => $transactionSellLine->id,
                                                    'pax' => $meta->key,
                                                    'addon' => $meta->value,
                                                    'name' => $name,
                                                    'value' => $value,
                                                ]
                                            );
                                        }
                                    }
                                }

                        }
                        $is_refund = 0;
                        $this->createTransactionPayment($order, $bussiness_location_id, $transection, $is_refund);

                        if (count($order->refunds) > 0) {
                            foreach ($order->refunds as $refund) {
                                $is_refund = 1;
                                $this->createTransactionPayment($order, $bussiness_location_id, $transection, $is_refund, $refund);
                            }
                            $transection_date = ($transection->date_modified) ? $transection->date_modified : '0000-00-00';
                            MasterList::where('transaction_id', $transection->id)->whereDate('delivery_date', '>' ,$transection_date)->update([
                                'status' => 2,
                                'additional_notes' => 'reason',
                            ]);

                        } else {
                            $days = 1;
                            if (str_contains($lineItem->sku, 'tingkat')) {
                                $skuSplit = explode("-",$lineItem->sku);
                                $days = $skuSplit[0];
                            }

                            if (!MasterList::where('transaction_id', $transection->id)->exists()) {
                                // if ($order->status == 'completed' || $order->status == 'processing') {
                                    $this->createMasterList($transection, $days);
                                // }
                            } else {
                                MasterList::where('transaction_id', $transection->id)->update([
                                    'shipping_address_line_1' => $transection->shipping_address_line_1 ?? 'shipping_address_line_1',
                                    'shipping_address_line_2' => $transection->shipping_address_line_2 ?? null,
                                    'shipping_city' => $transection->shipping_city ?? 'shipping_city',
                                    'shipping_state' => $transection->shipping_state ?? 'shipping_state',
                                    'shipping_country' => $transection->shipping_country ?? 'shipping_country',
                                    'shipping_zip_code' => $transection->shipping_zip_code ?? 'shipping_zip_code',
                                    'additional_notes' => $transection->additional_notes ?? 'additional_notes',
                                    'hpnumber' => 'hpnumber',
                                    'status' => $this->getMasterListStatus($transection),
                                    'additional_notes' => 'additional_notes',
                                ]);
                            }
                        }
                    }

                    // }
                }
            } catch (Exception $e) {
                dd('Ex. - ', $e);
            }
            $i++;

        }
        return 'Order completed';
    }

    /**
     * separateStringPrice
     *
     * @param  mixed $string
     * @param  mixed $delimiter
     * @return void
     */
    public function separateStringPrice ($string , $delimiter)
    {
        $stringpos = strrpos($string, $delimiter, -1);
        $name = substr($string,0,$stringpos);
        $search = array($name, '(','+', ')', '$');
        $replace = array('', '', '', '', '');
        $value = str_replace($search, $replace, $string);
        return $name.'-'.$value;
    }

    /**
     * checkStartDateExistOrNot
     *
     * @param  mixed $metaData
     * @return void
     */
    public function checkStartDateExistOrNot($metaData)
    {
        foreach ($metaData as $meta) {
            if ($meta->key == 'Start Date') {
                return true;
            }
        }
        return false;
    }

    /**
     * createTransection
     *
     * @param  mixed $order
     * @param  mixed $bussiness_location_id
     * @param  mixed $customerId
     * @return object
     */
    public function createTransection($order, $bussiness_location_id, $customerId = null)
    {
        try {
            $transaction = Transaction::create([
                'web_order_id' => $order->id,
                'business_id' => 1,
                'location_id' => $bussiness_location_id,
                'status' => getOrderStatusNumber($order->status),
                'type' => 'sell',
                'payment_status' => $this->getPaymentStatus($order),
                'contact_id' => $customerId,
                'billing_address_line_1' => optional($order->billing)->address_1,
                'billing_address_line_2' => optional($order->billing)->address_2,
                'billing_city' => optional($order->billing)->city,
                'billing_state' => optional($order->billing)->state,
                'billing_country' => optional($order->billing)->country,
                'billing_zip_code' => optional($order->billing)->postcode,
                'billing_email' => optional($order->billing)->email,
                'billing_phone' => optional($order->billing)->phone,
                'billing_company' => optional($order->billing)->company,
                'shipping_address_line_1' => optional($order->shipping)->address_1,
                'shipping_address_line_2' => optional($order->shipping)->address_2,
                'shipping_city' => optional($order->shipping)->city,
                'shipping_state' => optional($order->shipping)->state,
                'shipping_country' => optional($order->shipping)->country,
                'shipping_zip_code' => optional($order->shipping)->postcode,
                'customer_group_id' => 1,
                'invoice_no' =>  $order->number,
                'ref_no' => null,

                'transaction_date' => ($order->date_paid) ? $order->date_paid : $order->date_created,
                'total_before_tax' =>  $order->total - $order->total_tax,
                'tax_id' => null,
                'tax_amount' => $order->total_tax,
                'discount_type' => null,
                'discount_amount' => $order->discount_total,

                'shipping_status' => null,
                'delivered_to' => null,
                'shipping_charges' => $order->shipping_total,

                'additional_notes' => $order->customer_note,
                'staff_note' => null,

                'final_total' => $order->total,
                'created_by' => 1,

                'prefer_payment_method' => $order->payment_method_title,
                'prefer_payment_account' => $order->payment_method,
                'sales_order_ids' => $order->id,

            ]);
            return $transaction;
        } catch (Exception $e) {
            dd('Ex. - ', $e);
        }
    }

    /**
     * getPaymentStatus
     *
     * @param  mixed $order
     * @return void
     */
    public function getPaymentStatus($order)
    {
        if ($order->status === 'completed' || $order->status === 'processing') {
            return 'paid';
        } else {
            return 'due';
        }
    }

    /**
     * createTransactionSellLine
     *
     * @param  mixed $transection
     * @param  mixed $product_id
     * @param  mixed $lineItem
     * @param  mixed $timeSlot
     * @param  mixed $startDate
     * @return object
     */
    public function createTransactionSellLine($transection, $product_id, $lineItem, $timeSlot, $startDate = null)
    {
        try {
            $metaData = $lineItem->meta_data ?? [];
            $delivery_date = null;
            $delivery_time = null;
            if ($metaData) {
                foreach ($metaData as $item) {
                    if ($item->key === 'Delivery Date') {
                        $delivery_date = $item->value;
                    }

                    if ($item->key === 'Delivery Time') {
                        $delivery_time = $item->value;
                    }
                }
            }
            $transactionSellLine = TransactionSellLine::create(
                [
                    'transaction_id' => $transection->id,
                    'product_id' => $product_id,
                    'product_name' => $lineItem->name,
                    'time_slot' => $timeSlot,
                    'start_date' => !filled($startDate) ? null : Carbon::parse($startDate)->format('Y-m-d'),
                    'variation_id' => $lineItem->variation_id,
                    'quantity' => $lineItem->quantity,
                    'quantity_returned' => 0,
                    'unit_price_before_discount' => $lineItem->subtotal,
                    'unit_price' => $lineItem->price,
                    'delivery_date' =>  !filled($delivery_date) ? null : Carbon::parse($delivery_date)->format('Y-m-d'),
                    'delivery_time' => !filled($delivery_time) ? null : Carbon::parse($delivery_time)->format('h:i:s'),
                ]
            );

            return $transactionSellLine;
        } catch (Exception $e) {
            dd('Ex. - ', $e);
        }
    }


    /**
     * createTransactionPayment
     *
     * @param  mixed $order
     * @param  mixed $bussiness_location_id
     * @param  mixed $transection
     * @param  mixed $refund
     * @return void
     */
    public function createTransactionPayment($order, $bussiness_location_id, $transection, $is_refund, $refund = null)
    {
        try {
            if ($refund != null && $is_refund == 1) {
                $amount = $refund->total;
                $reason = $refund->reason;
            } else {
                $amount = $order->total;
                $reason = $order->customer_note;
            }
            $transectionPayment = TransactionPayment::create(
                [
                    'transaction_id' => $transection->id,
                    'business_id' => $bussiness_location_id,
                    'is_return' => $is_refund,
                    'amount' => $amount,
                    'method' => $order->payment_method_title,
                    'transaction_no' => $order->transaction_id,
                    'paid_on' => $order->date_paid,
                    'created_by' => 1,
                    'gateway' => $order->payment_method,
                    'is_advance' => 0,
                    'payment_for' => null,
                    'note' => $reason,
                    'payment_ref_no' => $order->transaction_id,
                ]
            );
            return $transectionPayment;
        } catch (Exception $e) {
            dd('Ex. - ', $e);
        }
    }


    /**
     * createCustomer
     *
     * @return void
     */
    public function createCustomer($customerId, $bussiness_location_id)
    {
        try {
            $customerEndpoint = config("api.customer_endpoint") . '/' . $customerId;
            $customer = getData(getConfiguration($bussiness_location_id), $customerEndpoint);
            $newCustomer = Contact::create(
                [
                    'business_id' => 1,
                    'business_location_id' => $bussiness_location_id,
                    'type' => $customer->role,
                    'name' => $customer->first_name . ' ' . $customer->last_name,
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'email' => $customer->email,
                    'contact_id' => $this->getContactId($bussiness_location_id).''.$customer->id,
                    'city' => optional($customer->billing)->city,
                    'state' => optional($customer->billing)->state,
                    'country' => optional($customer->billing)->country,
                    'address_line_1' => optional($customer->billing)->address_1,
                    'address_line_2' => optional($customer->billing)->address_2,
                    'zip_code' => optional($customer->billing)->postcode,
                    'mobile' => optional($customer->billing)->phone,
                    'created_by' => 1,
                    'shipping_address' => null,
                    'shipping_custom_field_details' => null,
                    'billing_phone' => optional($customer->billing)->phone,
                    'billing_email' => optional($customer->billing)->email,
                    'shipping_address_1' => optional($customer->shipping)->address_1,
                    'shipping_address_2' => optional($customer->shipping)->address_2,
                    'shipping_city' => optional($customer->shipping)->city,
                    'shipping_state' => optional($customer->shipping)->state,
                    'shipping_zipcode' => optional($customer->shipping)->postcode,
                    'customer_group_id' => 1

                ]
            );
            $this->contactUtil->activityLog($newCustomer, 'added');
            return $newCustomer->id;
        } catch (Exception $e) {
            dd('Ex. - ', $e, 'customerEndpoint', $customerEndpoint);
        }
    }



    /**
     * createProduct
     *
     * @return void
     */

    public function createProduct($productId, $bussiness_location_id)
    {
        try {
            $productEndpoint = config("api.product_endpoint") . '/' . $productId;
            $product = getData(getConfiguration($bussiness_location_id), $productEndpoint);
            $category_id =  $this->getCategoryId($product->sku) ?? 1;
            $admin_id = 1;
            if ($product->status === 'publish') {
                    $newProduct = Product::create(
                    [
                        'product_id' =>   $this->getContactId($bussiness_location_id).''.$productId,
                        'category_id' =>  $category_id,
                        'sub_category_id' => $category_id,
                        'name' =>  $product->name,
                        'business_id' =>  $bussiness_location_id,
                        'product_description' => ($product->description) ? $product->description : $product->short_description,
                        'sku' =>  $product->sku,
                        'created_by' =>  $admin_id,
                        'enable_stock'    => 1,
                        'alert_quantity' => 1,
                        'is_inactive' =>  1,
                        'status' =>  $product->status,
                    ]
                );
                $newProduct->product_locations()->sync([$bussiness_location_id]);

                $productVariations = ProductVariation::updateOrCreate(
                    [
                        'product_id' =>  $newProduct->id,
                    ],
                    [
                        'variation_template_id' => null,
                        'name' => 'Dummy',
                        'product_id' => $newProduct->id,
                        'is_dummy' => 1
                    ]
                );

                $variations = Variation::updateOrCreate([
                    'product_id' => $newProduct->id,
                    'product_variation_id' => $productVariations->id
                ], [
                    'name' =>  'DUMMY',
                    'product_id' => $newProduct->id,
                    'sub_sku' =>  '000' . $newProduct->id,
                    'product_variation_id' => $productVariations->id,
                    'variation_value_id' => null,
                    'default_purchase_price' => $product->price,
                    'dpp_inc_tax' => $product->price,
                    'profit_percent' => 0,
                    'default_sell_price' => $product->price,
                    'sell_price_inc_tax' => $product->price,
                ]);
            } else {
                Product::where([
                    'product_id' =>  $productId,
                    'business_id' =>  $bussiness_location_id
                ])->delete();
            }
            return $newProduct->id;
        } catch (Exception $e) {
            dd('Ex. - ', $e, 'productEndpoint');
        }
    }

    /**
     * getCategoryId
     *
     * @param  mixed $categoryName
     * @return void
     */
    public function getCategoryId($categoryName)
    {
        return Category::where('name', $categoryName)->value('id');
    }

    /**
     * getDayNumber
     *
     * @return void
     */
    public function getDayNumber($day)
    {
        return getDayNumberByDayName($day);
    }

    /**
     * createMasterList
     *
     * @param  mixed $transaction
     * @return void
     */
    public function createMasterList($transaction,$day=1)
    {

        try {
            foreach ($transaction->sell_lines as $key => $saleLine) {

                $delivery_date = $saleLine->delivery_date;
                $timeSlot = $saleLine->time_slot;
                $transaction_sell_lines_id = $saleLine->id;
                $delivery_time = $saleLine->delivery_time;

                if (isset($saleLine->delivery_date) && isset($saleLine->delivery_time) && !filled($saleLine->sell_lines_days)) {
                    $this->storeMasterList($transaction, $transaction_sell_lines_id, $delivery_date, $delivery_time, $timeSlot);
                }

                $isMasterlistCreated = 0;
                if (filled($saleLine->sell_lines_days)) {
                    $delivery_days = [];
                    foreach ($saleLine->sell_lines_days as $value)
                    {
                        $delivery_days[] = $value->day;
                    }
                    $i = 1;
                    $deliveryDate = $saleLine->start_date;
                    while (true) {
                        // foreach ($saleLine->sell_lines_days as $key => $saleLineDay) {
                            if ($timeSlot === 3) {
                                $timeSlots = [1, 2];
                                // code for delivery date calculation
                                $delivery_day = Carbon::parse($deliveryDate)->format('N');

                                $delivery_date = $this->getDeliveryDate($delivery_day,$delivery_days,$deliveryDate);
                                $deliveryDate = Carbon::parse($deliveryDate)->addDay();
                                $delivery_day = $delivery_day + 1;
                                // end code for delivery date calculation
                                if ($delivery_date >= $saleLine->start_date)
                                {
                                    foreach ($timeSlots as $value) {
                                        $this->storeMasterList($transaction, $transaction_sell_lines_id, $delivery_date, $delivery_time, $value);
                                    }
                                    $isMasterlistCreated = $isMasterlistCreated + 1;
                                }


                            } else {

                                // code for delivery date calculation
                                $delivery_day = Carbon::parse($deliveryDate)->format('N');

                                $delivery_date = $this->getDeliveryDate($delivery_day,$delivery_days,$deliveryDate);

                                $deliveryDate = Carbon::parse($deliveryDate)->addDay();
                                $delivery_day = $delivery_day + 1;
                                // end code for delivery date calculation
                                if ($delivery_date >= $saleLine->start_date)
                                {
                                    $delivery_time = null;
                                    $this->storeMasterList($transaction, $transaction_sell_lines_id, $delivery_date, $delivery_time,
                                    $timeSlot);
                                    $isMasterlistCreated = $isMasterlistCreated + 1;
                                }
                            }
                        // }
                        if ($isMasterlistCreated >= $day) {
                            break;
                        }
                        $i++;
                    }
                }
            }
            return true;
        } catch (Exception $e) {
            dd('Ex. - ', $e);
        }
    }


    /**
     * storeMasterList
     *
     * @return void
     */
    public function storeMasterList($transaction, $transaction_sell_lines_id, $delivery_date = null, $delivery_time = null, $timeSlot)
    {
        $masterList = MasterList::create(
            [
                'transaction_sell_lines_id' => $transaction_sell_lines_id,
                'transaction_id' => $transaction->id,
                'contacts_id' => isset($transaction->contact->id) ? $transaction->contact->id : 'contact_id',
                'contacts_name' => isset($transaction->contact->name) ? $transaction->contact->name : 'contact_name',
                'shipping_address_line_1' => $transaction->shipping_address_line_1 ?? 'shipping_address_line_1',
                'shipping_address_line_2' => $transaction->shipping_address_line_2 ?? null,
                'shipping_city' => $transaction->shipping_city ?? 'shipping_city',
                'shipping_state' => $transaction->shipping_state ?? 'shipping_state',
                'shipping_country' => $transaction->shipping_country ?? 'shipping_country',
                'shipping_zip_code' => $transaction->shipping_zip_code ?? 'shipping_zip_code',
                'additional_notes' => $transaction->additional_notes ?? 'additional_notes',
                'delivery_note' => 'delivery_note',
                'delivery_date' => Carbon::parse($delivery_date)->format('Y-m-d'),
                'delivery_time' => Carbon::parse($delivery_time)->format('h:i:s'),
                'shipping_phone' => 'shipping_phone',
                'status' => $this->getMasterListStatus($transaction),
                'staff_notes' => ($transaction->staff_note) ? $transaction->staff_note : 'staff_note',
                'created_by' => 1,
                'time_slot' => $timeSlot,
            ]
        );
        return true;
    }


    /**
     * getMasterListStatus
     *
     * @param  mixed $transaction
     * @return void
     */
    public function getMasterListStatus($transaction)
    {
        $status = 0;
        switch ($transaction->status) {
            case ('refunded'):
                $status = 2;
                break;
            case ('completed'):
                $status = 1;
                break;
            case ('final'):
                $status = 1;
                break;
            case ('processing'):
                $status = 1;
                break;
            case ('cancelled'):
                $status = 2;
                break;
            case ('failed'):
                $status = 2;
                break;
            case ('payment_pending'):
                $status = 2;
                break;
            default:
                $status = 0;
        }
        return $status;
    }


    /**
     * getDeliveryDate
     *
     * @param  mixed $sellLine
     * @param  mixed $saleLineDay
     * @return date
     */
    public function getDeliveryDate($delivery_day, $delivery_days,$deliveryDate)
    {
        $delivery_date = null;

        for ($i = 1; $i < 8; $i++) {
            if (in_array((int)$delivery_day, $delivery_days)) {
                $delivery_date = $deliveryDate;
                break;
            }
            if ($delivery_day > 7) {
                $delivery_day = 1;
            }
        }
        return $delivery_date;
    }

    /**
     * getContactId
     *
     * @param  mixed $business_location_id
     * @return void
     */
    public function getContactId($business_location_id)
    {
        return BusinessLocation::where('id', $business_location_id)->value('location_id');
    }
}
