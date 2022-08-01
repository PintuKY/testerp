<?php

namespace App\Http\Controllers;
use Excel;
use App\Models\User;
use App\Models\Contact;
use App\Models\Product;
use App\Models\TaxRate;
use App\Models\Business;
use App\Models\Currency;
use App\Models\Supplier;
use App\Models\Variation;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Models\Transaction;
use App\Utils\BusinessUtil;
use App\Models\PurchaseLine;
use Illuminate\Http\Request;

use App\Models\CustomerGroup;
use App\Utils\TransactionUtil;
use App\Models\KitchenLocation;

use App\Models\SupplierProduct;
use App\Models\BusinessLocation;
use App\Models\AccountTransaction;
use Illuminate\Support\Facades\DB;
use App\Models\SupplierTransaction;
use Illuminate\Support\Facades\Log;
use App\Models\SupplierPurchaseLine;
use App\Utils\SupplierTransactionUtil;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;

class SupplierPurchaseController extends Controller
{
     /**
     * All Utils instance.
     *
     */
    protected $productUtil;
    protected $supplierTransactionUtil;
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, SupplierTransactionUtil $supplierTransactionUtil, BusinessUtil $businessUtil, ModuleUtil $moduleUtil)
    {
        $this->productUtil = $productUtil;
        $this->supplierTransactionUtil = $supplierTransactionUtil;
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;

        $this->dummyPaymentLine = ['method' => 'cash', 'amount' => 0, 'note' => '', 'card_transaction_number' => '', 'card_number' => '', 'card_type' => '', 'card_holder_name' => '', 'card_month' => '', 'card_year' => '', 'card_security' => '', 'cheque_number' => '', 'bank_account_number' => '',
        'is_return' => 0, 'transaction_no' => ''];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // if (!auth()->user()->can('purchase.view') && !auth()->user()->can('purchase.create') && !auth()->user()->can('view_own_purchase')) {
        //     abort(403, 'Unauthorized action.');
        // }
        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            $purchases = $this->supplierTransactionUtil->getListPurchases($business_id);
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $purchases->whereIn('supplier_transactions.location_id', $permitted_locations);
            }

            if (!empty(request()->supplier_id)) {
                $purchases->where('supplier.id', request()->supplier_id);
            }
            if (!empty(request()->location_id)) {
                $purchases->where('supplier_transactions.location_id', request()->location_id);
            }
            if (!empty(request()->input('payment_status')) && request()->input('payment_status') != 'overdue') {
                $purchases->where('supplier_transactions.payment_status', request()->input('payment_status'));
            } elseif (request()->input('payment_status') == 'overdue') {
                $purchases->whereIn('supplier_transactions.payment_status', ['due', 'partial'])
                    ->whereNotNull('supplier_transactions.pay_term_number')
                    ->whereNotNull('supplier_transactions.pay_term_type')
                    ->whereRaw("IF(supplier_transactions.pay_term_type='days', DATE_ADD(supplier_transactions.transaction_date, INTERVAL supplier_transactions.pay_term_number DAY) < CURDATE(), DATE_ADD(supplier_transactions.transaction_date, INTERVAL supplier_transactions.pay_term_number MONTH) < CURDATE())");
            }

            if (!empty(request()->status)) {
                $purchases->where('supplier_transactions.status', request()->status);
            }

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $purchases->whereDate('supplier_transactions.transaction_date', '>=', $start)
                            ->whereDate('supplier_transactions.transaction_date', '<=', $end);
            }

            if (!auth()->user()->can('purchase.view') && auth()->user()->can('view_own_purchase')) {
                $purchases->where('supplier_transactions.created_by', request()->session()->get('user.id'));
            }

            return Datatables::of($purchases)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                                data-toggle="dropdown" aria-expanded="false">' .
                                __("messages.actions") .
                                '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                </span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-left" role="menu">';
                    if (auth()->user()->can("purchase.view")) {
                        $html .= '<li><a href="#" data-href="' . route('supplier-purchases.show', [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fas fa-eye" aria-hidden="true"></i>' . __("messages.view") . '</a></li>';
                    }
                    // if (auth()->user()->can("purchase.view")) {
                    //     $html .= '<li><a href="#" class="print-invoice" data-href="' . action('PurchaseController@printInvoice', [$row->id]) . '"><i class="fas fa-print" aria-hidden="true"></i>'. __("messages.print") .'</a></li>';
                    // }
                    if (auth()->user()->can("purchase.update")) {
                        $html .= '<li><a href="' . action('SupplierPurchaseController@edit', [$row->id]) . '"><i class="fas fa-edit"></i>' . __("messages.edit") . '</a></li>';
                    }
                    if (auth()->user()->can("purchase.delete")) {
                        $html .= '<li><a href="' . action('SupplierPurchaseController@destroy', [$row->id]) . '" class="delete-purchase"><i class="fas fa-trash"></i>' . __("messages.delete") . '</a></li>';
                    }

                    $html .= '<li><a href="' . action('LabelsController@supplierPurchaseShow') . '?purchase_id=' . $row->id . '" data-toggle="tooltip" title="' . __('lang_v1.label_help') . '"><i class="fas fa-barcode"></i>' . __('barcode.labels') . '</a></li>';

                    if (auth()->user()->can("purchase.view") && !empty($row->document)) {
                        $document_name = !empty(explode("_", $row->document, 2)[1]) ? explode("_", $row->document, 2)[1] : $row->document ;
                        $html .= '<li><a href="' . url('uploads/documents/' . $row->document) .'" download="' . $document_name . '"><i class="fas fa-download" aria-hidden="true"></i>' . __("purchase.download_document") . '</a></li>';
                        if (isFileImage($document_name)) {
                            $html .= '<li><a href="#" data-href="' . url('uploads/documents/' . $row->document) .'" class="view_uploaded_document"><i class="fas fa-image" aria-hidden="true"></i>' . __("lang_v1.view_document") . '</a></li>';
                        }
                    }

                    if (auth()->user()->can("purchase.payments") ||
                        auth()->user()->can("edit_purchase_payment") ||
                        auth()->user()->can("delete_purchase_payment")) {

                        $html .= '<li class="divider"></li>';
                        if ($row->payment_status != 'paid') {
                            $html .= '<li><a href="' . action('SupplierTransactionPaymentController@addPayment', [$row->id]) . '" class="add_payment_modal"><i class="fas fa-money-bill-alt" aria-hidden="true"></i>' . __("purchase.add_payment") . '</a></li>';
                        }

                        $html .= '<li><a href="' . action('SupplierTransactionPaymentController@show', $row->id) .
                        '" class="view_payment_modal"><i class="fas fa-money-bill-alt" aria-hidden="true" ></i>' . __("purchase.view_payments") . '</a></li>';
                    }

                    if (auth()->user()->can("purchase.update")) {
                        $html .= '<li><a href="' . action('PurchaseReturnController@add', [$row->id]) .
                        '"><i class="fas fa-undo" aria-hidden="true" ></i>' . __("lang_v1.purchase_return") . '</a></li>';
                    }

                    if (auth()->user()->can("purchase.update") || auth()->user()->can("purchase.update_status")) {
                        $html .= '<li><a href="#" data-purchase-id="'.$row->id.'" data-status="' . $row->status . '" class="update_status"><i class="fas fa-edit" aria-hidden="true" ></i>' . __("lang_v1.update_status") . '</a></li>';
                    }

                    if ($row->status == 'ordered') {
                        $html .= '<li><a href="#" data-href="' . action('NotificationController@getTemplate', ["transaction_id" => $row->id,"template_for" => "new_order"]) . '" class="btn-modal" data-container=".view_modal"><i class="fas fa-envelope" aria-hidden="true"></i> ' . __("lang_v1.new_order_notification") . '</a></li>';
                    } elseif ($row->status == 'received') {
                        $html .= '<li><a href="#" data-href="' . action('NotificationController@getTemplate', ["transaction_id" => $row->id,"template_for" => "items_received"]) . '" class="btn-modal" data-container=".view_modal"><i class="fas fa-envelope" aria-hidden="true"></i> ' . __("lang_v1.item_received_notification") . '</a></li>';
                    } elseif ($row->status == 'pending') {
                        $html .= '<li><a href="#" data-href="' . action('NotificationController@getTemplate', ["transaction_id" => $row->id,"template_for" => "items_pending"]) . '" class="btn-modal" data-container=".view_modal"><i class="fas fa-envelope" aria-hidden="true"></i> ' . __("lang_v1.item_pending_notification") . '</a></li>';
                    }

                    $html .=  '</ul></div>';
                    return $html;
                })
                ->removeColumn('id')
                ->editColumn('ref_no', function ($row) {
                    return !empty($row->return_exists) ? $row->ref_no . ' <small class="label bg-red label-round no-print" title="' . __('lang_v1.some_qty_returned') .'"><i class="fas fa-undo"></i></small>' : $row->ref_no;
                })
                ->editColumn(
                    'final_total',
                    '<span class="final_total" data-orig-value="{{$final_total}}">@format_currency($final_total)</span>'
                )
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn('name', '@if(!empty($supplier_business_name)) {{$supplier_business_name}}, <br> @endif {{$name}}')
                ->editColumn(
                    'status',
                    '<a href="#" @if(auth()->user()->can("purchase.update") || auth()->user()->can("purchase.update_status")) class="update_status no-print" data-purchase-id="{{$id}}" data-status="{{$status}}" @endif><span class="label @transaction_status($status) status-label" data-status-name="{{__(\'lang_v1.\' . $status)}}" data-orig-value="{{$status}}">{{__(\'lang_v1.\' . $status)}}
                        </span></a>'
                )
                ->editColumn(
                    'payment_status',
                    function ($row) {
                        $payment_status = SupplierTransaction::getPaymentStatus($row);
                        return (string) view('sell.partials.payment_status', ['payment_status' => $payment_status, 'id' => $row->id, 'for_purchase' => true]);
                    }
                )
                ->addColumn('payment_due', function ($row) {
                    $due = $row->final_total - $row->amount_paid;
                    $due_html = '<strong>' . __('lang_v1.purchase') .':</strong> <span class="payment_due" data-orig-value="' . $due . '">' . $this->supplierTransactionUtil->num_f($due, true) . '</span>';

                    // if (!empty($row->return_exists)) {
                    //     $return_due = $row->amount_return - $row->return_paid;
                    //     $due_html .= '<br><strong>' . __('lang_v1.purchase_return') .':</strong> <a href="' . action("SupplierTransactionPaymentController@show", [$row->return_transaction_id]) . '" class="view_purchase_return_payment_modal"><span class="purchase_return" data-orig-value="' . $return_due . '">' . $this->transactionUtil->num_f($return_due, true) . '</span></a>';
                    // }
                    return $due_html;
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("purchase.view")) {
                            return  action('SupplierPurchaseController@show', $row->id) ;
                        } else {
                            return '';
                        }
                    }])
                ->rawColumns(['final_total', 'action', 'payment_due', 'payment_status', 'status', 'ref_no', 'name'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $suppliers = Supplier::suppliersDropdown($business_id, false);
        $kitchen_locations  = KitchenLocation::pluck('name','id'); 


        $orderStatuses = $this->productUtil->orderStatuses();

        return view('supplier_purchase.index')
            ->with(compact('business_locations', 'suppliers', 'kitchen_locations','orderStatuses'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // if (!auth()->user()->can('purchase.create')) {
        //     abort(403, 'Unauthorized action.');
        // }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        }

        $taxes = TaxRate::where('business_id', $business_id)
                        ->ExcludeForTaxGroup()
                        ->get();
        $orderStatuses = $this->productUtil->orderStatuses();
        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];
        $kitchen_locations  = KitchenLocation::pluck('name','id'); 


        $currency_details = $this->supplierTransactionUtil->purchaseCurrencyDetails($business_id);

        $default_purchase_status = null;
        if (request()->session()->get('business.enable_purchase_status') != 1) {
            $default_purchase_status = 'received';
        }

        $types = [];
        if (auth()->user()->can('supplier.create')) {
            $types['supplier'] = __('report.supplier');
        }

        // $customer_groups = CustomerGroup::forDropdown($business_id);

        $business_details = $this->businessUtil->getDetails($business_id);
        $shortcuts = json_decode($business_details->keyboard_shortcuts, true);

        $payment_line = $this->dummyPaymentLine;
        $payment_types = $this->productUtil->payment_types(null, true, $business_id);

        //Accounts
        $accounts = $this->moduleUtil->accountsDropdown($business_id, true);

        $common_settings = !empty(session('business.common_settings')) ? session('business.common_settings') : [];

        return view('supplier_purchase.create')
            ->with(compact('taxes','kitchen_locations', 'orderStatuses', 'business_locations', 'currency_details', 'default_purchase_status', 'types', 'shortcuts', 'payment_line', 'payment_types', 'accounts', 'bl_attributes', 'common_settings'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $business_id = $request->session()->get('user.business_id');
            
            //Check if subscribed or not
            if (!$this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse(action('SupplierPurchaseController@index'));
            }
            
            $transaction_data = $request->only([ 'ref_no', 'status', 'supplier_id', 'transaction_date', 'total_before_tax', 'location_id','discount_type', 'discount_amount','tax_id', 'tax_amount', 'shipping_details', 'shipping_charges', 'final_total', 'additional_notes', 'exchange_rate', 'pay_term_number', 'pay_term_type', 'purchase_order_ids']);
            
            $exchange_rate = $transaction_data['exchange_rate'];
            
            //Reverse exchange rate and save it.
            //$transaction_data['exchange_rate'] = $transaction_data['exchange_rate'];
            
            //TODO: Check for "Undefined index: total_before_tax" issue
            //Adding temporary fix by validating
            
            $request->validate([
                'status' => 'required',
                'supplier_id' => 'required',
                'transaction_date' => 'required',
                'total_before_tax' => 'required',
                'location_id' => 'required',
                'final_total' => 'required',
                'document' => 'file|max:'. (config('constants.document_size_limit') / 1000)
            ]);
            
            $user_id = $request->session()->get('user.id');
            $enable_product_editing = $request->session()->get('business.enable_editing_product_from_purchase');
            
            //Update business exchange rate.
            Business::update_business($business_id, ['p_exchange_rate' => ($transaction_data['exchange_rate'])]);
            
            $currency_details = $this->supplierTransactionUtil->purchaseCurrencyDetails($business_id);

            //unformat input values
            $transaction_data['total_before_tax'] = $this->productUtil->num_uf($transaction_data['total_before_tax'], $currency_details)*$exchange_rate;

            // If discount type is fixed them multiply by exchange rate, else don't
            if ($transaction_data['discount_type'] == 'fixed') {
                $transaction_data['discount_amount'] = $this->productUtil->num_uf($transaction_data['discount_amount'], $currency_details)*$exchange_rate;
            } elseif ($transaction_data['discount_type'] == 'percentage') {
                $transaction_data['discount_amount'] = $this->productUtil->num_uf($transaction_data['discount_amount'], $currency_details);
            } else {
                $transaction_data['discount_amount'] = 0;
            }

            $transaction_data['tax_amount'] = $this->productUtil->num_uf($transaction_data['tax_amount'], $currency_details)*$exchange_rate;
            $transaction_data['shipping_charges'] = $this->productUtil->num_uf($transaction_data['shipping_charges'], $currency_details)*$exchange_rate;
            $transaction_data['final_total'] = $this->productUtil->num_uf($transaction_data['final_total'], $currency_details)*$exchange_rate;

            $transaction_data['business_id'] = $business_id;
            $transaction_data['created_by'] = $user_id;
            $transaction_data['type'] = 'purchase';
            $transaction_data['payment_status'] = 'due';
            $transaction_data['transaction_date'] = $this->productUtil->uf_date($transaction_data['transaction_date'], true);

            //upload document
            $transaction_data['document'] = $this->supplierTransactionUtil->uploadFile($request, 'document', 'documents');

            // $transaction_data['custom_field_1'] = $request->input('custom_field_1', null);
            // $transaction_data['custom_field_2'] = $request->input('custom_field_2', null);
            // $transaction_data['custom_field_3'] = $request->input('custom_field_3', null);
            // $transaction_data['custom_field_4'] = $request->input('custom_field_4', null);

            // $transaction_data['shipping_custom_field_1'] = $request->input('shipping_custom_field_1', null);
            // $transaction_data['shipping_custom_field_2'] = $request->input('shipping_custom_field_2', null);
            // $transaction_data['shipping_custom_field_3'] = $request->input('shipping_custom_field_3', null);
            // $transaction_data['shipping_custom_field_4'] = $request->input('shipping_custom_field_4', null);
            // $transaction_data['shipping_custom_field_5'] = $request->input('shipping_custom_field_5', null);

            // if ($request->input('additional_expense_value_1') != '') {
            //     $transaction_data['additional_expense_key_1'] = $request->input('additional_expense_key_1');
            //     $transaction_data['additional_expense_value_1'] = $this->productUtil->num_uf($request->input('additional_expense_value_1'), $currency_details)*$exchange_rate;
            // }

            // if ($request->input('additional_expense_value_2') != '') {
            //     $transaction_data['additional_expense_key_2'] = $request->input('additional_expense_key_2');
            //     $transaction_data['additional_expense_value_2'] = $this->productUtil->num_uf($request->input('additional_expense_value_2'), $currency_details)*$exchange_rate;
            // }

            // if ($request->input('additional_expense_value_3') != '') {
            //     $transaction_data['additional_expense_key_3'] = $request->input('additional_expense_key_3');
            //     $transaction_data['additional_expense_value_3'] = $this->productUtil->num_uf($request->input('additional_expense_value_3'), $currency_details)*$exchange_rate;
            // }

            // if ($request->input('additional_expense_value_4') != '') {
            //     $transaction_data['additional_expense_key_4'] = $request->input('additional_expense_key_4');
            //     $transaction_data['additional_expense_value_4'] = $this->productUtil->num_uf($request->input('additional_expense_value_4'), $currency_details)*$exchange_rate;
            // }

            DB::beginTransaction();

            //Update reference count
            $ref_count = $this->productUtil->setAndGetReferenceCount($transaction_data['type']);
            //Generate reference number
            if (empty($transaction_data['ref_no'])) {
                $transaction_data['ref_no'] = $this->productUtil->generateReferenceNumber($transaction_data['type'], $ref_count);
            }
            $supplier_transaction = SupplierTransaction::create($transaction_data);
            
            $purchase_lines = [];
            $purchases = $request->input('purchases');

           $this->productUtil->createOrUpdateSupplierPurchaseLines($supplier_transaction, $purchases, $currency_details, $enable_product_editing);

            //Add Purchase payments
            $this->supplierTransactionUtil->createOrUpdateSupplierPaymentLines($supplier_transaction, $request->input('payment'));

            // update payment status
            $this->supplierTransactionUtil->updatePaymentStatus($supplier_transaction->id, $supplier_transaction->final_total);

            if (!empty($supplier_transaction->purchase_order_ids)) {
                $this->supplierTransactionUtil->updatePurchaseOrderStatus($supplier_transaction->purchase_order_ids);
            }

            //Adjust stock over selling if found
            $this->productUtil->adjustSupplierProductStockOverSelling($supplier_transaction);

            $this->supplierTransactionUtil->activityLog($supplier_transaction, 'added');

            DB::commit();

            $output = ['success' => 1,
                            'msg' => __('purchase.purchase_add_success')
                        ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                            'msg' => __('messages.something_went_wrong')
                        ];
        }

        return redirect('supplier-purchases')->with('status', $output);
    }


    public function importPurchaseProducts(Request $request)
    {
       try {
            $file = $request->file('file');

            $parsed_array = Excel::toArray([], $file);
            //Remove header row
            $imported_data = array_splice($parsed_array[0], 1);

            $business_id = $request->session()->get('user.business_id');
            $location_id = $request->input('location_id');
            $row_count = $request->input('row_count');

            $formatted_data = [];
            $row_index = 0;
            $error_msg = '';
            foreach ($imported_data as $key => $value) {
                $row_index = $key + 1;
                $temp_array = [];

                if (!empty($value[0])) {
                    $supplier_product = SupplierProduct::where('sku', trim($value[0]))
                    ->where('business_id', $business_id)
                                    ->with(['unit','product_tax'])
                                    ->first();

                    $temp_array['supplier_product'] = $supplier_product;

                    if (empty($supplier_product)) {
                        $error_msg = __('lang_v1.product_not_found_exception', ['row' => $row_index, 'sku' => $value[0]]);
                        break;
                    }
                } else {
                    $error_msg = __('lang_v1.product_not_found_exception', ['row' => $row_index, 'sku' => $value[0]]);
                    break;
                }

                if (!empty($value[0])) {
                    $temp_array['quantity'] = $value[1];
                } else {
                    $error_msg = __('lang_v1.quantity_required', ['row' => $row_index]);
                    break;
                }

                $temp_array['unit_cost_before_discount'] = !empty($value[2]) ? $value[2] : $supplier_product->purchase_price;
                $temp_array['discount_percent'] = !empty($value[3]) ? $value[3] : 0;

                $tax_id = null;

                if (!empty($value[4])) {
                    $tax_name = trim($value[4]);
                    $tax = TaxRate::where('business_id', $business_id)
                                ->where('name', 'like', "%{$tax_name}%" )
                                ->first();

                    $tax_id =  $tax->id ?? $tax_id;
                }

                $temp_array['tax_id'] = $tax_id;
                $temp_array['lot_number'] = !empty($value[5]) ? $value[5] : null;
                $temp_array['mfg_date'] = !empty($value[6]) ? $this->productUtil->format_date($value[6]) : null;
                $temp_array['exp_date'] = !empty($value[7]) ? $this->productUtil->format_date($value[7]) : null;

                $formatted_data[] = $temp_array;
            }

            if (!empty($error_msg)) {
                return [
                    'success' => false,
                    'msg' => $error_msg
                ];
            }

            $hide_tax = 'hide';
            if ($request->session()->get('business.enable_inline_tax') == 1) {
                $hide_tax = '';
            }

            $taxes = TaxRate::where('business_id', $business_id)
                            ->ExcludeForTaxGroup()
                            ->get();

            $currency_details = $this->supplierTransactionUtil->purchaseCurrencyDetails($business_id);

            $html = view('supplier_purchase.partials.imported_purchase_product_rows')
                        ->with(compact('formatted_data', 'taxes', 'currency_details', 'hide_tax', 'row_count'))->render();

            return [
                    'success' => true,
                    'msg' => __('lang_v.imported'),
                    'html' => $html
                ];
       } catch (\Exception $e) {
        Log::info($e);
           return [
                    'success' => false,
                    'msg' => $e->getMessage()
                ];
       }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // if (!auth()->user()->can('purchase.view')) {
        //     abort(403, 'Unauthorized action.');
        // }

        $business_id = request()->session()->get('user.business_id');
        $taxes = TaxRate::where('business_id', $business_id)
                            ->pluck('name', 'id');
        $purchase = SupplierTransaction::where('business_id', $business_id)
                                ->where('id', $id)
                                ->with(
                                    'supplier',
                                    'supplierPurchaseLines',
                                    'supplierPurchaseLines.product',
                                    'supplierPurchaseLines.product.unit',
                                    'supplierPurchaseLines.variations',
                                    'supplierPurchaseLines.variations.product_variation',
                                    'supplierPurchaseLines.subUnit',
                                    'location',
                                    'paymentLines',
                                    'tax'
                                )
                                ->firstOrFail();
        foreach ($purchase->supplierPurchaseLines as $key => $value) {
            if (!empty($value->sub_unit_id)) {
                $formated_purchase_line = $this->productUtil->changePurchaseLineUnit($value, $business_id);
                $purchase->supplierPurchaseLines[$key] = $formated_purchase_line;
            }
        }

        $payment_methods = $this->productUtil->payment_types($purchase->location_id, true);

        $purchase_taxes = [];
        if (!empty($purchase->tax)) {
            if ($purchase->tax->is_tax_group) {
                $purchase_taxes = $this->supplierTransactionUtil->sumGroupTaxDetails($this->transactionUtil->groupTaxDetails($purchase->tax, $purchase->tax_amount));
            } else {
                $purchase_taxes[$purchase->tax->name] = $purchase->tax_amount;
            }
        }

        //Purchase orders
        $purchase_order_nos = '';
        $purchase_order_dates = '';
        if (!empty($purchase->purchase_order_ids)) {
            $purchase_orders = SupplierTransaction::find($purchase->purchase_order_ids);

            $purchase_order_nos = implode(', ', $purchase_orders->pluck('ref_no')->toArray());
            $order_dates = [];
            foreach ($purchase_orders as $purchase_order) {
                $order_dates[] = $this->supplierTransactionUtil->format_date($purchase_order->transaction_date, true);
            }
            $purchase_order_dates = implode(', ', $order_dates);
        }

        $activities = Activity::forSubject($purchase)
           ->with(['causer', 'subject'])
           ->latest()
           ->get();

        $statuses = $this->productUtil->orderStatuses();

        return view('supplier_purchase.show')
                ->with(compact('taxes', 'purchase', 'payment_methods', 'purchase_taxes', 'activities', 'statuses', 'purchase_order_nos', 'purchase_order_dates'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('purchase.update')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action('PurchaseController@index'));
        }

        //Check if the transaction can be edited or not.
        $edit_days = request()->session()->get('business.transaction_edit_days');
        if (!$this->supplierTransactionUtil->canBeEdited($id, $edit_days)) {
            return back()
                ->with('status', ['success' => 0,
                    'msg' => __('messages.transaction_edit_not_allowed', ['days' => $edit_days])]);
        }

        //Check if return exist then not allowed
        if ($this->supplierTransactionUtil->isReturnExist($id)) {
            return back()->with('status', ['success' => 0,
                    'msg' => __('lang_v1.return_exist')]);
        }

        $business = Business::find($business_id);

        $currency_details = $this->supplierTransactionUtil->purchaseCurrencyDetails($business_id);

        $taxes = TaxRate::where('business_id', $business_id)
                            ->ExcludeForTaxGroup()
                            ->get();
        $purchase = SupplierTransaction::where('business_id', $business_id)
                    ->where('id', $id)
                    ->with(
                        'supplier',
                        'supplierPurchaseLines',
                        'supplierPurchaseLines.product',
                        'supplierPurchaseLines.product.unit',
                        //'purchase_lines.product.unit.sub_units',
                        // 'supplierPurchaseLines.variations',
                        // 'supplierPurchaseLines.variations.product_variation',
                        'location',
                        'supplierPurchaseLines.subUnit',
                        'supplierPurchaseLines.purchaseOrderLine'
                    )
                    ->first();

        foreach ($purchase->supplierPurchaseLines as $key => $value) {
            if (!empty($value->sub_unit_id)) {
                $formated_purchase_line = $this->productUtil->changePurchaseLineUnit($value, $business_id);
                $purchase->supplierPurchaseLines[$key] = $formated_purchase_line;
            }
        }

        $orderStatuses = $this->productUtil->orderStatuses();

        $business_locations = BusinessLocation::forDropdown($business_id);
        $kitchen_locations  = KitchenLocation::pluck('name','id');

        $default_purchase_status = null;
        if (request()->session()->get('business.enable_purchase_status') != 1) {
            $default_purchase_status = 'received';
        }

        $types = [];
        // if (auth()->user()->can('supplier.create')) {
        //     $types['supplier'] = __('report.supplier');
        // }
        // if (auth()->user()->can('customer.create')) {
        //     $types['customer'] = __('report.customer');
        // }

        // $customer_groups = CustomerGroup::forDropdown($business_id);

        $business_details = $this->businessUtil->getDetails($business_id);
        $shortcuts = json_decode($business_details->keyboard_shortcuts, true);

        $common_settings = !empty(session('business.common_settings')) ? session('business.common_settings') : [];

        $purchase_orders = null;
        if(!empty($common_settings['enable_purchase_order'])) {
            $purchase_orders = SupplierTransaction::where('business_id', $business_id)
                                        ->where('type', 'purchase_order')
                                        ->where('supplier_id', $purchase->supplier_id)
                                        ->where( function($q) use($purchase){
                                            $q->where('status', '!=', 'completed');

                                            if (!empty($purchase->purchase_order_ids)) {
                                                $q->orWhereIn('id', $purchase->purchase_order_ids);
                                            }
                                        })
                                        ->pluck('ref_no', 'id');
        }

        return view('supplier_purchase.edit')
            ->with(compact(
                'taxes',
                'purchase',
                'orderStatuses',
                'business_locations',
                'kitchen_locations',
                'business',
                'currency_details',
                'default_purchase_status',
                'types',
                'shortcuts',
                'purchase_orders',
                'common_settings'
            ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('purchase.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            //Validate document size
            $request->validate([
                'document' => 'file|max:'. (config('constants.document_size_limit') / 1000)
            ]);

            $transaction = SupplierTransaction::findOrFail($id);
            $before_status = $transaction->status;
            $business_id = request()->session()->get('user.business_id');
            $enable_product_editing = $request->session()->get('business.enable_editing_product_from_purchase');

            $transaction_before = $transaction->replicate();

            $currency_details = $this->supplierTransactionUtil->purchaseCurrencyDetails($business_id);

            $update_data = $request->only([ 'ref_no', 'status', 'supplier_id',
                            'transaction_date', 'total_before_tax',
                            'discount_type', 'discount_amount', 'tax_id',
                            'tax_amount', 'shipping_details',
                            'shipping_charges', 'final_total',
                            'additional_notes', 'exchange_rate', 'pay_term_number', 'pay_term_type', 'purchase_order_ids']);

            $exchange_rate = $update_data['exchange_rate'];

            //Reverse exchage rate and save
            //$update_data['exchange_rate'] = number_format(1 / $update_data['exchange_rate'], 2);

            $update_data['transaction_date'] = $this->productUtil->uf_date($update_data['transaction_date'], true);

            //unformat input values
            $update_data['total_before_tax'] = $this->productUtil->num_uf($update_data['total_before_tax'], $currency_details) * $exchange_rate;

            // If discount type is fixed them multiply by exchange rate, else don't
            if ($update_data['discount_type'] == 'fixed') {
                $update_data['discount_amount'] = $this->productUtil->num_uf($update_data['discount_amount'], $currency_details) * $exchange_rate;
            } elseif ($update_data['discount_type'] == 'percentage') {
                $update_data['discount_amount'] = $this->productUtil->num_uf($update_data['discount_amount'], $currency_details);
            } else {
                $update_data['discount_amount'] = 0;
            }

            $update_data['tax_amount'] = $this->productUtil->num_uf($update_data['tax_amount'], $currency_details) * $exchange_rate;
            $update_data['shipping_charges'] = $this->productUtil->num_uf($update_data['shipping_charges'], $currency_details) * $exchange_rate;
            $update_data['final_total'] = $this->productUtil->num_uf($update_data['final_total'], $currency_details) * $exchange_rate;
            //unformat input values ends

            $update_data['custom_field_1'] = $request->input('custom_field_1', null);
            $update_data['custom_field_2'] = $request->input('custom_field_2', null);
            $update_data['custom_field_3'] = $request->input('custom_field_3', null);
            $update_data['custom_field_4'] = $request->input('custom_field_4', null);

            $update_data['shipping_custom_field_1'] = $request->input('shipping_custom_field_1', null);
            $update_data['shipping_custom_field_2'] = $request->input('shipping_custom_field_2', null);
            $update_data['shipping_custom_field_3'] = $request->input('shipping_custom_field_3', null);
            $update_data['shipping_custom_field_4'] = $request->input('shipping_custom_field_4', null);
            $update_data['shipping_custom_field_5'] = $request->input('shipping_custom_field_5', null);

            //upload document
            $document_name = $this->supplierTransactionUtil->uploadFile($request, 'document', 'documents');
            if (!empty($document_name)) {
                $update_data['document'] = $document_name;
            }

            $purchase_order_ids = $transaction->purchase_order_ids ?? [];

            $update_data['additional_expense_key_1'] = $request->input('additional_expense_key_1');
            $update_data['additional_expense_key_2'] = $request->input('additional_expense_key_2');
            $update_data['additional_expense_key_3'] = $request->input('additional_expense_key_3');
            $update_data['additional_expense_key_4'] = $request->input('additional_expense_key_4');

            $update_data['additional_expense_value_1'] = $request->input('additional_expense_value_1') != '' ? $this->productUtil->num_uf($request->input('additional_expense_value_1'), $currency_details) * $exchange_rate : 0;
            $update_data['additional_expense_value_2'] = $request->input('additional_expense_value_2') != '' ? $this->productUtil->num_uf($request->input('additional_expense_value_2'), $currency_details) * $exchange_rate: 0;
            $update_data['additional_expense_value_3'] = $request->input('additional_expense_value_3') != '' ? $this->productUtil->num_uf($request->input('additional_expense_value_3'), $currency_details) * $exchange_rate : 0;
            $update_data['additional_expense_value_4'] = $request->input('additional_expense_value_4') != '' ? $this->productUtil->num_uf($request->input('additional_expense_value_4'), $currency_details) * $exchange_rate : 0;

            DB::beginTransaction();

            //update transaction
            $transaction->update($update_data);

            //Update transaction payment status
            $payment_status = $this->supplierTransactionUtil->updatePaymentStatus($transaction->id);
            $transaction->payment_status = $payment_status;

            $purchases = $request->input('purchases');

            $delete_purchase_lines = $this->productUtil->createOrUpdateSupplierPurchaseLines($transaction, $purchases, $currency_details, $enable_product_editing, $before_status);

            //Update mapping of purchase & Sell.
            $this->supplierTransactionUtil->adjustMappingSupplierPurchaseSellAfterEditingSupplierPurchase($before_status, $transaction, $delete_purchase_lines);

            //Adjust stock over selling if found
            $this->productUtil->adjustSupplierProductStockOverSelling($transaction);

            $new_purchase_order_ids = $transaction->purchase_order_ids ?? [];
            $purchase_order_ids = array_merge($purchase_order_ids, $new_purchase_order_ids);
            if (!empty($purchase_order_ids)) {
                $this->transactionUtil->updatePurchaseOrderStatus($purchase_order_ids);
            }

            $this->supplierTransactionUtil->activityLog($transaction, 'edited', $transaction_before);

            DB::commit();

            $output = ['success' => 1,
                            'msg' => __('purchase.purchase_update_success')
                        ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                            'msg' => $e->getMessage()
                        ];
            return back()->with('status', $output);
        }

        return redirect('supplier-purchases')->with('status', $output);
    }


     /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        if (!auth()->user()->can('purchase.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            if (request()->ajax()) {
                $business_id = request()->session()->get('user.business_id');

                //Check if return exist then not allowed
                if ($this->supplierTransactionUtil->isReturnExist($id)) {
                    $output = [
                        'success' => false,
                        'msg' => __('lang_v1.return_exist')
                    ];
                    return $output;
                }

                $transaction = SupplierTransaction::where('id', $id)
                                ->where('business_id', $business_id)
                                ->with(['supplierPurchaseLines'])
                                ->first();

                //Check if lot numbers from the purchase is selected in sale
                // if (request()->session()->get('business.enable_lot_number') == 1 && $this->supplierTransactionUtil->isLotUsed($transaction)) {
                //     $output = [
                //         'success' => false,
                //         'msg' => __('lang_v1.lot_numbers_are_used_in_sale')
                //     ];
                //     return $output;
                // }

                $delete_purchase_lines = $transaction->supplierPurchaseLines;
                DB::beginTransaction();

                $log_properities = [
                    'id' => $transaction->id,
                    'ref_no' => $transaction->ref_no
                ];
                $this->supplierTransactionUtil->activityLog($transaction, 'purchase_deleted', $log_properities);
                $transaction_status = $transaction->status;
                if ($transaction_status != 'received') {
                    $transaction->delete();
                } else {
                    //Delete purchase lines first
                    //Delete purchase lines first
                    $delete_purchase_line_ids = [];
                    foreach ($delete_purchase_lines as $purchase_line) {
                        $delete_purchase_line_ids[] = $purchase_line->id;
                        $this->productUtil->decreaseSupplierProductQuantity(
                            $purchase_line->product_id,
                            $transaction->location_id,
                            $purchase_line->quantity
                        );
                    }
                    SupplierPurchaseLine::where('supplier_transactions_id', $transaction->id)
                                ->whereIn('id', $delete_purchase_line_ids)
                                ->delete();

                    //Update mapping of purchase & Sell.
                    $this->supplierTransactionUtil->adjustMappingSupplierPurchaseSellAfterEditingSupplierPurchase($transaction_status, $transaction, $delete_purchase_lines);
                }
                //Delete Transaction
                $transaction->delete();

                //Delete account transactions
                AccountTransaction::where('transaction_id', $id)->delete();

                DB::commit();

                $output = ['success' => true,
                            'msg' => __('lang_v1.purchase_delete_success')
                        ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => false,
                            'msg' => $e->getMessage()
                        ];
        }

        return $output;
    }

    /**
     * Retrieves supliers list.
     *
     * @return \Illuminate\Http\Response
     */
    public function getSuppliers()
    {
        if (request()->ajax()) {
            $term = request()->q;
            if (empty($term)) {
                return json_encode([]);
            }

            $business_id = request()->session()->get('user.business_id');
            $user_id = request()->session()->get('user.id');

            $query = Supplier::where('business_id', $business_id)
                            ->active();

            // $selected_contacts = User::isSelectedContacts($user_id);
            // if ($selected_contacts) {
            //     $query->join('user_contact_access AS uca', 'contacts.id', 'uca.contact_id')
            //     ->where('uca.user_id', $user_id);
            // }
            $suppliers = $query->where(function ($query) use ($term) {
                $query->where('name', 'like', '%' . $term .'%')
                                ->orWhere('supplier_business_name', 'like', '%' . $term .'%')
                                ->orWhere('supplier.supplier_id', 'like', '%' . $term .'%');
            })
                        ->select(
                            'supplier.id',
                            DB::raw('IF(name="", supplier_business_name, name) as text'),
                            'supplier_business_name as business_name',
                            'supplier.mobile',
                            'supplier.address_line_1',
                            'supplier.address_line_2',
                            'supplier.city',
                            'supplier.state',
                            'supplier.country',
                            'supplier.zip_code',
                            'supplier.supplier_id',
                            'supplier.pay_term_type',
                            'supplier.pay_term_number',
                            'supplier.balance'
                        )
                        ->get();
            return json_encode($suppliers);
        }
    }
    public function getPurchaseEntryRow(Request $request)
    {
        if (request()->ajax()) {
            $product_id = $request->input('id');
            $business_id = request()->session()->get('user.business_id');

            $hide_tax = 'hide';
            if ($request->session()->get('business.enable_inline_tax') == 1) {
                $hide_tax = '';
            }

            if (!empty($product_id)) {
                $row_count = $request->input('row_count');
                $supplier_products = SupplierProduct::where('id', $product_id)
                                    ->with(['unit','product_tax'])
                                    ->get();
                $currency_details = $this->supplierTransactionUtil->purchaseCurrencyDetails($business_id);

                $taxes = TaxRate::where('business_id', $business_id)
                            ->ExcludeForTaxGroup()
                            ->get();
                return view('supplier_purchase.partials.purchase_entry_row')
                    ->with(compact(
                        'supplier_products',
                        'row_count',
                        'taxes',
                        'currency_details',
                        'hide_tax'
                    ));
            }
        }
    }

    /**
     * Retrieves products list.
     *
     * @return \Illuminate\Http\Response
     */
    public function getProducts()
    {
        if (request()->ajax()) {
            $term = request()->term;
            if (empty($term)) {
                return json_encode([]);
            }

            $business_id = request()->session()->get('user.business_id');
            $q = SupplierProduct::leftJoin(
                'tax_rates',
                'supplier_products.tax',
                '=',
                'tax_rates.id'
            )->leftJoin(
                'supplier_product_categories',
                'supplier_products.category_id',
                '=',
                'supplier_product_categories.id')
                ->where(function ($query) use ($term) {
                    $query->where('supplier_products.name', 'like', '%' . $term .'%');
                    $query->orWhere('supplier_products.sku', 'like', '%' . $term .'%');
                    $query->orWhere('supplier_product_categories.name', 'like', '%' . $term .'%');
                })
                ->where('supplier_products.business_id', $business_id)
                ->whereNull('supplier_products.deleted_at')
                ->select(
                    'supplier_products.id as id',
                    'supplier_products.name as name',
                    'supplier_products.sku as sku',
                    'tax_rates.name as tax',
                    'supplier_product_categories.name as category',
                );
            $supplier_products = $q->get();
            $no_of_records = $supplier_products->count();
            return json_encode($supplier_products);
        }
    }

    /**
     * Update purchase status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request)
    {
        if (!auth()->user()->can('purchase.update') && !auth()->user()->can('purchase.update_status')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $transaction = SupplierTransaction::where('business_id', $business_id)
                                ->where('type', 'purchase')
                                ->findOrFail($request->input('purchase_id'));

            $before_status = $transaction->status;
            $update_data['status'] = $request->input('status');
            DB::beginTransaction();
            //update transaction
            $transaction->update($update_data);
            $currency_details = $this->supplierTransactionUtil->purchaseCurrencyDetails($business_id);
            foreach ($transaction->supplierPurchaseLines as $purchase_line) {
                $this->productUtil->updateSupplierProductStock($before_status, $transaction, $purchase_line->product_id, $purchase_line->quantity, $purchase_line->quantity, $currency_details);
            }

            //Update mapping of purchase & Sell.
            $this->supplierTransactionUtil->adjustMappingSupplierPurchaseSellAfterEditingSupplierPurchase($before_status, $transaction, null);

            //Adjust stock over selling if found
            $this->productUtil->adjustSupplierProductStockOverSelling($transaction);
            DB::commit();

            $output = ['success' => 1,
                            'msg' => __('purchase.purchase_update_success')
                        ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                            'msg' => $e->getMessage()
                        ];
        }

        return $output;
    }
    public function checkRefNumber(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $supplier_id  = $request->input('supplier_id');
        $ref_no       = $request->input('ref_no');
        $purchase_id  = $request->input('purchase_id');

        $count = 0;
        if (!empty($contact_id) && !empty($ref_no)) {
            //check in transactions table
            $query = SupplierTransaction::where('business_id', $business_id)
                            ->where('ref_no', $ref_no)
                            ->where('supplier_id', $supplier_id);
            if (!empty($purchase_id)) {
                $query->where('id', '!=', $purchase_id);
            }
            $count = $query->count();
        }
        if ($count == 0) {
            echo "true";
            exit;
        } else {
            echo "false";
            exit;
        }
    }
}
