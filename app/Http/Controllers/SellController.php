<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Contact;
use App\Models\CustomerGroup;
use App\Models\InvoiceScheme;
use App\Models\MasterList;
use App\Models\SellingPriceGroup;
use App\Models\TaxRate;
use App\Models\Transaction;
use App\Models\TransactionActivity;
use App\Models\TransactionSellLine;
use App\Models\TransactionSellLinesDay;
use App\Models\TypesOfService;
use App\Models\User;
use App\Models\VariationValueTemplate;
use App\Utils\AppConstant;
use App\Utils\BusinessUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use DB;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Product;
use App\Models\Media;
use App\Models\Variation;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class SellController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $contactUtil;
    protected $businessUtil;
    protected $transactionUtil;
    protected $productUtil;


    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ContactUtil $contactUtil, BusinessUtil $businessUtil, TransactionUtil $transactionUtil, ModuleUtil $moduleUtil, ProductUtil $productUtil)
    {
        $this->contactUtil = $contactUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->productUtil = $productUtil;

        $this->dummyPaymentLine = ['method' => '', 'amount' => 0, 'note' => '', 'card_transaction_number' => '', 'card_number' => '', 'card_type' => '', 'card_holder_name' => '', 'card_month' => '', 'card_year' => '', 'card_security' => '', 'cheque_number' => '', 'bank_account_number' => '',
            'is_return' => 0, 'transaction_no' => ''];

        $this->shipping_status_colors = [
            'ordered' => 'bg-yellow',
            'packed' => 'bg-info',
            'shipped' => 'bg-navy',
            'delivered' => 'bg-green',
            'cancelled' => 'bg-red',
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $is_admin = $this->businessUtil->is_admin(auth()->user());

        if (!$is_admin && !auth()->user()->hasAnyPermission(['sell.view', 'sell.create', 'direct_sell.access', 'direct_sell.view', 'view_own_sell_only', 'view_commission_agent_sell', 'access_shipping', 'access_own_shipping', 'access_commission_agent_shipping', 'so.view_all', 'so.view_own'])) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $is_woocommerce = $this->moduleUtil->isModuleInstalled('Woocommerce');
        $is_crm = $this->moduleUtil->isModuleInstalled('Crm');
        $is_tables_enabled = $this->transactionUtil->isModuleEnabled('tables');
        $is_service_staff_enabled = $this->transactionUtil->isModuleEnabled('service_staff');
        $is_types_service_enabled = $this->moduleUtil->isModuleEnabled('types_of_service');

        if (request()->ajax()) {
            $payment_types = $this->transactionUtil->payment_types(null, true, $business_id);
            $with = [];
            $shipping_statuses = $this->transactionUtil->shipping_statuses();$order_statuses = Transaction::sell_statuses();
            $order_statuses = Transaction::sell_statuses();
            $sale_type = !empty(request()->input('sale_type')) ? request()->input('sale_type') : 'sell';

            $sells = $this->transactionUtil->getListSells($business_id, $sale_type);

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $sells->whereIn('transactions.location_id', $permitted_locations);
            }

            //Add condition for created_by,used in sales representative sales report
            if (request()->has('created_by')) {
                $created_by = request()->get('created_by');
                if (!empty($created_by)) {
                    $sells->where('transactions.created_by', $created_by);
                }
            }

            $partial_permissions = ['view_own_sell_only', 'view_commission_agent_sell', 'access_own_shipping', 'access_commission_agent_shipping'];
            if (!auth()->user()->can('direct_sell.view')) {
                $sells->where(function ($q) {
                    if (auth()->user()->hasAnyPermission(['view_own_sell_only', 'access_own_shipping'])) {
                        $q->where('transactions.created_by', request()->session()->get('user.id'));
                    }

                    //if user is commission agent display only assigned sells
                    if (auth()->user()->hasAnyPermission(['view_commission_agent_sell', 'access_commission_agent_shipping'])) {
                        $q->orWhere('transactions.commission_agent', request()->session()->get('user.id'));
                    }
                });
            }
            if(!empty(request()->input('order_status'))){
                $sells->where('transactions.status',request()->input('order_status'));
            }
            $only_shipments = request()->only_shipments == 'true' ? true : false;
            if ($only_shipments) {
                $sells->whereNotNull('transactions.shipping_status');

                if (auth()->user()->hasAnyPermission(['access_pending_shipments_only'])) {
                    $sells->where('transactions.shipping_status', '!=', 'delivered');
                }
            }

            if (!$is_admin && !$only_shipments && $sale_type != 'sales_order') {
                $payment_status_arr = [];
                if (auth()->user()->can('view_paid_sells_only')) {
                    $payment_status_arr[] = 'paid';
                }

                if (auth()->user()->can('view_due_sells_only')) {
                    $payment_status_arr[] = 'due';
                }

                if (auth()->user()->can('view_partial_sells_only')) {
                    $payment_status_arr[] = 'partial';
                }

                if (empty($payment_status_arr)) {
                    if (auth()->user()->can('view_overdue_sells_only')) {
                        $sells->OverDue();
                    }
                } else {
                    if (auth()->user()->can('view_overdue_sells_only')) {
                        $sells->where(function ($q) use ($payment_status_arr) {
                            $q->whereIn('transactions.payment_status', $payment_status_arr)
                                ->orWhere(function ($qr) {
                                    $qr->OverDue();
                                });

                        });
                    } else {
                        $sells->whereIn('transactions.payment_status', $payment_status_arr);
                    }
                }
            }

            if (!empty(request()->input('payment_status')) && request()->input('payment_status') != 'overdue') {
                $sells->where('transactions.payment_status', request()->input('payment_status'));
            } elseif (request()->input('payment_status') == 'overdue') {
                $sells->whereIn('transactions.payment_status', ['due', 'partial'])
                    ->whereNotNull('transactions.pay_term_number')
                    ->whereNotNull('transactions.pay_term_type')
                    ->whereRaw("IF(transactions.pay_term_type='days', DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number DAY) < CURDATE(), DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number MONTH) < CURDATE())");
            }


            //Add condition for location,used in sales representative expense report
            if (request()->has('location_id')) {
                $location_id = request()->get('location_id');
                if (!empty($location_id)) {
                    $sells->where('transactions.location_id', $location_id);
                }
            }

            if (!empty(request()->input('rewards_only')) && request()->input('rewards_only') == true) {
                $sells->where(function ($q) {
                    $q->whereNotNull('transactions.rp_earned')
                        ->orWhere('transactions.rp_redeemed', '>', 0);
                });
            }

            if (!empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $sells->where('contacts.id', $customer_id);
            }
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end = request()->end_date;
                $sells->whereDate('transactions.transaction_date', '>=', $start)
                    ->whereDate('transactions.transaction_date', '<=', $end);
            }

            //Check is_direct sell
            if (request()->has('is_direct_sale')) {
                $is_direct_sale = request()->is_direct_sale;
                if ($is_direct_sale == 0) {
                    $sells->where('transactions.is_direct_sale', 0);
                    $sells->whereNull('transactions.sub_type');
                }
            }

            //Add condition for commission_agent,used in sales representative sales with commission report
            if (request()->has('commission_agent')) {
                $commission_agent = request()->get('commission_agent');
                if (!empty($commission_agent)) {
                    $sells->where('transactions.commission_agent', $commission_agent);
                }
            }

            if (!empty(request()->input('source'))) {
                //only exception for woocommerce
                if (request()->input('source') == 'woocommerce') {
                    $sells->whereNotNull('transactions.woocommerce_order_id');
                } else {
                    $sells->where('transactions.source', request()->input('source'));
                }

            }

            if ($is_crm) {
                $sells->addSelect('transactions.crm_is_order_request');

                if (request()->has('crm_is_order_request')) {
                    $sells->where('transactions.crm_is_order_request', 1);
                }
            }

            if (request()->only_subscriptions) {
                $sells->where(function ($q) {
                    $q->whereNotNull('transactions.recur_parent_id')
                        ->orWhere('transactions.is_recurring', 1);
                });
            }

            if (!empty(request()->list_for) && request()->list_for == 'service_staff_report') {
                $sells->whereNotNull('transactions.res_waiter_id');
            }

            if (!empty(request()->res_waiter_id)) {
                $sells->where('transactions.res_waiter_id', request()->res_waiter_id);
            }

            if (!empty(request()->input('sub_type'))) {
                $sells->where('transactions.sub_type', request()->input('sub_type'));
            }

            if (!empty(request()->input('created_by'))) {
                $sells->where('transactions.created_by', request()->input('created_by'));
            }

            if (!empty(request()->input('status'))) {
                $sells->where('transactions.status', request()->input('status'));
            }

            if (!empty(request()->input('sales_cmsn_agnt'))) {
                $sells->where('transactions.commission_agent', request()->input('sales_cmsn_agnt'));
            }

            if (!empty(request()->input('service_staffs'))) {
                $sells->where('transactions.res_waiter_id', request()->input('service_staffs'));
            }

            $only_pending_shipments = request()->only_pending_shipments == 'true' ? true : false;
            if ($only_pending_shipments) {
                $sells->where('transactions.shipping_status', '!=', 'delivered')
                    ->whereNotNull('transactions.shipping_status');
                $only_shipments = true;
            }

            if (!empty(request()->input('shipping_status'))) {
                $sells->where('transactions.shipping_status', request()->input('shipping_status'));
            }

            if (!empty(request()->input('for_dashboard_sales_order'))) {
                $sells->whereIn('transactions.status', ['partial', 'ordered'])
                    ->orHavingRaw('so_qty_remaining > 0');
            }

            if ($sale_type == 'sales_order') {
                if (!auth()->user()->can('so.view_all') && auth()->user()->can('so.view_own')) {
                    $sells->where('transactions.created_by', request()->session()->get('user.id'));
                }
            }

            $sells->groupBy('transactions.id');

            if (!empty(request()->suspended)) {
                $transaction_sub_type = request()->get('transaction_sub_type');
                if (!empty($transaction_sub_type)) {
                    $sells->where('transactions.sub_type', $transaction_sub_type);
                } else {
                    $sells->where('transactions.sub_type', null);
                }

                $with = ['sell_lines'];

                if ($is_tables_enabled) {
                    $with[] = 'table';
                }

                if ($is_service_staff_enabled) {
                    $with[] = 'service_staff';
                }

                $sales = $sells->where('transactions.is_suspend', 1)
                    ->with($with)
                    ->addSelect('transactions.is_suspend', 'transactions.res_table_id', 'transactions.res_waiter_id', 'transactions.additional_notes')
                    ->get();

                return view('sale_pos.partials.suspended_sales_modal')->with(compact('sales', 'is_tables_enabled', 'is_service_staff_enabled', 'transaction_sub_type'));
            }

            $with[] = 'payment_lines';
            if (!empty($with)) {
                $sells->with($with);
            }

            //$business_details = $this->businessUtil->getDetails($business_id);
            if ($this->businessUtil->isModuleEnabled('subscription')) {
                $sells->addSelect('transactions.is_recurring', 'transactions.recur_parent_id');
            }
            $sales_order_statuses = Transaction::sales_order_statuses();

            $datatable = Datatables::of($sells)
                ->addColumn(
                    'action',
                    function ($row) use ($only_shipments, $is_admin, $sale_type) {
                        $html = '<div class="btn-group">
                                    <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                                        data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                        </span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                        if (auth()->user()->can("sell.view") || auth()->user()->can("direct_sell.view") || auth()->user()->can("view_own_sell_only")) {
                            $html .= '<li><a href="#" data-href="' . action("SellController@show", [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fas fa-eye" aria-hidden="true"></i> ' . __("messages.view") . '</a></li>';
                        }
                        if (!$only_shipments) {
                            if ($row->is_direct_sale == 0) {
                                if (auth()->user()->can("sell.update")) {
                                    $html .= '<li><a target="_blank" href="' . action('SellPosController@edit', [$row->id]) . '"><i class="fas fa-edit"></i> ' . __("messages.edit") . '</a></li>';
                                }
                            } elseif ($row->type == 'sales_order') {
                                if (auth()->user()->can("so.update")) {
                                    $html .= '<li><a target="_blank" href="' . action('SellController@edit', [$row->id]) . '"><i class="fas fa-edit"></i> ' . __("messages.edit") . '</a></li>';
                                }
                            } else {
                                if (auth()->user()->can("direct_sell.update")) {
                                    $html .= '<li><a target="_blank" href="' . action('SellController@edit', [$row->id]) . '"><i class="fas fa-edit"></i> ' . __("messages.edit") . '</a></li>';
                                }
                            }

                            $delete_link = '<li><a href="' . action('SellPosController@destroy', [$row->id]) . '" class="delete-sale"><i class="fas fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                            if ($row->is_direct_sale == 0) {
                                if (auth()->user()->can("sell.delete")) {
                                    $html .= $delete_link;
                                }
                            } elseif ($row->type == 'sales_order') {
                                if (auth()->user()->can("so.delete")) {
                                    $html .= $delete_link;
                                }
                            } else {
                                if (auth()->user()->can("direct_sell.delete")) {
                                    $html .= $delete_link;
                                }
                            }
                        }

                        if (config('constants.enable_download_pdf') && auth()->user()->can("print_invoice") && $sale_type != 'sales_order') {
                            $html .= '<li><a href="' . route('sell.downloadPdf', [$row->id]) . '" target="_blank"><i class="fas fa-print" aria-hidden="true"></i> ' . __("lang_v1.download_pdf") . '</a></li>';

                            if (!empty($row->shipping_status)) {
                                $html .= '<li><a href="' . route('packing.downloadPdf', [$row->id]) . '" target="_blank"><i class="fas fa-print" aria-hidden="true"></i> ' . __("lang_v1.download_paking_pdf") . '</a></li>';
                            }
                        }

                        if (auth()->user()->can("sell.view") || auth()->user()->can("direct_sell.access")) {
                            if (!empty($row->document)) {
                                $document_name = !empty(explode("_", $row->document, 2)[1]) ? explode("_", $row->document, 2)[1] : $row->document;
                                $html .= '<li><a href="' . url('storage/documents/' . $row->document) . '" download="' . $document_name . '"><i class="fas fa-download" aria-hidden="true"></i>' . __("purchase.download_document") . '</a></li>';
                                if (isFileImage($document_name)) {
                                    $html .= '<li><a href="#" data-href="' . url('storage/documents/' . $row->document) . '" class="view_uploaded_document"><i class="fas fa-image" aria-hidden="true"></i>' . __("lang_v1.view_document") . '</a></li>';
                                }
                            }
                        }

                        if ($is_admin || auth()->user()->hasAnyPermission(['access_shipping', 'access_own_shipping', 'access_commission_agent_shipping'])) {
                            $html .= '<li><a href="#" data-href="' . action('SellController@editShipping', [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fas fa-truck" aria-hidden="true"></i>' . __("lang_v1.edit_shipping") . '</a></li>';
                        }

                        if ($row->type == 'sell') {
                            if (auth()->user()->can("print_invoice")) {
                                $html .= '<li><a href="#" class="print-invoice" data-href="' . route('sell.printInvoice', [$row->id]) . '"><i class="fas fa-print" aria-hidden="true"></i> ' . __("lang_v1.print_invoice") . '</a></li>';
                            }
                            $html .= '<li class="divider"></li>';
                            if (!$only_shipments) {

                                if (auth()->user()->can("sell.payments") ||
                                    auth()->user()->can("edit_sell_payment") ||
                                    auth()->user()->can("delete_sell_payment")) {

                                    if ($row->payment_status != "paid") {
                                        $html .= '<li><a href="' . action('TransactionPaymentController@addPayment', [$row->id]) . '" class="add_payment_modal"><i class="fas fa-money-bill-alt"></i> ' . __("purchase.add_payment") . '</a></li>';
                                    }

                                    $html .= '<li><a href="' . action('TransactionPaymentController@show', [$row->id]) . '" class="view_payment_modal"><i class="fas fa-money-bill-alt"></i> ' . __("purchase.view_payments") . '</a></li>';
                                }

                                if (auth()->user()->can("sell.create") || auth()->user()->can("direct_sell.access")) {
                                    // $html .= '<li><a href="' . action('SellController@duplicateSell', [$row->id]) . '"><i class="fas fa-copy"></i> ' . __("lang_v1.duplicate_sell") . '</a></li>';


                                    $html .= '<li><a href="' . action('SellReturnController@add', [$row->id]) . '"><i class="fas fa-undo"></i> ' . __("lang_v1.sell_return") . '</a></li>

                                    <li><a href="' . action('SellPosController@showInvoiceUrl', [$row->id]) . '" class="view_invoice_url"><i class="fas fa-eye"></i> ' . __("lang_v1.view_invoice_url") . '</a></li>';
                                }
                            }

                            $html .= '<li><a href="#" data-href="' . action('NotificationController@getTemplate', ["transaction_id" => $row->id, "template_for" => "new_sale"]) . '" class="btn-modal" data-container=".view_modal"><i class="fa fa-envelope" aria-hidden="true"></i>' . __("lang_v1.sale_notification") . '</a></li>';
                        } else {
                            $html .= '<li><a href="#" data-href="' . action('SellController@viewMedia', ["model_id" => $row->id, "model_type" => "App\Models\Transaction", 'model_media_type' => 'shipping_document']) . '" class="btn-modal" data-container=".view_modal"><i class="fas fa-paperclip" aria-hidden="true"></i>' . __("lang_v1.shipping_documents") . '</a></li>';
                        }

                        $html .= '</ul></div>';

                        return $html;
                    }
                )
                ->removeColumn('id')
                ->editColumn(
                    'final_total',
                    '<span class="final-total" data-orig-value="{{$final_total}}">@format_currency($final_total)</span>'
                )
                ->editColumn(
                    'tax_amount',
                    '<span class="total-tax" data-orig-value="{{$tax_amount}}">@format_currency($tax_amount)</span>'
                )
                ->editColumn(
                    'total_paid',
                    '<span class="total-paid" data-orig-value="{{$total_paid}}">@format_currency($total_paid)</span>'
                )
                ->editColumn(
                    'total_before_tax',
                    '<span class="total_before_tax" data-orig-value="{{$total_before_tax}}">@format_currency($total_before_tax)</span>'
                )
                ->editColumn(
                    'discount_amount',
                    function ($row) {
                        $discount = !empty($row->discount_amount) ? $row->discount_amount : 0;

                        if (!empty($discount) && $row->discount_type == 'percentage') {
                            $discount = $row->total_before_tax * ($discount / 100);
                        }

                        return '<span class="total-discount" data-orig-value="' . $discount . '">' . $this->transactionUtil->num_f($discount, true) . '</span>';
                    }
                )
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn(
                    'payment_status',
                    function ($row) {
                        $payment_status = Transaction::getPaymentStatus($row);
                        return (string)view('sell.partials.payment_status', ['payment_status' => $payment_status, 'id' => $row->id]);
                    }
                )
                ->editColumn(
                    'sell_status',
                    function ($row) {
                        return $row->status;
                    }
                )
                ->editColumn(
                    'types_of_service_name',
                    '<span class="service-type-label" data-orig-value="{{$types_of_service_name}}" data-status-name="{{$types_of_service_name}}">{{$types_of_service_name}}</span>'
                )
                ->addColumn('total_remaining', function ($row) {
                    $total_remaining = $row->final_total - $row->total_paid;
                    $total_remaining_html = '<span class="payment_due" data-orig-value="' . $total_remaining . '">' . $this->transactionUtil->num_f($total_remaining, true) . '</span>';

                    return $total_remaining_html;
                })
                ->addColumn('return_due', function ($row) {
                    $return_due_html = '';
                    if (!empty($row->return_exists)) {
                        $return_due = $row->amount_return - $row->return_paid;
                        $return_due_html .= '<a href="' . action("TransactionPaymentController@show", [$row->return_transaction_id]) . '" class="view_purchase_return_payment_modal"><span class="sell_return_due" data-orig-value="' . $return_due . '">' . $this->transactionUtil->num_f($return_due, true) . '</span></a>';
                    }
                    return $return_due_html;
                })
                ->editColumn('invoice_no', function ($row) use ($is_crm) {
                    $invoice_no = $row->invoice_no;
                    if (!empty($row->woocommerce_order_id)) {
                        $invoice_no .= ' <i class="fab fa-wordpress text-primary no-print" title="' . __('lang_v1.synced_from_woocommerce') . '"></i>';
                    }
                    if (!empty($row->return_exists)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-red label-round no-print" title="' . __('lang_v1.some_qty_returned_from_sell') . '"><i class="fas fa-undo"></i></small>';
                    }
                    if (!empty($row->is_recurring)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-red label-round no-print" title="' . __('lang_v1.subscribed_invoice') . '"><i class="fas fa-recycle"></i></small>';
                    }

                    if (!empty($row->recur_parent_id)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-info label-round no-print" title="' . __('lang_v1.subscription_invoice') . '"><i class="fas fa-recycle"></i></small>';
                    }

                    if (!empty($row->is_export)) {
                        $invoice_no .= '</br><small class="label label-default no-print" title="' . __('lang_v1.export') . '">' . __('lang_v1.export') . '</small>';
                    }

                    if ($is_crm && !empty($row->crm_is_order_request)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-yellow label-round no-print" title="' . __('crm::lang.order_request') . '"><i class="fas fa-tasks"></i></small>';
                    }

                    return $invoice_no;
                })
                ->editColumn('shipping_status', function ($row) use ($shipping_statuses) {
                    $status_color = !empty($this->shipping_status_colors[$row->shipping_status]) ? $this->shipping_status_colors[$row->shipping_status] : 'bg-gray';
                    $status = !empty($row->shipping_status) ? '<a href="#" class="btn-modal" data-href="' . action('SellController@editShipping', [$row->id]) . '" data-container=".view_modal"><span class="label ' . $status_color . '">' . $shipping_statuses[$row->shipping_status] . '</span></a>' : '';

                    return $status;
                })
                ->addColumn('conatct_name', '@if(!empty($supplier_business_name)) {{$supplier_business_name}}, <br> @endif {{$name}}')
                ->editColumn('total_items', '{{@format_quantity($total_items)}}')
                ->filterColumn('conatct_name', function ($query, $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('contacts.name', 'like', "%{$keyword}%")
                            ->orWhere('contacts.supplier_business_name', 'like', "%{$keyword}%");
                    });
                })
                ->addColumn('payment_methods', function ($row) use ($payment_types) {
                    $methods = array_unique($row->payment_lines->pluck('method')->toArray());
                    $count = count($methods);
                    $payment_method = '';
                    if ($count == 1) {
                        $payment_method = $payment_types[$methods[0]];
                    } elseif ($count > 1) {
                        $payment_method = __('lang_v1.checkout_multi_pay');
                    }

                    $html = !empty($payment_method) ? '<span class="payment-method" data-orig-value="' . $payment_method . '" data-status-name="' . $payment_method . '">' . $payment_method . '</span>' : '';

                    return $html;
                })
                ->editColumn('status', function ($row) use ($sales_order_statuses, $is_admin) {
                    $status = '';

                    if ($row->type == 'sales_order') {
                        if ($is_admin && $row->status != 'completed') {
                            $status = '<span class="edit-so-status label ' . $sales_order_statuses[$row->status]['class'] . '" data-href="' . action("SalesOrderController@getEditSalesOrderStatus", ['id' => $row->id]) . '">' . $sales_order_statuses[$row->status]['label'] . '</span>';
                        } else {
                            $status = '<span class="label ' . $sales_order_statuses[$row->status]['class'] . '" >' . $sales_order_statuses[$row->status]['label'] . '</span>';
                        }
                    }

                    return $status;
                })
                ->editColumn('so_qty_remaining', '{{@format_quantity($so_qty_remaining)}}')
                ->addColumn(
                    'shipping_details',
                    function ($row) {
                        $value =  $row->shipping_address_line_1.', <br>'.
                            $row->shipping_address_line_2 .', <br>';
                        if (!empty($row->contact->shipping_city)) {
                            $value .= $row->contact->shipping_city.', <br>';
                        }
                        if (!empty($row->contact->shipping_state)) {
                            $value .= $row->contact->shipping_state.', <br>';
                        }
                        if (!empty($row->contact->shipping_country)) {
                            $value .= $row->contact->shipping_country.', <br>';
                        }
                        if (!empty($row->contact->shipping_zipcode)) {
                            $value .= $row->contact->shipping_zipcode.', <br>';
                        }
                        if (!empty($row->shipping_custom_field_1)) {
                            $value .= $row->shipping_custom_field_1;
                        }
                        if (!empty($row->shipping_custom_field_2)) {
                            $value .= $row->shipping_custom_field_2;
                        }
                        if (!empty($row->shipping_custom_field_3)) {
                            $value .= $row->shipping_custom_field_3;
                        }
                        if (!empty($row->shipping_custom_field_4)) {
                            $value .= $row->shipping_custom_field_4;
                        }
                        if (!empty($row->shipping_custom_field_5)) {
                            $value .= $row->shipping_custom_field_5;
                        }
                        return $value;
                    }
                )
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("sell.view") || auth()->user()->can("view_own_sell_only")) {
                            return action('SellController@show', [$row->id]);
                        } else {
                            return '';
                        }
                    }]);

            $rawColumns = ['final_total', 'action', 'total_paid', 'total_remaining', 'payment_status', 'invoice_no', 'discount_amount', 'tax_amount', 'total_before_tax', 'shipping_status', 'types_of_service_name', 'payment_methods', 'return_due', 'conatct_name', 'status', 'shipping_details'];

            return $datatable->rawColumns($rawColumns)
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);
        $sales_representative = User::forDropdown($business_id, false, false, true);

        //Commission agent filter
        $is_cmsn_agent_enabled = request()->session()->get('business.sales_cmsn_agnt');
        $commission_agents = [];
        if (!empty($is_cmsn_agent_enabled)) {
            $commission_agents = User::forDropdown($business_id, false, true, true);
        }

        //Service staff filter
        $service_staffs = null;
        if ($this->productUtil->isModuleEnabled('service_staff')) {
            $service_staffs = $this->productUtil->serviceStaffDropdown($business_id);
        }

        $shipping_statuses = $this->transactionUtil->shipping_statuses();$order_statuses = Transaction::sell_statuses();

        $sources = $this->transactionUtil->getSources($business_id);
        if ($is_woocommerce) {
            $sources['woocommerce'] = 'Woocommerce';
        }

        return view('sell.index')
            ->with(compact('business_locations', 'customers', 'is_woocommerce', 'sales_representative', 'is_cmsn_agent_enabled', 'commission_agents', 'service_staffs', 'is_tables_enabled', 'is_service_staff_enabled', 'is_types_service_enabled', 'shipping_statuses','order_statuses', 'sources'));
    }


    public function master()
    {
        $is_admin = $this->businessUtil->is_admin(auth()->user());

        if (!$is_admin && !auth()->user()->hasAnyPermission(['sell.view', 'sell.create', 'direct_sell.access', 'direct_sell.view', 'view_own_sell_only', 'view_commission_agent_sell', 'access_shipping', 'access_own_shipping', 'access_commission_agent_shipping', 'so.view_all', 'so.view_own'])) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $is_woocommerce = $this->moduleUtil->isModuleInstalled('Woocommerce');
        $is_crm = $this->moduleUtil->isModuleInstalled('Crm');
        $is_tables_enabled = $this->transactionUtil->isModuleEnabled('tables');
        $is_service_staff_enabled = $this->transactionUtil->isModuleEnabled('service_staff');
        $is_types_service_enabled = $this->moduleUtil->isModuleEnabled('types_of_service');

        if (request()->ajax()) {
            $payment_types = $this->transactionUtil->payment_types(null, true, $business_id);
            $with = [];
            $shipping_statuses = $this->transactionUtil->shipping_statuses();$order_statuses = Transaction::sell_statuses();

            $sale_type = !empty(request()->input('sale_type')) ? request()->input('sale_type') : 'sell';

            $sells = $this->transactionUtil->getListSells($business_id, $sale_type);

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $sells->whereIn('transactions.location_id', $permitted_locations);
            }

            //Add condition for created_by,used in sales representative sales report
            if (request()->has('created_by')) {
                $created_by = request()->get('created_by');
                if (!empty($created_by)) {
                    $sells->where('transactions.created_by', $created_by);
                }
            }

            $partial_permissions = ['view_own_sell_only', 'view_commission_agent_sell', 'access_own_shipping', 'access_commission_agent_shipping'];
            if (!auth()->user()->can('direct_sell.view')) {
                $sells->where(function ($q) {
                    if (auth()->user()->hasAnyPermission(['view_own_sell_only', 'access_own_shipping'])) {
                        $q->where('transactions.created_by', request()->session()->get('user.id'));
                    }

                    //if user is commission agent display only assigned sells
                    if (auth()->user()->hasAnyPermission(['view_commission_agent_sell', 'access_commission_agent_shipping'])) {
                        $q->orWhere('transactions.commission_agent', request()->session()->get('user.id'));
                    }
                });
            }

            $only_shipments = request()->only_shipments == 'true' ? true : false;
            if ($only_shipments) {
                $sells->whereNotNull('transactions.shipping_status');

                if (auth()->user()->hasAnyPermission(['access_pending_shipments_only'])) {
                    $sells->where('transactions.shipping_status', '!=', 'delivered');
                }
            }

            if (!$is_admin && !$only_shipments && $sale_type != 'sales_order') {
                $payment_status_arr = [];
                if (auth()->user()->can('view_paid_sells_only')) {
                    $payment_status_arr[] = 'paid';
                }

                if (auth()->user()->can('view_due_sells_only')) {
                    $payment_status_arr[] = 'due';
                }

                if (auth()->user()->can('view_partial_sells_only')) {
                    $payment_status_arr[] = 'partial';
                }

                if (empty($payment_status_arr)) {
                    if (auth()->user()->can('view_overdue_sells_only')) {
                        $sells->OverDue();
                    }
                } else {
                    if (auth()->user()->can('view_overdue_sells_only')) {
                        $sells->where(function ($q) use ($payment_status_arr) {
                            $q->whereIn('transactions.payment_status', $payment_status_arr)
                                ->orWhere(function ($qr) {
                                    $qr->OverDue();
                                });

                        });
                    } else {
                        $sells->whereIn('transactions.payment_status', $payment_status_arr);
                    }
                }
            }

            if (!empty(request()->input('payment_status')) && request()->input('payment_status') != 'overdue') {
                $sells->where('transactions.payment_status', request()->input('payment_status'));
            } elseif (request()->input('payment_status') == 'overdue') {
                $sells->whereIn('transactions.payment_status', ['due', 'partial'])
                    ->whereNotNull('transactions.pay_term_number')
                    ->whereNotNull('transactions.pay_term_type')
                    ->whereRaw("IF(transactions.pay_term_type='days', DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number DAY) < CURDATE(), DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number MONTH) < CURDATE())");
            }


            //Add condition for location,used in sales representative expense report
            if (request()->has('location_id')) {
                $location_id = request()->get('location_id');
                if (!empty($location_id)) {
                    $sells->where('transactions.location_id', $location_id);
                }
            }

            if (!empty(request()->input('rewards_only')) && request()->input('rewards_only') == true) {
                $sells->where(function ($q) {
                    $q->whereNotNull('transactions.rp_earned')
                        ->orWhere('transactions.rp_redeemed', '>', 0);
                });
            }

            if (!empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $sells->where('contacts.id', $customer_id);
            }
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end = request()->end_date;
                $sells->whereDate('transactions.transaction_date', '>=', $start)
                    ->whereDate('transactions.transaction_date', '<=', $end);
            }

            //Check is_direct sell
            if (request()->has('is_direct_sale')) {
                $is_direct_sale = request()->is_direct_sale;
                if ($is_direct_sale == 0) {
                    $sells->where('transactions.is_direct_sale', 0);
                    $sells->whereNull('transactions.sub_type');
                }
            }

            //Add condition for commission_agent,used in sales representative sales with commission report
            if (request()->has('commission_agent')) {
                $commission_agent = request()->get('commission_agent');
                if (!empty($commission_agent)) {
                    $sells->where('transactions.commission_agent', $commission_agent);
                }
            }

            if (!empty(request()->input('source'))) {
                //only exception for woocommerce
                if (request()->input('source') == 'woocommerce') {
                    $sells->whereNotNull('transactions.woocommerce_order_id');
                } else {
                    $sells->where('transactions.source', request()->input('source'));
                }

            }

            if ($is_crm) {
                $sells->addSelect('transactions.crm_is_order_request');

                if (request()->has('crm_is_order_request')) {
                    $sells->where('transactions.crm_is_order_request', 1);
                }
            }

            if (request()->only_subscriptions) {
                $sells->where(function ($q) {
                    $q->whereNotNull('transactions.recur_parent_id')
                        ->orWhere('transactions.is_recurring', 1);
                });
            }

            if (!empty(request()->list_for) && request()->list_for == 'service_staff_report') {
                $sells->whereNotNull('transactions.res_waiter_id');
            }

            if (!empty(request()->res_waiter_id)) {
                $sells->where('transactions.res_waiter_id', request()->res_waiter_id);
            }

            if (!empty(request()->input('sub_type'))) {
                $sells->where('transactions.sub_type', request()->input('sub_type'));
            }

            if (!empty(request()->input('created_by'))) {
                $sells->where('transactions.created_by', request()->input('created_by'));
            }

            if (!empty(request()->input('status'))) {
                $sells->where('transactions.status', request()->input('status'));
            }

            if (!empty(request()->input('sales_cmsn_agnt'))) {
                $sells->where('transactions.commission_agent', request()->input('sales_cmsn_agnt'));
            }

            if (!empty(request()->input('service_staffs'))) {
                $sells->where('transactions.res_waiter_id', request()->input('service_staffs'));
            }

            $only_pending_shipments = request()->only_pending_shipments == 'true' ? true : false;
            if ($only_pending_shipments) {
                $sells->where('transactions.shipping_status', '!=', 'delivered')
                    ->whereNotNull('transactions.shipping_status');
                $only_shipments = true;
            }

            if (!empty(request()->input('shipping_status'))) {
                $sells->where('transactions.shipping_status', request()->input('shipping_status'));
            }

            if (!empty(request()->input('for_dashboard_sales_order'))) {
                $sells->whereIn('transactions.status', ['partial', 'ordered'])
                    ->orHavingRaw('so_qty_remaining > 0');
            }

            if ($sale_type == 'sales_order') {
                if (!auth()->user()->can('so.view_all') && auth()->user()->can('so.view_own')) {
                    $sells->where('transactions.created_by', request()->session()->get('user.id'));
                }
            }

            $sells->groupBy('transactions.id');

            if (!empty(request()->suspended)) {
                $transaction_sub_type = request()->get('transaction_sub_type');
                if (!empty($transaction_sub_type)) {
                    $sells->where('transactions.sub_type', $transaction_sub_type);
                } else {
                    $sells->where('transactions.sub_type', null);
                }

                $with = ['sell_lines'];

                if ($is_tables_enabled) {
                    $with[] = 'table';
                }

                if ($is_service_staff_enabled) {
                    $with[] = 'service_staff';
                }

                $sales = $sells->where('transactions.is_suspend', 1)
                    ->with($with)
                    ->addSelect('transactions.is_suspend', 'transactions.res_table_id', 'transactions.res_waiter_id', 'transactions.additional_notes')
                    ->get();

                return view('sale_pos.partials.suspended_sales_modal')->with(compact('sales', 'is_tables_enabled', 'is_service_staff_enabled', 'transaction_sub_type'));
            }

            $with[] = 'payment_lines';
            if (!empty($with)) {
                $sells->with($with);
            }

            //$business_details = $this->businessUtil->getDetails($business_id);
            if ($this->businessUtil->isModuleEnabled('subscription')) {
                $sells->addSelect('transactions.is_recurring', 'transactions.recur_parent_id');
            }
            $sales_order_statuses = Transaction::sales_order_statuses();
            $datatable = Datatables::of($sells)
                ->addColumn(
                    'action',
                    function ($row) use ($only_shipments, $is_admin, $sale_type) {
                        $html = '<div class="btn-group">
                                    <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                                        data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                        </span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                        if (auth()->user()->can("sell.view") || auth()->user()->can("direct_sell.view") || auth()->user()->can("view_own_sell_only")) {
                            $html .= '<li><a href="#" data-href="' . action("SellController@show", [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fas fa-eye" aria-hidden="true"></i> ' . __("messages.view") . '</a></li>';
                        }
                        if (!$only_shipments) {
                            if ($row->is_direct_sale == 0) {
                                if (auth()->user()->can("sell.update")) {
                                    $html .= '<li><a target="_blank" href="' . action('SellPosController@edit', [$row->id]) . '"><i class="fas fa-edit"></i> ' . __("messages.edit") . '</a></li>';
                                }
                            } elseif ($row->type == 'sales_order') {
                                if (auth()->user()->can("so.update")) {
                                    $html .= '<li><a target="_blank" href="' . action('SellController@edit', [$row->id]) . '"><i class="fas fa-edit"></i> ' . __("messages.edit") . '</a></li>';
                                }
                            } else {
                                if (auth()->user()->can("direct_sell.update")) {
                                    $html .= '<li><a target="_blank" href="' . action('SellController@edit', [$row->id]) . '"><i class="fas fa-edit"></i> ' . __("messages.edit") . '</a></li>';
                                }
                            }

                            $delete_link = '<li><a href="' . action('SellPosController@destroy', [$row->id]) . '" class="delete-sale"><i class="fas fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                            if ($row->is_direct_sale == 0) {
                                if (auth()->user()->can("sell.delete")) {
                                    $html .= $delete_link;
                                }
                            } elseif ($row->type == 'sales_order') {
                                if (auth()->user()->can("so.delete")) {
                                    $html .= $delete_link;
                                }
                            } else {
                                if (auth()->user()->can("direct_sell.delete")) {
                                    $html .= $delete_link;
                                }
                            }
                        }

                        if (config('constants.enable_download_pdf') && auth()->user()->can("print_invoice") && $sale_type != 'sales_order') {
                            $html .= '<li><a href="' . route('sell.downloadPdf', [$row->id]) . '" target="_blank"><i class="fas fa-print" aria-hidden="true"></i> ' . __("lang_v1.download_pdf") . '</a></li>';

                            if (!empty($row->shipping_status)) {
                                $html .= '<li><a href="' . route('packing.downloadPdf', [$row->id]) . '" target="_blank"><i class="fas fa-print" aria-hidden="true"></i> ' . __("lang_v1.download_paking_pdf") . '</a></li>';
                            }
                        }

                        if (auth()->user()->can("sell.view") || auth()->user()->can("direct_sell.access")) {
                            if (!empty($row->document)) {
                                $document_name = !empty(explode("_", $row->document, 2)[1]) ? explode("_", $row->document, 2)[1] : $row->document;
                                $html .= '<li><a href="' . url('uploads/documents/' . $row->document) . '" download="' . $document_name . '"><i class="fas fa-download" aria-hidden="true"></i>' . __("purchase.download_document") . '</a></li>';
                                if (isFileImage($document_name)) {
                                    $html .= '<li><a href="#" data-href="' . url('uploads/documents/' . $row->document) . '" class="view_uploaded_document"><i class="fas fa-image" aria-hidden="true"></i>' . __("lang_v1.view_document") . '</a></li>';
                                }
                            }
                        }

                        if ($is_admin || auth()->user()->hasAnyPermission(['access_shipping', 'access_own_shipping', 'access_commission_agent_shipping'])) {
                            $html .= '<li><a href="#" data-href="' . action('SellController@editShipping', [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fas fa-truck" aria-hidden="true"></i>' . __("lang_v1.edit_shipping") . '</a></li>';
                        }

                        if ($row->type == 'sell') {
                            if (auth()->user()->can("print_invoice")) {
                                $html .= '<li><a href="#" class="print-invoice" data-href="' . route('sell.printInvoice', [$row->id]) . '"><i class="fas fa-print" aria-hidden="true"></i> ' . __("lang_v1.print_invoice") . '</a></li>';
                            }
                            $html .= '<li class="divider"></li>';
                            if (!$only_shipments) {

                                if (auth()->user()->can("sell.payments") ||
                                    auth()->user()->can("edit_sell_payment") ||
                                    auth()->user()->can("delete_sell_payment")) {

                                    if ($row->payment_status != "paid") {
                                        $html .= '<li><a href="' . action('TransactionPaymentController@addPayment', [$row->id]) . '" class="add_payment_modal"><i class="fas fa-money-bill-alt"></i> ' . __("purchase.add_payment") . '</a></li>';
                                    }

                                    $html .= '<li><a href="' . action('TransactionPaymentController@show', [$row->id]) . '" class="view_payment_modal"><i class="fas fa-money-bill-alt"></i> ' . __("purchase.view_payments") . '</a></li>';
                                }

                                if (auth()->user()->can("sell.create") || auth()->user()->can("direct_sell.access")) {
                                    // $html .= '<li><a href="' . action('SellController@duplicateSell', [$row->id]) . '"><i class="fas fa-copy"></i> ' . __("lang_v1.duplicate_sell") . '</a></li>';


                                    $html .= '<li><a href="' . action('SellReturnController@add', [$row->id]) . '"><i class="fas fa-undo"></i> ' . __("lang_v1.sell_return") . '</a></li>

                                    <li><a href="' . action('SellPosController@showInvoiceUrl', [$row->id]) . '" class="view_invoice_url"><i class="fas fa-eye"></i> ' . __("lang_v1.view_invoice_url") . '</a></li>';
                                }
                            }

                            $html .= '<li><a href="#" data-href="' . action('NotificationController@getTemplate', ["transaction_id" => $row->id, "template_for" => "new_sale"]) . '" class="btn-modal" data-container=".view_modal"><i class="fa fa-envelope" aria-hidden="true"></i>' . __("lang_v1.sale_notification") . '</a></li>';
                        } else {
                            $html .= '<li><a href="#" data-href="' . action('SellController@viewMedia', ["model_id" => $row->id, "model_type" => "App\Models\Transaction", 'model_media_type' => 'shipping_document']) . '" class="btn-modal" data-container=".view_modal"><i class="fas fa-paperclip" aria-hidden="true"></i>' . __("lang_v1.shipping_documents") . '</a></li>';
                        }

                        $html .= '</ul></div>';

                        return $html;
                    }
                )
                ->removeColumn('id')
                ->editColumn(
                    'final_total',
                    '<span class="final-total" data-orig-value="{{$final_total}}">@format_currency($final_total)</span>'
                )
                ->editColumn(
                    'tax_amount',
                    '<span class="total-tax" data-orig-value="{{$tax_amount}}">@format_currency($tax_amount)</span>'
                )
                ->editColumn(
                    'total_paid',
                    '<span class="total-paid" data-orig-value="{{$total_paid}}">@format_currency($total_paid)</span>'
                )
                ->editColumn(
                    'total_before_tax',
                    '<span class="total_before_tax" data-orig-value="{{$total_before_tax}}">@format_currency($total_before_tax)</span>'
                )
                ->editColumn(
                    'discount_amount',
                    function ($row) {
                        $discount = !empty($row->discount_amount) ? $row->discount_amount : 0;

                        if (!empty($discount) && $row->discount_type == 'percentage') {
                            $discount = $row->total_before_tax * ($discount / 100);
                        }

                        return '<span class="total-discount" data-orig-value="' . $discount . '">' . $this->transactionUtil->num_f($discount, true) . '</span>';
                    }
                )
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn(
                    'payment_status',
                    function ($row) {
                        $payment_status = Transaction::getPaymentStatus($row);
                        return (string)view('sell.partials.payment_status', ['payment_status' => $payment_status, 'id' => $row->id]);
                    }
                )
                ->editColumn(
                    'types_of_service_name',
                    '<span class="service-type-label" data-orig-value="{{$types_of_service_name}}" data-status-name="{{$types_of_service_name}}">{{$types_of_service_name}}</span>'
                )
                ->addColumn('total_remaining', function ($row) {
                    $total_remaining = $row->final_total - $row->total_paid;
                    $total_remaining_html = '<span class="payment_due" data-orig-value="' . $total_remaining . '">' . $this->transactionUtil->num_f($total_remaining, true) . '</span>';


                    return $total_remaining_html;
                })
                ->addColumn('return_due', function ($row) {
                    $return_due_html = '';
                    if (!empty($row->return_exists)) {
                        $return_due = $row->amount_return - $row->return_paid;
                        $return_due_html .= '<a href="' . action("TransactionPaymentController@show", [$row->return_transaction_id]) . '" class="view_purchase_return_payment_modal"><span class="sell_return_due" data-orig-value="' . $return_due . '">' . $this->transactionUtil->num_f($return_due, true) . '</span></a>';
                    }

                    return $return_due_html;
                })
                ->editColumn('invoice_no', function ($row) use ($is_crm) {
                    $invoice_no = $row->invoice_no;
                    if (!empty($row->woocommerce_order_id)) {
                        $invoice_no .= ' <i class="fab fa-wordpress text-primary no-print" title="' . __('lang_v1.synced_from_woocommerce') . '"></i>';
                    }
                    if (!empty($row->return_exists)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-red label-round no-print" title="' . __('lang_v1.some_qty_returned_from_sell') . '"><i class="fas fa-undo"></i></small>';
                    }
                    if (!empty($row->is_recurring)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-red label-round no-print" title="' . __('lang_v1.subscribed_invoice') . '"><i class="fas fa-recycle"></i></small>';
                    }

                    if (!empty($row->recur_parent_id)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-info label-round no-print" title="' . __('lang_v1.subscription_invoice') . '"><i class="fas fa-recycle"></i></small>';
                    }

                    if (!empty($row->is_export)) {
                        $invoice_no .= '</br><small class="label label-default no-print" title="' . __('lang_v1.export') . '">' . __('lang_v1.export') . '</small>';
                    }

                    if ($is_crm && !empty($row->crm_is_order_request)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-yellow label-round no-print" title="' . __('crm::lang.order_request') . '"><i class="fas fa-tasks"></i></small>';
                    }

                    return $invoice_no;
                })
                ->editColumn('shipping_status', function ($row) use ($shipping_statuses) {
                    $status_color = !empty($this->shipping_status_colors[$row->shipping_status]) ? $this->shipping_status_colors[$row->shipping_status] : 'bg-gray';
                    $status = !empty($row->shipping_status) ? '<a href="#" class="btn-modal" data-href="' . action('SellController@editShipping', [$row->id]) . '" data-container=".view_modal"><span class="label ' . $status_color . '">' . $shipping_statuses[$row->shipping_status] . '</span></a>' : '';

                    return $status;
                })
                ->addColumn('conatct_name', '@if(!empty($supplier_business_name)) {{$supplier_business_name}}, <br> @endif {{$name}}')
                ->editColumn('total_items', '{{@format_quantity($total_items)}}')
                ->filterColumn('conatct_name', function ($query, $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('contacts.name', 'like', "%{$keyword}%")
                            ->orWhere('contacts.supplier_business_name', 'like', "%{$keyword}%");
                    });
                })
                ->addColumn('payment_methods', function ($row) use ($payment_types) {
                    $methods = array_unique($row->payment_lines->pluck('method')->toArray());
                    $count = count($methods);
                    $payment_method = '';
                    if ($count == 1) {
                        $payment_method = $payment_types[$methods[0]];
                    } elseif ($count > 1) {
                        $payment_method = __('lang_v1.checkout_multi_pay');
                    }

                    $html = !empty($payment_method) ? '<span class="payment-method" data-orig-value="' . $payment_method . '" data-status-name="' . $payment_method . '">' . $payment_method . '</span>' : '';

                    return $html;
                })
                ->editColumn('status', function ($row) use ($sales_order_statuses, $is_admin) {
                    $status = '';

                    if ($row->type == 'sales_order') {
                        if ($is_admin && $row->status != 'completed') {
                            $status = '<span class="edit-so-status label ' . $sales_order_statuses[$row->status]['class'] . '" data-href="' . action("SalesOrderController@getEditSalesOrderStatus", ['id' => $row->id]) . '">' . $sales_order_statuses[$row->status]['label'] . '</span>';
                        } else {
                            $status = '<span class="label ' . $sales_order_statuses[$row->status]['class'] . '" >' . $sales_order_statuses[$row->status]['label'] . '</span>';
                        }
                    }

                    return $status;
                })
                ->editColumn('so_qty_remaining', '{{@format_quantity($so_qty_remaining)}}')
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("sell.view") || auth()->user()->can("view_own_sell_only")) {
                            return action('SellController@show', [$row->id]);
                        } else {
                            return '';
                        }
                    }]);

            $rawColumns = ['final_total', 'action', 'total_paid', 'total_remaining', 'payment_status', 'invoice_no', 'discount_amount', 'tax_amount', 'total_before_tax', 'shipping_status', 'types_of_service_name', 'payment_methods', 'return_due', 'conatct_name', 'status'];

            return $datatable->rawColumns($rawColumns)
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);
        $sales_representative = User::forDropdown($business_id, false, false, true);

        //Commission agent filter
        $is_cmsn_agent_enabled = request()->session()->get('business.sales_cmsn_agnt');
        $commission_agents = [];
        if (!empty($is_cmsn_agent_enabled)) {
            $commission_agents = User::forDropdown($business_id, false, true, true);
        }

        //Service staff filter
        $service_staffs = null;
        if ($this->productUtil->isModuleEnabled('service_staff')) {
            $service_staffs = $this->productUtil->serviceStaffDropdown($business_id);
        }

        $shipping_statuses = $this->transactionUtil->shipping_statuses();$order_statuses = Transaction::sell_statuses();

        $sources = $this->transactionUtil->getSources($business_id);
        if ($is_woocommerce) {
            $sources['woocommerce'] = 'Woocommerce';
        }

        return view('sell.master')
            ->with(compact('business_locations', 'customers', 'is_woocommerce', 'sales_representative', 'is_cmsn_agent_enabled', 'commission_agents', 'service_staffs', 'is_tables_enabled', 'is_service_staff_enabled', 'is_types_service_enabled', 'shipping_statuses','order_statuses', 'sources'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $sale_type = request()->get('sale_type', '');

        if ($sale_type == 'sales_order') {
            if (!auth()->user()->can('so.create')) {
                abort(403, 'Unauthorized action.');
            }
        } else {
            if (!auth()->user()->can('direct_sell.access')) {
                abort(403, 'Unauthorized action.');
            }
        }


        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not, then check for users quota
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        } elseif (!$this->moduleUtil->isQuotaAvailable('invoices', $business_id)) {
            return $this->moduleUtil->quotaExpiredResponse('invoices', $business_id, action('SellController@index'));
        }

        $walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);

        $business_details = $this->businessUtil->getDetails($business_id);
        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);

        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];

        $default_location = null;
        foreach ($business_locations as $id => $name) {
            $default_location = BusinessLocation::findOrFail($id);
            break;
        }

        $commsn_agnt_setting = $business_details->sales_cmsn_agnt;
        $commission_agent = [];
        if ($commsn_agnt_setting == 'user') {
            $commission_agent = User::forDropdown($business_id);
        } elseif ($commsn_agnt_setting == 'cmsn_agnt') {
            $commission_agent = User::saleCommissionAgentsDropdown($business_id);
        }

        $types = [];
        if (auth()->user()->can('supplier.create')) {
            $types['supplier'] = __('report.supplier');
        }
        if (auth()->user()->can('customer.create')) {
            $types['customer'] = __('report.customer');
        }

        $customer_groups = CustomerGroup::forDropdown($business_id);

        $payment_line = $this->dummyPaymentLine;
        $payment_types = $this->transactionUtil->payment_types(null, true, $business_id);

        //Selling Price Group Dropdown
        $price_groups = SellingPriceGroup::forDropdown($business_id);

        $default_datetime = $this->businessUtil->format_date('now', true);
        $default_time = $this->businessUtil->format_times(Carbon::parse(now())->format('H:i'));

        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

        $invoice_schemes = InvoiceScheme::forDropdown($business_id);
        $default_invoice_schemes = InvoiceScheme::getDefault($business_id);
        if (!empty($default_location)) {
            $default_invoice_schemes = InvoiceScheme::where('business_id', $business_id)
                ->findorfail($default_location->invoice_scheme_id);
        }
        $shipping_statuses = $this->transactionUtil->shipping_statuses();$order_statuses = Transaction::sell_statuses();

        //Types of service
        $types_of_service = [];
        if ($this->moduleUtil->isModuleEnabled('types_of_service')) {
            $types_of_service = TypesOfService::forDropdown($business_id);
        }

        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }

        $status = request()->get('status', '');

        $statuses = Transaction::sell_statuses();

        if ($sale_type == 'sales_order') {
            $status = 'ordered';
        }

        $is_order_request_enabled = false;
        $is_crm = $this->moduleUtil->isModuleInstalled('Crm');
        if ($is_crm) {
            $crm_settings = Business::where('id', auth()->user()->business_id)
                ->value('crm_settings');
            $crm_settings = !empty($crm_settings) ? json_decode($crm_settings, true) : [];

            if (!empty($crm_settings['enable_order_request'])) {
                $is_order_request_enabled = true;
            }
        }

        return view('sell.create')
            ->with(compact(
                'business_details',
                'taxes',
                'walk_in_customer',
                'business_locations',
                'bl_attributes',
                'default_location',
                'commission_agent',
                'types',
                'customer_groups',
                'payment_line',
                'payment_types',
                'price_groups',
                'default_datetime',
                'default_time',
                'pos_settings',
                'invoice_schemes',
                'default_invoice_schemes',
                'types_of_service',
                'accounts',
                'shipping_statuses',
                'order_statuses',
                'status',
                'sale_type',
                'statuses',
                'is_order_request_enabled'
            ));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }


    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // if (!auth()->user()->can('sell.view') && !auth()->user()->can('direct_sell.access') && !auth()->user()->can('view_own_sell_only')) {
        //     abort(403, 'Unauthorized action.');
        // }
        $business_id = request()->session()->get('user.business_id');
        $taxes = TaxRate::where('business_id', $business_id)
            ->pluck('name', 'id');
        $query = Transaction::where('business_id', $business_id)
            ->where('id', $id)
            ->with(['contact', 'transaction_activity', 'sell_lines' => function ($q) {
                $q->whereNull('parent_sell_line_id');
            }, 'sell_lines.product', 'sell_lines.product.unit', 'sell_lines.variations', 'sell_lines.variations.product_variation', 'payment_lines', 'sell_lines.modifiers', 'sell_lines.lot_details', 'tax', 'sell_lines.sub_unit', 'table', 'service_staff', 'sell_lines.service_staff', 'types_of_service', 'media', 'sell_lines.transactionSellLinesVariants']);

        if (!auth()->user()->can('sell.view') && !auth()->user()->can('direct_sell.access') && auth()->user()->can('view_own_sell_only')) {
            $query->where('transactions.created_by', request()->session()->get('user.id'));
        }

        $sell = $query->firstOrFail();

        $activities = Activity::forSubject($sell)
            ->with(['causer', 'subject'])
            ->latest()
            ->get();
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
            ->leftjoin('units', 'units.id', '=', 'p.unit_id')
            ->where('transaction_sell_lines.transaction_id', $id)
            ->with(['so_line','lot_details'])
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
                'transaction_sell_lines.res_line_order_status',
                'units.id as unit_id',
                'transaction_sell_lines.sub_unit_id',
                'transaction_sell_lines_variants.value',
                'transaction_sell_lines_variants.pax',
                'transaction_sell_lines_variants.name as transaction_sell_lines_variants_name',
            /*DB::raw('vld.qty_available + transaction_sell_lines.quantity AS qty_available')*/
            )
            ->get();
        $line_taxes = [];
        $product_id = [];
        $product_name = [];
        $edit_product = [];
        foreach ($sell->sell_lines as $key => $value) {
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

            if (!empty($value->sub_unit_id)) {
                $formated_sell_line = $this->transactionUtil->recalculateSellLineTotals($business_id, $value);
                $sell->sell_lines[$key] = $formated_sell_line;
            }

            if (!empty($taxes[$value->tax_id])) {
                if (isset($line_taxes[$taxes[$value->tax_id]])) {
                    $line_taxes[$taxes[$value->tax_id]] += ($value->item_tax * $value->quantity);
                } else {
                    $line_taxes[$taxes[$value->tax_id]] = ($value->item_tax * $value->quantity);
                }
            }
        }
        $product_ids = array_unique($product_id);
        $product_count = count($product_ids);
        $product_names = array_unique($product_name);
        $payment_types = $this->transactionUtil->payment_types($sell->location_id, true);
        $order_taxes = [];
        if (!empty($sell->tax)) {
            if ($sell->tax->is_tax_group) {
                $order_taxes = $this->transactionUtil->sumGroupTaxDetails($this->transactionUtil->groupTaxDetails($sell->tax, $sell->tax_amount));
            } else {
                $order_taxes[$sell->tax->name] = $sell->tax_amount;
            }
        }

        $business_details = $this->businessUtil->getDetails($business_id);
        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);
        $shipping_statuses = $this->transactionUtil->shipping_statuses();$order_statuses = Transaction::sell_statuses();
        $shipping_status_colors = $this->shipping_status_colors;
        $common_settings = session()->get('business.common_settings');
        $is_warranty_enabled = !empty($common_settings['enable_product_warranty']) ? true : false;

        $statuses = Transaction::sell_statuses();

        if ($sell->type == 'sales_order') {
            $sales_order_statuses = Transaction::sales_order_statuses(true);
            $statuses = array_merge($statuses, $sales_order_statuses);
        }
        $status_color_in_activity = Transaction::sales_order_statuses();
        $sales_orders = $sell->salesOrders();

        return view('sale_pos.show')
            ->with(compact(
                'edit_product',
                'taxes',
                'sell',
                'payment_types',
                'order_taxes',
                'pos_settings',
                'shipping_statuses',
                'order_statuses',
                'shipping_status_colors',
                'is_warranty_enabled',
                'activities',
                'statuses',
                'status_color_in_activity',
                'sales_orders',
                'line_taxes',
                'product_ids',
                'product_names',
                'sell_details'
            ));
    }

    /**
     * List of transaction activity.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function transactionActivity($id)
    {
        if (!auth()->user()->can('direct_sell.update') && !auth()->user()->can('so.update')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');

        $business_details = $this->businessUtil->getDetails($business_id);

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $sells = TransactionActivity::where('transaction_id', $id);
            return Datatables::of($sells)
                ->addColumn(
                    'action', function ($row) {
                    if ($row->user_comment != '') {
                        $html = '<div class="btn-group">
                                <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                                    data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                    </span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                    ';

                        if (auth()->user()->can('draft.delete') || auth()->user()->can('quotation.delete')) {
                            $html .= '<li>
                                <a href="' . action('TransactionActivityController@destroy', [$row->id]) . '" class="delete-activity"><i class="fas fa-trash"></i>' . __("messages.delete") . '</a>
                                </li>';
                        }

                        $html .= '</ul></div>';

                        return $html;
                    }
                    return 'NA';

                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

        if (!auth()->user()->can('direct_sell.update') && !auth()->user()->can('so.update')) {
            abort(403, 'Unauthorized action.');
        }
        //Check if the transaction can be edited or not.
        $edit_days = request()->session()->get('business.transaction_edit_days');
        if (!$this->transactionUtil->canBeEdited($id, $edit_days)) {
            return back()
                ->with('status', ['success' => 0,
                    'msg' => __('messages.transaction_edit_not_allowed', ['days' => $edit_days])]);
        }

        //Check if return exist then not allowed
        if ($this->transactionUtil->isReturnExist($id)) {
            return back()->with('status', ['success' => 0,
                'msg' => __('lang_v1.return_exist')]);
        }

        $business_id = request()->session()->get('user.business_id');

        $business_details = $this->businessUtil->getDetails($business_id);
        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);

        $transaction = Transaction::where('business_id', $business_id)
            ->with(['price_group', 'types_of_service', 'media', 'media.uploaded_by_user', 'transaction_activity'])
            ->whereIn('type', ['sell', 'sales_order'])
            ->findorfail($id);
        $total_compensate = MasterList::where(['transaction_id' => $transaction->id])->whereNotNull('cancel_reason')->count();
        $master_list = MasterList::where('transaction_id', $transaction->id)->get();

        if ($transaction->type == 'sales_order' && !auth()->user()->can('so.update')) {
            abort(403, 'Unauthorized action.');
        }

        $location_id = $transaction->location_id;
        $location_printer_type = BusinessLocation::find($location_id)->receipt_printer_type;
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
            ->where('transaction_sell_lines.transaction_id', $id)
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
            /*DB::raw('vld.qty_available + transaction_sell_lines.quantity AS qty_available')*/
            )
            ->get();

        $transaction_sell_lines_id = [];
        $transaction_sell_lines_days = '';
        $time_slot = '';
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
                ];
                $product_name[] = $value->product_actual_name;
                //If modifier or combo sell line then unset
                if (!empty($sell_details[$key]->parent_sell_line_id)) {
                    unset($sell_details[$key]);
                } else {
                    if ($transaction->status != AppConstant::FINAL || $transaction->status != AppConstant::COMPLETED || $transaction->status != AppConstant::PROCESSING ) {
                        $actual_qty_avlbl = $value->qty_available - $value->quantity_ordered;
                        $sell_details[$key]->qty_available = $actual_qty_avlbl;
                        $value->qty_available = $actual_qty_avlbl;
                    }
                    //$number_of_days = $value->number_of_days;
                    $time_slot = $value->time_slot;

                    $transaction_sell_lines_days = TransactionSellLinesDay::where('transaction_sell_lines_id', $value->transaction_sell_lines_id)->get();
                    foreach ($transaction_sell_lines_days as $days) {
                        $transaction_sell_lines_id[$value->product_id][] = $days->day;
                    }
                    $sell_details[$key]->formatted_qty_available = $this->productUtil->num_f($value->qty_available, false, null, true);
                    $lot_numbers = [];
                    if (request()->session()->get('business.enable_lot_number') == 1) {
                        $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($value->variation_id, $business_id, $location_id);
                        foreach ($lot_number_obj as $lot_number) {
                            //If lot number is selected added ordered quantity to lot quantity available
                            if ($value->lot_no_line_id == $lot_number->purchase_line_id) {
                                $lot_number->qty_available += $value->quantity_ordered;
                            }

                            $lot_number->qty_formated = $this->transactionUtil->num_f($lot_number->qty_available);
                            $lot_numbers[] = $lot_number;
                        }
                    }
                    $sell_details[$key]->lot_numbers = $lot_numbers;

                    if (!empty($value->sub_unit_id)) {
                        $value = $this->productUtil->changeSellLineUnit($business_id, $value);
                        $sell_details[$key] = $value;
                    }

                    if ($this->transactionUtil->isModuleEnabled('modifiers')) {
                        //Add modifier details to sel line details
                        $sell_line_modifiers = TransactionSellLine::where('parent_sell_line_id', $sell_details[$key]->transaction_sell_lines_id)
                            ->where('children_type', 'modifier')
                            ->get();
                        $modifiers_ids = [];
                        if (count($sell_line_modifiers) > 0) {
                            $sell_details[$key]->modifiers = $sell_line_modifiers;
                            foreach ($sell_line_modifiers as $sell_line_modifier) {
                                $modifiers_ids[] = $sell_line_modifier->variation_id;
                            }
                        }
                        $sell_details[$key]->modifiers_ids = $modifiers_ids;

                        //add product modifier sets for edit
                        $this_product = Product::find($sell_details[$key]->product_id);
                        if (count($this_product->modifier_sets) > 0) {
                            $sell_details[$key]->product_ms = $this_product->modifier_sets;
                        }
                    }

                    //Get details of combo items
                    if ($sell_details[$key]->product_type == 'combo') {
                        $sell_line_combos = TransactionSellLine::where('parent_sell_line_id', $sell_details[$key]->transaction_sell_lines_id)
                            ->where('children_type', 'combo')
                            ->get()
                            ->toArray();
                        if (!empty($sell_line_combos)) {
                            $sell_details[$key]->combo_products = $sell_line_combos;
                        }

                        //calculate quantity available if combo product
                        $combo_variations = [];
                        foreach ($sell_line_combos as $combo_line) {
                            $combo_variations[] = [
                                'variation_id' => $combo_line['variation_id'],
                                'quantity' => $combo_line['quantity'] / $sell_details[$key]->quantity_ordered,
                                'unit_id' => null
                            ];
                        }
                        $sell_details[$key]->qty_available =
                            $this->productUtil->calculateComboQuantity($location_id, $combo_variations);

                        if ($transaction->status == AppConstant::FINAL || $transaction->status == AppConstant::COMPLETED || $transaction->status == AppConstant::PROCESSING) {
                            $sell_details[$key]->qty_available = $sell_details[$key]->qty_available + $sell_details[$key]->quantity_ordered;
                        }

                        $sell_details[$key]->formatted_qty_available = $this->productUtil->num_f($sell_details[$key]->qty_available, false, null, true);
                    }
                }
            }
        }
        $product_ids = array_unique($product_id);
        $product_count = count($product_ids);
        $product_names = array_unique($product_name);
        $commsn_agnt_setting = $business_details->sales_cmsn_agnt;
        $commission_agent = [];
        if ($commsn_agnt_setting == 'user') {
            $commission_agent = User::forDropdown($business_id);
        } elseif ($commsn_agnt_setting == 'cmsn_agnt') {
            $commission_agent = User::saleCommissionAgentsDropdown($business_id);
        }

        $types = [];
        if (auth()->user()->can('supplier.create')) {
            $types['supplier'] = __('report.supplier');
        }
        if (auth()->user()->can('customer.create')) {
            $types['customer'] = __('report.customer');
        }

        $customer_groups = CustomerGroup::forDropdown($business_id);

        $transaction->transaction_date = $this->transactionUtil->format_date($transaction->transaction_date, true);

        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

        $waiters = [];
        if ($this->productUtil->isModuleEnabled('service_staff') && !empty($pos_settings['inline_service_staff'])) {
            $waiters = $this->productUtil->serviceStaffDropdown($business_id);
        }

        $invoice_schemes = [];
        $default_invoice_schemes = null;

        if ($transaction->status == AppConstant::PAYMENT_PENDING) {
            $invoice_schemes = InvoiceScheme::forDropdown($business_id);
            $default_invoice_schemes = InvoiceScheme::getDefault($business_id);
        }

        $redeem_details = [];
        if (request()->session()->get('business.enable_rp') == 1) {
            $redeem_details = $this->transactionUtil->getRewardRedeemDetails($business_id, $transaction->contact_id);

            $redeem_details['points'] += $transaction->rp_redeemed;
            $redeem_details['points'] -= $transaction->rp_earned;
        }

        $edit_discount = auth()->user()->can('edit_product_discount_from_sale_screen');
        $edit_price = auth()->user()->can('edit_product_price_from_sale_screen');

        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }

        $shipping_statuses = $this->transactionUtil->shipping_statuses();$order_statuses = Transaction::sell_statuses();

        $common_settings = session()->get('business.common_settings');
        $is_warranty_enabled = !empty($common_settings['enable_product_warranty']) ? true : false;

        $statuses = Transaction::sell_statuses();

        $is_order_request_enabled = false;
        $is_crm = $this->moduleUtil->isModuleInstalled('Crm');
        if ($is_crm) {
            $crm_settings = Business::where('id', auth()->user()->business_id)
                ->value('crm_settings');
            $crm_settings = !empty($crm_settings) ? json_decode($crm_settings, true) : [];

            if (!empty($crm_settings['enable_order_request'])) {
                $is_order_request_enabled = true;
            }
        }

        $sales_orders = [];
        if (!empty($pos_settings['enable_sales_order']) || $is_order_request_enabled) {
            $sales_orders = Transaction::where('business_id', $business_id)
                ->where('type', 'sales_order')
                ->where('contact_id', $transaction->contact_id)
                ->where(function ($q) use ($transaction) {
                    $q->where('status', '!=', 'completed');

                    if (!empty($transaction->sales_order_ids)) {
                        $q->orWhereIn('id', $transaction->sales_order_ids);
                    }
                })
                ->pluck('invoice_no', 'id');
        }

        $payment_types = $this->transactionUtil->payment_types($transaction->location_id, false, $business_id);

        $payment_lines = $this->transactionUtil->getPaymentDetails($id);

        //If no payment lines found then add dummy payment line.
        if (empty($payment_lines)) {
            $payment_lines[] = $this->dummyPaymentLine;
        }
        $change_return = $this->dummyPaymentLine;
        $customer_due = $this->transactionUtil->getContactDue($transaction->contact_id, $transaction->business_id);
        $customer_due = $customer_due != 0 ? $this->transactionUtil->num_f($customer_due, true) : '';
        $default_datetime = $this->businessUtil->format_date('now', true);
        $default_time = $this->businessUtil->format_times(Carbon::parse(now())->format('H:i'));
        $default_date = $this->businessUtil->format_dates(Carbon::parse(now())->format('Y-m-d'));
        $role = 'sell';
        $masterListCols = config('masterlist.' . $role . '_columns');
        $tra_sell_lines = TransactionSellLine::with('product')->where(['transaction_id' => $id])->groupBy('product_id')->get();
        $tra_sell_lines_array = TransactionSellLine::with('product')->where(['transaction_id' => $id])->groupBy('product_id')->pluck('id')->toArray();
        $sell_ids = implode(',', $tra_sell_lines_array);

        return view('sell.edit')
            ->with(compact('tra_sell_lines', 'sell_ids', 'masterListCols', 'master_list', 'edit_product', 'business_details', 'default_datetime', 'default_time', 'default_date',/*'number_of_days','transaction_sell_lines_id',*/ 'time_slot', 'taxes', 'sell_details', 'transaction', 'commission_agent', 'types', 'customer_groups', 'pos_settings', 'waiters', 'invoice_schemes', 'default_invoice_schemes', 'redeem_details', 'edit_discount', 'edit_price', 'shipping_statuses','order_statuses', 'statuses', 'sales_orders', 'payment_types', 'accounts', 'payment_lines', 'change_return', 'is_order_request_enabled', 'customer_due', 'transaction_sell_lines_days', 'transaction_sell_lines_id', 'product_ids', 'product_names', 'product_count', 'total_compensate'));
    }


    /**
     * Display a listing sell drafts.
     *
     * @return \Illuminate\Http\Response
     */
    public function getDrafts()
    {
        if (!auth()->user()->can('draft.view_all') && !auth()->user()->can('draft.view_own')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);

        $sales_representative = User::forDropdown($business_id, false, false, true);


        //return view('sale_pos.draft')
        return view('sell.index')
            ->with(compact('business_locations', 'customers', 'sales_representative'));
    }

    /**
     * Display a listing sell quotations.
     *
     * @return \Illuminate\Http\Response
     */
    public function getQuotations()
    {
        if (!auth()->user()->can('quotation.view_all') && !auth()->user()->can('quotation.view_own')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);

        $sales_representative = User::forDropdown($business_id, false, false, true);

        return view('sale_pos.quotations')
            ->with(compact('business_locations', 'customers', 'sales_representative'));
    }

    /**
     * Send the datatable response for draft or quotations.
     *
     * @return \Illuminate\Http\Response
     */
    public function getDraftDatables()
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $is_quotation = request()->input('is_quotation', 0);

            $is_woocommerce = $this->moduleUtil->isModuleInstalled('Woocommerce');

            $sells = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->leftJoin('users as u', 'transactions.created_by', '=', 'u.id')
                ->join(
                    'business_locations AS bl',
                    'transactions.location_id',
                    '=',
                    'bl.id'
                )
                ->leftJoin('transaction_sell_lines as tsl', function ($join) {
                    $join->on('transactions.id', '=', 'tsl.transaction_id')
                        ->whereNull('tsl.parent_sell_line_id');
                })
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', AppConstant::PAYMENT_PENDING)
                ->select(
                    'transactions.id',
                    'transaction_date',
                    'invoice_no',
                    'contacts.name',
                    'contacts.mobile',
                    'contacts.supplier_business_name',
                    'bl.name as business_location',
                    'is_direct_sale',
                    'sub_status',
                    DB::raw('COUNT( DISTINCT tsl.id) as total_items'),
                    DB::raw('SUM(tsl.quantity) as total_quantity'),
                    DB::raw("CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as added_by"),
                    'transactions.is_export'
                );

            if ($is_quotation == 1) {
                $sells->where('transactions.sub_status', 'quotation');

                if (!auth()->user()->can('quotation.view_all') && auth()->user()->can('quotation.view_own')) {
                    $sells->where('transactions.created_by', request()->session()->get('user.id'));
                }

            } else {
                if (!auth()->user()->can('draft.view_all') && auth()->user()->can('draft.view_own')) {
                    $sells->where('transactions.created_by', request()->session()->get('user.id'));
                }
            }


            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $sells->whereIn('transactions.location_id', $permitted_locations);
            }

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end = request()->end_date;
                $sells->whereDate('transaction_date', '>=', $start)
                    ->whereDate('transaction_date', '<=', $end);
            }

            if (request()->has('location_id')) {
                $location_id = request()->get('location_id');
                if (!empty($location_id)) {
                    $sells->where('transactions.location_id', $location_id);
                }
            }

            if (request()->has('created_by')) {
                $created_by = request()->get('created_by');
                if (!empty($created_by)) {
                    $sells->where('transactions.created_by', $created_by);
                }
            }

            if (!empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $sells->where('contacts.id', $customer_id);
            }

            if ($is_woocommerce) {
                $sells->addSelect('transactions.woocommerce_order_id');
            }

            $sells->groupBy('transactions.id');

            return Datatables::of($sells)
                ->addColumn(
                    'action', function ($row) {
                    $html = '<div class="btn-group">
                                <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                                    data-toggle="dropdown" aria-expanded="false">' .
                        __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                    </span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                    <li>
                                    <a href="#" data-href="' . action('SellController@show', [$row->id]) . '" class="btn-modal" data-container=".view_modal">
                                        <i class="fas fa-eye" aria-hidden="true"></i>' . __("messages.view") . '
                                    </a>
                                    </li>';

                    if (auth()->user()->can('draft.update') || auth()->user()->can('quotation.update')) {
                        if ($row->is_direct_sale == 1) {
                            $html .= '<li>
                                            <a target="_blank" href="' . action('SellController@edit', [$row->id]) . '">
                                                <i class="fas fa-edit"></i>' . __("messages.edit") . '
                                            </a>
                                        </li>';
                        } else {
                            $html .= '<li>
                                            <a target="_blank" href="' . action('SellPosController@edit', [$row->id]) . '">
                                                <i class="fas fa-edit"></i>' . __("messages.edit") . '
                                            </a>
                                        </li>';
                        }
                    }

                    $html .= '<li>
                                    <a href="#" class="print-invoice" data-href="' . route('sell.printInvoice', [$row->id]) . '"><i class="fas fa-print" aria-hidden="true"></i>' . __("messages.print") . '</a>
                                </li>';


                    if (config("constants.enable_download_pdf")) {
                        $sub_status = $row->sub_status == 'proforma' ? 'proforma' : '';
                        $html .= '<li>
                                        <a href="' . route('quotation.downloadPdf', ['id' => $row->id, 'sub_status' => $sub_status]) . '" target="_blank">
                                            <i class="fas fa-print" aria-hidden="true"></i>' . __("lang_v1.download_pdf") . '
                                        </a>
                                    </li>';
                    }

                    if ((auth()->user()->can("sell.create") || auth()->user()->can("direct_sell.access")) && config("constants.enable_convert_draft_to_invoice")) {
                        $html .= '<li>
                                        <a href="' . action('SellPosController@convertToInvoice', [$row->id]) . '" class="convert-draft"><i class="fas fa-sync-alt"></i>' . __("lang_v1.convert_to_invoice") . '</a>
                                    </li>';
                    }

                    if ($row->sub_status != "proforma") {
                        $html .= '<li>
                                        <a href="' . action('SellPosController@convertToProforma', [$row->id]) . '" class="convert-to-proforma"><i class="fas fa-sync-alt"></i>' . __("lang_v1.convert_to_proforma") . '</a>
                                    </li>';
                    }

                    if (auth()->user()->can('draft.delete') || auth()->user()->can('quotation.delete')) {
                        $html .= '<li>
                                <a href="' . action('SellPosController@destroy', [$row->id]) . '" class="delete-sale"><i class="fas fa-trash"></i>' . __("messages.delete") . '</a>
                                </li>';
                    }

                    if ($row->sub_status == "quotation") {
                        $html .= '<li>
                                        <a href="' . action("SellPosController@showInvoiceUrl", [$row->id]) . '" class="view_invoice_url"><i class="fas fa-eye"></i>' . __("lang_v1.view_quote_url") . '</a>
                                    </li>
                                    <li>
                                        <a href="#" data-href="' . action("NotificationController@getTemplate", ["transaction_id" => $row->id, "template_for" => "new_quotation"]) . '" class="btn-modal" data-container=".view_modal"><i class="fa fa-envelope" aria-hidden="true"></i>' . __("lang_v1.new_quotation_notification") . '
                                        </a>
                                    </li>';
                    }

                    $html .= '</ul></div>';

                    return $html;

                })
                ->removeColumn('id')
                ->editColumn('invoice_no', function ($row) {
                    $invoice_no = $row->invoice_no;
                    if (!empty($row->woocommerce_order_id)) {
                        $invoice_no .= ' <i class="fab fa-wordpress text-primary no-print" title="' . __('lang_v1.synced_from_woocommerce') . '"></i>';
                    }

                    if ($row->sub_status == 'proforma') {
                        $invoice_no .= '<br><span class="label bg-gray">' . __('lang_v1.proforma_invoice') . '</span>';
                    }

                    if (!empty($row->is_export)) {
                        $invoice_no .= '</br><small class="label label-default no-print" title="' . __('lang_v1.export') . '">' . __('lang_v1.export') . '</small>';
                    }

                    return $invoice_no;
                })
                ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                ->editColumn('total_items', '{{@format_quantity($total_items)}}')
                ->editColumn('total_quantity', '{{@format_quantity($total_quantity)}}')
                ->addColumn('conatct_name', '@if(!empty($supplier_business_name)) {{$supplier_business_name}}, <br>@endif {{$name}}')
                ->filterColumn('conatct_name', function ($query, $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('contacts.name', 'like', "%{$keyword}%")
                            ->orWhere('contacts.supplier_business_name', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('added_by', function ($query, $keyword) {
                    $query->whereRaw("CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) like ?", ["%{$keyword}%"]);
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("sell.view")) {
                            return action('SellController@show', [$row->id]);
                        } else {
                            return '';
                        }
                    }])
                ->rawColumns(['action', 'invoice_no', 'transaction_date', 'conatct_name'])
                ->make(true);
        }
    }

    /**
     * Creates copy of the requested sale.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function duplicateSell($id)
    {
        if (!auth()->user()->can('sell.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $user_id = request()->session()->get('user.id');

            $transaction = Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->findorfail($id);
            $duplicate_transaction_data = [];
            foreach ($transaction->toArray() as $key => $value) {
                if (!in_array($key, ['id', 'created_at', 'updated_at'])) {
                    $duplicate_transaction_data[$key] = $value;
                }
            }
            $duplicate_transaction_data['status'] = AppConstant::PAYMENT_PENDING;
            $duplicate_transaction_data['payment_status'] = null;
            $duplicate_transaction_data['transaction_date'] = Carbon::now();
            $duplicate_transaction_data['created_by'] = $user_id;
            $duplicate_transaction_data['invoice_token'] = null;

            DB::beginTransaction();
            $duplicate_transaction_data['invoice_no'] = $this->transactionUtil->getInvoiceNumber($business_id, AppConstant::PAYMENT_PENDING, $duplicate_transaction_data['location_id']);

            //Create duplicate transaction
            $duplicate_transaction = Transaction::create($duplicate_transaction_data);

            //Create duplicate transaction sell lines
            $duplicate_sell_lines_data = [];

            foreach ($transaction->sell_lines as $sell_line) {
                $new_sell_line = [];
                foreach ($sell_line->toArray() as $key => $value) {
                    if (!in_array($key, ['id', 'transaction_id', 'created_at', 'updated_at', 'lot_no_line_id'])) {
                        $new_sell_line[$key] = $value;
                    }
                }

                $duplicate_sell_lines_data[] = $new_sell_line;
            }

            $duplicate_transaction->sell_lines()->createMany($duplicate_sell_lines_data);

            DB::commit();

            $output = ['success' => 0,
                'msg' => trans("lang_v1.duplicate_sell_created_successfully")
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = ['success' => 0,
                'msg' => trans("messages.something_went_wrong")
            ];
        }

        if (!empty($duplicate_transaction)) {
            if ($duplicate_transaction->is_direct_sale == 1) {
                return redirect()->action('SellController@edit', [$duplicate_transaction->id])->with(['status', $output]);
            } else {
                return redirect()->action('SellPosController@edit', [$duplicate_transaction->id])->with(['status', $output]);
            }
        } else {
            abort(404, 'Not Found.');
        }
    }

    /**
     * Shows modal to edit shipping details.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function editShipping($id)
    {
        $is_admin = $this->businessUtil->is_admin(auth()->user());

        if (!$is_admin && !auth()->user()->hasAnyPermission(['access_shipping', 'access_own_shipping', 'access_commission_agent_shipping'])) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $transaction = Transaction::where('business_id', $business_id)
            ->with(['media', 'media.uploaded_by_user'])
            ->findorfail($id);
        $shipping_statuses = $this->transactionUtil->shipping_statuses();$order_statuses = Transaction::sell_statuses();

        $activities = Activity::forSubject($transaction)
            ->with(['causer', 'subject'])
            ->where('activity_log.description', 'shipping_edited')
            ->latest()
            ->get();

        return view('sell.partials.edit_shipping')
            ->with(compact('transaction', 'shipping_statuses','order_statuses', 'activities'));
    }

    /**
     * Update shipping.
     *
     * @param Request $request , int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateShipping(Request $request, $id)
    {
        $is_admin = $this->businessUtil->is_admin(auth()->user());

        if (!$is_admin && !auth()->user()->hasAnyPermission(['access_shipping', 'access_own_shipping', 'access_commission_agent_shipping'])) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only([
                'shipping_details', 'shipping_address',
                'shipping_status', 'delivered_to', 'shipping_custom_field_1', 'shipping_custom_field_2', 'shipping_custom_field_3', 'shipping_custom_field_4', 'shipping_custom_field_5'
            ]);
            $business_id = $request->session()->get('user.business_id');


            $transaction = Transaction::where('business_id', $business_id)
                ->findOrFail($id);

            $transaction_before = $transaction->replicate();

            $transaction->update($input);

            $activity_property = ['update_note' => $request->input('shipping_note', '')];
            $this->transactionUtil->activityLog($transaction, 'shipping_edited', $transaction_before, $activity_property);

            $output = ['success' => 1,
                'msg' => trans("lang_v1.updated_success")
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = ['success' => 0,
                'msg' => trans("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    /**
     * Display list of shipments.
     *
     * @return \Illuminate\Http\Response
     */
    public function shipments()
    {
        $is_admin = $this->businessUtil->is_admin(auth()->user());

        if (!$is_admin && !auth()->user()->hasAnyPermission(['access_shipping', 'access_own_shipping', 'access_commission_agent_shipping'])) {
            abort(403, 'Unauthorized action.');
        }

        $shipping_statuses = $this->transactionUtil->shipping_statuses();$order_statuses = Transaction::sell_statuses();

        $business_id = request()->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);

        $sales_representative = User::forDropdown($business_id, false, false, true);

        $is_service_staff_enabled = $this->transactionUtil->isModuleEnabled('service_staff');

        //Service staff filter
        $service_staffs = null;
        if ($this->productUtil->isModuleEnabled('service_staff')) {
            $service_staffs = $this->productUtil->serviceStaffDropdown($business_id);
        }

        return view('sell.shipments')->with(compact('shipping_statuses','order_statuses'))
            ->with(compact('business_locations', 'customers', 'sales_representative', 'is_service_staff_enabled', 'service_staffs'));
    }

    public function viewMedia($model_id)
    {
        if (request()->ajax()) {
            $model_type = request()->input('model_type');
            $business_id = request()->session()->get('user.business_id');

            $query = Media::where('business_id', $business_id)
                ->where('model_id', $model_id)
                ->where('model_type', $model_type);

            $title = __('lang_v1.attachments');
            if (!empty(request()->input('model_media_type'))) {
                $query->where('model_media_type', request()->input('model_media_type'));
                $title = __('lang_v1.shipping_documents');
            }

            $medias = $query->get();

            return view('sell.view_media')->with(compact('medias', 'title'));
        }
    }

    public function resetMapping()
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        Artisan::call('pos:mapPurchaseSell');

        echo "Mapping reset success";
        exit;
    }

    /**
     * Retrieves products list.
     *
     * @param string $q
     * @param boolean $check_qty
     *
     * @return JSON
     */
    public function getProducts()
    {

        if (request()->ajax()) {
            $location_id = request()->input('location_id', null);
            $search_term = request()->input('term', '');
            $search_fields = request()->get('search_fields', ['name', 'sku']);
            if (in_array('sku', $search_fields)) {
                $search_fields[] = 'sub_sku';
            }
            $permitted_locations = auth()->user()->permitted_locations();
            $query = Product::active();
            //Include search
            if (!empty($search_term)) {
                $query->where(function ($query) use ($search_term, $search_fields) {
                    if (in_array('name', $search_fields)) {
                        $query->where('products.name', 'like', '%' . $search_term . '%');
                    }
                });
            }

            if (!empty($location_id) && $location_id != 'none') {
                if ($permitted_locations == 'all' || in_array($location_id, $permitted_locations)) {
                    $query->whereHas('product_locations', function ($query) use ($location_id) {
                        $query->where('product_locations.location_id', '=', $location_id);
                    });
                }
            } elseif ($location_id == 'none') {
                $query->doesntHave('product_locations');
            } else {
                if ($permitted_locations != 'all') {
                    $query->whereHas('product_locations', function ($query) use ($permitted_locations) {
                        $query->whereIn('product_locations.location_id', $permitted_locations);
                    });
                } else {
                    $query->with('product_locations');
                }
            }
            $query->select(
                'products.id as product_id',
                'products.name',
                'products.type',
                'products.business_id',
            );
            $result = $query->get();
            return json_encode($result);
        }
    }

    /**
     * Returns the HTML row for a product in POS
     *
     * @param int $variation_id
     * @param int $location_id
     * @return \Illuminate\Http\Response
     */
    public function getSellProductRow($product_id, $location_id)
    {
        $output = [];
        try {
            $row_count = request()->get('product_row');
            $price_group = request()->get('price_group');
            $row_count = $row_count + 1;
            $quantity = request()->get('quantity', 1);

            $weighing_barcode = request()->get('weighing_scale_barcode', null);

            $is_direct_sell = false;
            if (request()->get('is_direct_sell') == 'true') {
                $is_direct_sell = true;
            }

            // if ($variation_id == 'null' && !empty($weighing_barcode)) {
            //     $product_details = $this->__parseWeighingBarcode($weighing_barcode);
            //     if ($product_details['success']) {
            //         $variation_id = $product_details['variation_id'];
            //         $quantity = $product_details['qty'];
            //     } else {
            //         $output['success'] = false;
            //         $output['msg'] = $product_details['msg'];
            //         return $output;
            //     }
            // }

            $output = $this->getSellLineRow($product_id, $location_id, $quantity, $row_count, $is_direct_sell, $so_line = null, $price_group);

            $output['product'] = Product::with('unit')->where('id', $product_id)->first();
            if ($this->transactionUtil->isModuleEnabled('modifiers') && !$is_direct_sell) {
                $variation = Variation::find($product_id);
                $business_id = request()->session()->get('user.business_id');
                $this_product = Product::where('business_id', $business_id)
                    ->with(['modifier_sets'])
                    ->find($variation->product_id);
                if (count($this_product->modifier_sets) > 0) {
                    $product_ms = $this_product->modifier_sets;
                    $output['html_modifier'] = view('restaurant.product_modifier_set.modifier_for_product')
                        ->with(compact('product_ms', 'row_count'))->render();
                }
            }

        } catch (\Exception $e) {
            dd('aaa' . $e->getMessage());
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output['success'] = false;
            $output['msg'] = __('lang_v1.item_out_of_stock');
        }

        return $output;
    }

    private function getSellLineRow($product_id, $location_id, $quantity, $row_count, $is_direct_sell, $so_line = null, $price_group)
    {
        $business_id = request()->session()->get('user.business_id');
        $business_details = $this->businessUtil->getDetails($business_id);
        //Check for weighing scale barcode
        $weighing_barcode = request()->get('weighing_scale_barcode');

        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

        $check_qty = !empty($pos_settings['allow_overselling']) ? false : true;

        $is_sales_order = request()->has('is_sales_order') && request()->input('is_sales_order') == 'true' ? true : false;

        if ($is_sales_order) {
            $check_qty = false;
        }

        $productDatas = $this->getDetailsFromVariation($product_id, $business_id, $location_id, $check_qty);


        /* $sub_units = '';
         $waiters = [];
         $purchase_line_id='';*/
        foreach ($productDatas as $key => $productData) {

            if (!isset($productData->quantity_ordered)) {
                $productData->quantity_ordered = $quantity;
            }

            // $productData->formatted_qty_available = $this->productUtil->num_f($productData->qty_available, false, null, true);

            $sub_units = $this->productUtil->getSubUnits($business_id, $productData->product->unit_id, false, $productData->product->id);


            //Get customer group and change the price accordingly
            $customer_id = request()->get('customer_id', null);

            $cg = $this->contactUtil->getCustomerGroup($business_id, $customer_id);
            $percent = (empty($cg) || empty($cg->amount) || $cg->price_calculation_type != 'percentage') ? 0 : $cg->amount;
            $productData->default_sell_price = $productData->default_sell_price + ($percent * $productData->default_sell_price / 100);
            $productData->sell_price_inc_tax = $productData->sell_price_inc_tax + ($percent * $productData->sell_price_inc_tax / 100);

            $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);

            $enabled_modules = $this->transactionUtil->allModulesEnabled();

            //Get lot number dropdown if enabled
            $lot_numbers = [];
            if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
                $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($productData->id, $business_id, $location_id, true);
                foreach ($lot_number_obj as $lot_number) {
                    $lot_number->qty_formated = $this->productUtil->num_f($lot_number->qty_available);
                    $lot_numbers[] = $lot_number;
                }
            }
            $productData->lot_numbers = $lot_numbers;

            $purchase_line_id = request()->get('purchase_line_id');
            $price_group = request()->input('price_group');
            if (!empty($price_group)) {
                $variation_group_prices = $this->productUtil->getVariationGroupPrice($productData->id, $price_group, $productData->tax_id);

                if (!empty($variation_group_prices['price_inc_tax'])) {
                    $productData->sell_price_inc_tax = $variation_group_prices['price_inc_tax'];
                    $productData->default_sell_price = $variation_group_prices['price_exc_tax'];
                }
            }


            $output['success'] = true;
            //$output['enable_sr_no'] = $productData->enable_sr_no;

            $waiters = [];
            if ($this->productUtil->isModuleEnabled('service_staff') && !empty($pos_settings['inline_service_staff'])) {
                $waiters_enabled = true;
                $waiters = $this->productUtil->serviceStaffDropdown($business_id, $location_id);
            }
        }
        if (request()->get('type') == 'sell-return') {

            $output['html_content'] = view('sell_return.partials.product_row')
                ->with(compact('productDatas', 'row_count', 'tax_dropdown', 'enabled_modules', 'sub_units'))
                ->render();
        } else {

            $is_cg = !empty($cg->id) ? true : false;

            $discount = $this->productUtil->getSellProductDiscount($productDatas, $business_id, $location_id, $is_cg, $price_group, ''/*$productData->id*/);

            if ($is_direct_sell) {
                $edit_discount = auth()->user()->can('edit_product_discount_from_sale_screen');
                $edit_price = auth()->user()->can('edit_product_price_from_sale_screen');
            } else {
                $edit_discount = auth()->user()->can('edit_product_discount_from_pos_screen');
                $edit_price = auth()->user()->can('edit_product_price_from_pos_screen');
            }
            $default_datetime = $this->businessUtil->format_date('now', true);
            $default_time = $this->businessUtil->format_times(Carbon::parse(now())->format('H:i'));

            $output['html_content'] = view('sell.product_row')
                ->with(compact('product_id', 'default_datetime', 'default_time', 'productDatas', 'row_count', 'tax_dropdown', /*'enabled_modules',*/ 'pos_settings', 'sub_units', 'discount', 'waiters', 'edit_discount', 'edit_price', 'purchase_line_id', 'quantity', 'is_direct_sell', 'so_line', 'is_sales_order'))
                ->render();
        }
        return $output;
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
    public function getDetailsFromVariation($product_id, $business_id, $location_id = null, $check_qty = true)
    {
        $query = Variation::where('product_id', '=', $product_id)->with('product', 'product.unit', 'product_variation', 'product_variation.variation_template', 'product_variation.variation_template.values');
        // Add condition for check of quantity. (if stock is not enabled or qty_available > 0)
        /*if ($check_qty) {
            $query->whereHas('variation_location_details', function( $q ) use ($product_id){
                $q->where('product_id', '=', $product_id);
            });
        }

        if (!empty($location_id) && $check_qty) {
            //Check for enable stock, if enabled check for location id.
            $query->whereHas('product.variationLocationDetails', function( $q ) use ( $location_id , $product_id){
                $q->where('product_id', '=', $product_id);
                $q->where('location_id', '!=', $location_id);
            });
        }*/
        $data = $query->groupBy('variations.product_variation_id')->get();
        return $data;
    }

}
