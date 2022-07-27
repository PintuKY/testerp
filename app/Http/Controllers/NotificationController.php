<?php

namespace App\Http\Controllers;

use App\Models\TransactionSellLine;
use \Notification;

use App\Models\Contact;
use App\Notifications\CustomerNotification;
use App\Notifications\SupplierNotification;
use App\Models\NotificationTemplate;
use App\Restaurant\Booking;

use App\Models\Transaction;
use App\Utils\NotificationUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $notificationUtil;
    protected $transactionUtil;

    /**
     * Constructor
     *
     * @param NotificationUtil $notificationUtil , TransactionUtil $transactionUtil
     * @return void
     */
    public function __construct(NotificationUtil $notificationUtil, TransactionUtil $transactionUtil)
    {
        $this->notificationUtil = $notificationUtil;
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Display a notification view.
     *
     * @return \Illuminate\Http\Response
     */
    public function getTemplate($id, $template_for)
    {
        $business_id = request()->session()->get('user.business_id');

        $notification_template = NotificationTemplate::getTemplate($business_id, $template_for);

        $contact = null;
        $transaction = null;
        if ($template_for == 'new_booking') {
            $transaction = Booking::where('business_id', $business_id)
                ->with(['customer'])
                ->find($id);
            $contact = $transaction->customer;
        } elseif ($template_for == 'send_ledger') {
            $contact = Contact::find($id);
        } else {
            $transaction = Transaction::where('business_id', $business_id)
                ->with(['contact'])
                ->find($id);
            $contact = $transaction->contact;
        }
        $product_id = [];
        $product_name = [];
        $edit_product = [];
        foreach ($transaction->sell_lines as $key => $value) {
            $product_id[] = $value->product_id;
            $product_name[] = $value->product->name;
            $edit_product[$value->product_id] = [
                'start_date' => $value->start_date,
                'time_slot' => $value->time_slot,
                'delivery_date' => $value->delivery_date,
                'delivery_time' => $value->delivery_time,
                'unit_value' => $value->unit_value,
                'quantity' => $value->quantity,
                'total_item_value' => $value->total_item_value,
                'unit' => $value->unit,
                'unit_id' => $value->unit_id,
                'default_sell_price' => $value->default_sell_price,
                'unit_price_before_discount' => $value->unit_price_before_discount,
            ];

        }
        $product_ids = array_unique($product_id);
        $product_count = count($product_ids);
        $product_names = array_unique($product_name);
        $customer_notifications = NotificationTemplate::customerNotifications();
        $supplier_notifications = NotificationTemplate::supplierNotifications();
        $general_notifications = NotificationTemplate::generalNotifications();

        $template_name = '';

        $tags = [];
        if (array_key_exists($template_for, $customer_notifications)) {
            $template_name = $customer_notifications[$template_for]['name'];
            $tags = $customer_notifications[$template_for]['extra_tags'];
        } elseif (array_key_exists($template_for, $supplier_notifications)) {
            $template_name = $supplier_notifications[$template_for]['name'];
            $tags = $supplier_notifications[$template_for]['extra_tags'];
        } elseif (array_key_exists($template_for, $general_notifications)) {
            $template_name = $general_notifications[$template_for]['name'];
            $tags = $general_notifications[$template_for]['extra_tags'];
        }

        //for send_ledger notification template
        $start_date = request()->input('start_date');
        $end_date = request()->input('end_date');

        return view('notification.show_template')
            ->with(compact('product_ids',
                'product_names','edit_product','notification_template', 'transaction', 'tags', 'template_name', 'contact', 'start_date', 'end_date'));
    }

    /**
     * Sends notifications to customer and supplier
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function send(Request $request)
    {

        // if (!auth()->user()->can('send_notification')) {
        //     abort(403, 'Unauthorized action.');
        // }
        $notAllowed = $this->notificationUtil->notAllowedInDemo();
        if (!empty($notAllowed)) {
            return $notAllowed;
        }

        try {
            $customer_notifications = NotificationTemplate::customerNotifications();
            $supplier_notifications = NotificationTemplate::supplierNotifications();

            $data = $request->only(['to_email', 'subject', 'email_body', 'mobile_number', 'sms_body', 'notification_type', 'cc', 'bcc', 'whatsapp_text']);

            $emails_array = array_map('trim', explode(',', $data['to_email']));

            $transaction_id = $request->input('transaction_id');
            $business_id = request()->session()->get('business.id');

            $transaction = !empty($transaction_id) ? Transaction::find($transaction_id) : null;

            $sell_details = TransactionSellLine::
            join(
                'products AS p',
                'transaction_sell_lines.product_id',
                '=',
                'p.id'
            )
                ->join(
                    'variations AS variations',
                    'transaction_sell_lines.variation_id',
                    '=',
                    'variations.id'
                )
                ->join(
                    'product_variations AS pv',
                    'variations.product_variation_id',
                    '=',
                    'pv.id'
                )
                ->join(
                    'transaction_sell_lines_variants',
                    'transaction_sell_lines_variants.transaction_sell_lines_id',
                    '=',
                    'transaction_sell_lines.id'
                )
                /*->join(
                    'transaction_sell_lines_days',
                    'transaction_sell_lines_days.transaction_sell_lines_id',
                    '=',
                    'transaction_sell_lines.id'
                )*/
                /*->leftjoin('variation_location_details AS vld', function ($join) use ($location_id) {
                    $join->on('variations.id', '=', 'vld.variation_id')
                        ->where('vld.location_id', '=', $location_id);
                })*/
                ->leftjoin('units', 'units.id', '=', 'p.unit_id')
                ->where('transaction_sell_lines.transaction_id', $transaction_id)
                ->with(['so_line'])
                ->select(
                    \Illuminate\Support\Facades\DB::raw("IF(pv.is_dummy = 0, CONCAT(p.name, ' (', pv.name, ':',variations.name, ')'), p.name) AS product_name"),
                    'p.id as product_id',
                    'p.name as product_actual_name',
                    'p.type as product_type',
                    'pv.name as product_variation_name',
                    'pv.is_dummy as is_dummy',
                    'variations.name as variation_name',
                    'variations.sub_sku',
                    'p.barcode_type',
                    'variations.id as variation_id',
                    'units.short_name as unit',
                    'units.allow_decimal as unit_allow_decimal',
                    'transaction_sell_lines.tax_id as tax_id',
                    'transaction_sell_lines.item_tax as item_tax',
                    'transaction_sell_lines.unit_price as default_sell_price',
                    'transaction_sell_lines.unit_price_before_discount as unit_price_before_discount',
                    'transaction_sell_lines.unit_price_inc_tax as sell_price_inc_tax',
                    'transaction_sell_lines.id as transaction_sell_lines_id',
                    'transaction_sell_lines.id',
                    'transaction_sell_lines.quantity as quantity_ordered',
                    'transaction_sell_lines.return_amount as return_amount',
                    'transaction_sell_lines.total_item_value as total_item_value',
                    'transaction_sell_lines.sell_line_note as sell_line_note',
                    'transaction_sell_lines.parent_sell_line_id',
                    'transaction_sell_lines.lot_no_line_id',
                    'transaction_sell_lines.line_discount_type',
                    'transaction_sell_lines.line_discount_amount',
                    'transaction_sell_lines.res_service_staff_id',
                    'transaction_sell_lines.time_slot',
                    'transaction_sell_lines.start_date',
                    'transaction_sell_lines.delivery_date',
                    'transaction_sell_lines.delivery_time',
                    'transaction_sell_lines.unit_price_inc_tax',
                    'transaction_sell_lines.unit_price_inc_tax',
                    'units.id as unit_id',
                    'transaction_sell_lines.sub_unit_id',
                    'transaction_sell_lines_variants.value',
                    'transaction_sell_lines_variants.pax',
                    'transaction_sell_lines_variants.name as transaction_sell_lines_variants_name',
                )
                ->get();
            $product_id = [];
            $product_name = [];
            $edit_product = [];
            if (!empty($sell_details)) {
                foreach ($sell_details as $key => $value) {
                    $product_id[] = $value->product_id;
                    $edit_product[$value->product_id] = [
                        'start_date' => $value->start_date,
                        'time_slot' => $value->time_slot,
                        'delivery_date' => $value->delivery_date,
                        'delivery_time' => $value->delivery_time,
                        'unit_value' => $value->unit_value,
                        'quantity' => $value->quantity_ordered,
                        'total_item_value' => $value->total_item_value,
                        'unit' => $value->unit,
                        'unit_id' => $value->unit_id,
                        'default_sell_price' => $value->default_sell_price,
                        'unit_price_before_discount' => $value->unit_price_before_discount,
                        'return_amount' => $value->return_amount,
                    ];
                    $product_name[] = $value->product_actual_name;
                }
            }
            $product_ids = array_unique($product_id);
            $product_count = count($product_ids);
            $product_names = array_unique($product_name);
            $order_taxes = [];
            if (!empty($transaction->tax)) {
                if ($transaction->tax->is_tax_group) {
                    $order_taxes = $this->transactionUtil->sumGroupTaxDetails($this->transactionUtil->groupTaxDetails($transaction->tax, $transaction->tax_amount));
                } else {
                    $order_taxes[$transaction->tax->name] = $transaction->tax_amount;
                }
            }
            if ($request->input('template_for') == 'new_sale') {
                $orig_data = [
                    'email_body' => view('notification.product')->with(compact('product_ids',
                            'product_names','edit_product', 'transaction','sell_details','order_taxes'))->render(),
                    'sms_body' => $data['sms_body'],
                    'subject' => $data['subject'],
                    'whatsapp_text' => $data['whatsapp_text']
                ];
            }else{
                $orig_data = [
                    'email_body' => $data['email_body'],
                    'sms_body' => $data['sms_body'],
                    'subject' => $data['subject'],
                    'whatsapp_text' => $data['whatsapp_text']
                ];
            }


            if ($request->input('template_for') == 'new_booking') {
                $tag_replaced_data = $this->notificationUtil->replaceBookingTags($business_id, $orig_data, $transaction_id, $transaction->location_id);

                $data['email_body'] = $tag_replaced_data['email_body'];
                $data['sms_body'] = $tag_replaced_data['sms_body'];
                $data['subject'] = $tag_replaced_data['subject'];
                $data['whatsapp_text'] = $tag_replaced_data['whatsapp_text'];
            } else {
                $tag_replaced_data = $this->notificationUtil->replaceTags($business_id, $orig_data, $transaction_id);

                $data['email_body'] = $tag_replaced_data['email_body'];
                $data['sms_body'] = $tag_replaced_data['sms_body'];
                $data['subject'] = $tag_replaced_data['subject'];
                $data['whatsapp_text'] = $tag_replaced_data['whatsapp_text'];
            }


            $data['email_settings'] = request()->session()->get('business.email_settings');

            /*$data['email_settings'] = [
                "mail_driver" => env('MAIL_DRIVER'),
                "mail_host" => env('MAIL_HOST'),
                "mail_port" => env('MAIL_PORT'),
                "mail_username" => env('MAIL_USERNAME'),
                "mail_password" => env('MAIL_PASSWORD'),
                "mail_encryption" => env('MAIL_ENCRYPTION'),
                "mail_from_address" => env('MAIL_FROM_ADDRESS'),
                "mail_from_name" => env('MAIL_FROM_NAME'),
            ];*/

            $data['sms_settings'] = request()->session()->get('business.sms_settings');

            $notification_type = $request->input('notification_type');

            $whatsapp_link = '';
            if (array_key_exists($request->input('template_for'), $customer_notifications)) {
                if (in_array('email', $notification_type)) {

                    if (!empty($request->input('attach_pdf'))) {
                        $data['pdf_name'] = 'INVOICE-' . $transaction->invoice_no . '.pdf';
                        $data['pdf'] = $this->transactionUtil->getEmailAttachmentForGivenTransaction($business_id, $transaction_id, true);
                    }

                    Notification::route('mail', $emails_array)
                        ->notify(new CustomerNotification($data));

                    if (!empty($transaction)) {
                        $this->notificationUtil->activityLog($transaction, 'email_notification_sent', null, [], false);
                    }
                }
                if (in_array('sms', $notification_type)) {
                    $this->notificationUtil->sendSms($data);

                    if (!empty($transaction)) {
                        $this->notificationUtil->activityLog($transaction, 'sms_notification_sent', null, [], false);
                    }
                }
                if (in_array('whatsapp', $notification_type)) {
                    $whatsapp_link = $this->notificationUtil->getWhatsappNotificationLink($data);
                }
            } elseif (array_key_exists($request->input('template_for'), $supplier_notifications)) {
                if (in_array('email', $notification_type)) {
                    Notification::route('mail', $emails_array)
                        ->notify(new SupplierNotification($data));

                    if (!empty($transaction)) {
                        $this->notificationUtil->activityLog($transaction, 'email_notification_sent', null, [], false);
                    }
                }
                if (in_array('sms', $notification_type)) {
                    $this->notificationUtil->sendSms($data);

                    if (!empty($transaction)) {
                        $this->notificationUtil->activityLog($transaction, 'sms_notification_sent', null, [], false);
                    }
                }
                if (in_array('whatsapp', $notification_type)) {
                    $whatsapp_link = $this->notificationUtil->getWhatsappNotificationLink($data);
                }
            }

            $output = ['success' => 1, 'msg' => __('lang_v1.notification_sent_successfully')];
            if (!empty($whatsapp_link)) {
                $output['whatsapp_link'] = $whatsapp_link;
            }
        } catch (\Exception $e) {
            dd("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = ['success' => 0,
                'msg' => $e->getMessage()
            ];
        }

        return $output;
    }
}
