<?php

namespace App\Http\Controllers;

use App\Models\BusinessLocation;
use App\Models\Contact;
use App\Models\Supplier;
use App\Models\SupplierPurchaseLine;
use App\Models\SupplierTransaction;
use App\Models\SupplierTransactionPayments;
use Illuminate\Http\Request;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\SupplierTransactionUtil;
use App\Utils\SupplierUtil;
use App\Utils\Util;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Spatie\Activitylog\Models\Activity;

class SupplierController extends Controller
{   

    protected $commonUtil;
    protected $supplierUtil;
    protected $supplierTransactionUtil;
    protected $moduleUtil;
    protected $notificationUtil;

    /**
     * Constructor
     *
     * @param Util $commonUtil
     * @return void
     */
    public function __construct(
        Util $commonUtil,
        ModuleUtil $moduleUtil,
        SupplierTransactionUtil $supplierTransactionUtil,
        NotificationUtil $notificationUtil,
        SupplierUtil $supplierUtil
    ) {
        $this->commonUtil = $commonUtil;
        $this->supplierUtil = $supplierUtil;
        $this->moduleUtil = $moduleUtil;
        $this->supplierTransactionUtil = $supplierTransactionUtil;
        $this->notificationUtil = $notificationUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            return $this->indexSupplier();
        }

        $reward_enabled = (request()->session()->get('business.enable_rp') == 1 ) ? true : false;

        return view('supplier.index')
            ->with(compact('reward_enabled'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('supplier.create') && !auth()->user()->can('supplier.view_own')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        }

        $types = [];
        if (auth()->user()->can('supplier.create') || auth()->user()->can('supplier.view_own')) {
            $types['supplier'] = __('report.supplier');
        }
        if (auth()->user()->can('customer.create') || auth()->user()->can('customer.view_own')) {
            $types['customer'] = __('report.customer');
        }


        // $customer_groups = CustomerGroup::forDropdown($business_id);
        $selected_type = request()->type;
        $module_form_parts = $this->moduleUtil->getModuleData('contact_form_part');

        return view('supplier.create')
            ->with(compact('types', 'selected_type', 'module_form_parts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {   
        if (!auth()->user()->can('supplier.create') && !auth()->user()->can('customer.create') && !auth()->user()->can('customer.view_own') && !auth()->user()->can('supplier.view_own')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            if (!$this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse();
            }

            $input = $request->only(['supplier_business_name',
                'prefix', 'first_name', 'middle_name', 'last_name', 'tax_number', 'pay_term_number', 'pay_term_type', 'mobile', 'landline', 'alternate_number', 'city', 'state', 'country', 'address_line_1','email' ,'address_line_2', 'zip_code', 'supplier_id']);

            $name_array = [];

            if (!empty($input['prefix'])) {
                $name_array[] = $input['prefix'];
            }
            if (!empty($input['first_name'])) {
                $name_array[] = $input['first_name'];
            }
            if (!empty($input['middle_name'])) {
                $name_array[] = $input['middle_name'];
            }
            if (!empty($input['last_name'])) {
                $name_array[] = $input['last_name'];
            }

            $input['name'] = trim(implode(' ', $name_array));

            if (!empty($input['dob'])) {
                $input['dob'] = $this->commonUtil->uf_date($input['dob']);
            }

            $input['business_id'] = $business_id;
            $input['created_by'] = $request->session()->get('user.id');

            $input['credit_limit'] = $request->input('credit_limit') != '' ? $this->commonUtil->num_uf($request->input('credit_limit')) : null;
            $input['opening_balance'] = $this->commonUtil->num_uf($request->input('opening_balance'));

            $output = $this->supplierUtil->createNewSupplier($input);

            $this->moduleUtil->getModuleData('after_supplier_saved', ['supplier' => $output['data'], 'input' => $request->input()]);

            $this->supplierUtil->activityLog($output['data'], 'added');

        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => false,
                            'msg' =>__("messages.something_went_wrong")
                        ];
        }

        return $output;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Supplier  $supplier
     * @return \Illuminate\Http\Response
     */
    public function show(Supplier $supplier)
    {
        if (!auth()->user()->can('supplier.view') && !auth()->user()->can('customer.view') && !auth()->user()->can('customer.view_own') && !auth()->user()->can('supplier.view_own')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $supplier = $this->supplierUtil->getSupplierInfo($business_id, $supplier->id);

        if (!auth()->user()->can('supplier.view') && auth()->user()->can('supplier.view_own')) {
            if ($supplier->created_by != auth()->user()->id) {
                abort(403, 'Unauthorized action.');
            }
        }
        
        $reward_enabled = (request()->session()->get('business.enable_rp') == 1 && in_array($supplier->type, ['customer', 'both'])) ? true : false;

        $supplier_dropdown = Supplier::suppliersDropdown($business_id, false, true);

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        //get contact view type : ledger, notes etc.
        $view_type = request()->get('view');
        if (is_null($view_type)) {
            $view_type = 'ledger';
        }
        
        $supplier_view_tabs = $this->moduleUtil->getModuleData('get_supplier_view_tabs');
        
        $activities = Activity::forSubject($supplier)
           ->with(['causer', 'subject'])
           ->latest()
           ->get();

        return view('supplier.show')
             ->with(compact('supplier', 'reward_enabled', 'supplier_dropdown' ,'business_locations', 'view_type', 'supplier_view_tabs', 'activities'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Supplier  $supplier
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('supplier.update') && !auth()->user()->can('customer.update') && !auth()->user()->can('customer.view_own') && !auth()->user()->can('supplier.view_own')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $supplier = Supplier::where('business_id', $business_id)->find($id);

            if (!$this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse();
            }

            $types = [];
            // if (auth()->user()->can('supplier.create')) {
            //     $types['supplier'] = __('report.supplier');
            // }
            // if (auth()->user()->can('supplier.create')) {
            //     $types['customer'] = __('report.customer');
            // }


            // $customer_groups = CustomerGroup::forDropdown($business_id);

            $ob_transaction =  SupplierTransaction::where('supplier_id', $id)
                                            ->where('type', 'opening_balance')
                                            ->first();
            
            $opening_balance = !empty($ob_transaction->final_total) ? $ob_transaction->final_total : 0;
            
            //Deduct paid amount from opening balance.
            if (!empty($opening_balance)) {
                $opening_balance_paid = $this->supplierTransactionUtil->getTotalAmountPaid($ob_transaction->id);
                if (!empty($opening_balance_paid)) {
                    $opening_balance = $opening_balance - $opening_balance_paid;
                }

                $opening_balance = $this->commonUtil->num_f($opening_balance);
            }

            return view('supplier.edit')
                ->with(compact('supplier', 'types', 'opening_balance'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Supplier  $supplier
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Supplier $supplier)
    {
        if (!auth()->user()->can('supplier.update') && !auth()->user()->can('supplier.view_own')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $input = $request->only(['supplier_business_name', 'prefix', 'first_name', 'middle_name', 'last_name', 'tax_number', 'pay_term_number', 'pay_term_type', 'mobile', 'address_line_1', 'address_line_2', 'zip_code', 'dob', 'alternate_number', 'city', 'state', 'country', 'landline', 'supplier_id', 'email','position']);

                $name_array = [];

                if (!empty($input['prefix'])) {
                    $name_array[] = $input['prefix'];
                }
                if (!empty($input['first_name'])) {
                    $name_array[] = $input['first_name'];
                }
                if (!empty($input['middle_name'])) {
                    $name_array[] = $input['middle_name'];
                }
                if (!empty($input['last_name'])) {
                    $name_array[] = $input['last_name'];
                }

                $input['name'] = trim(implode(' ', $name_array));

                // $input['is_export'] = !empty($request->input('is_export')) ? 1 : 0;

                if (!empty($input['dob'])) {
                    $input['dob'] = $this->commonUtil->uf_date($input['dob']);
                }

                $input['credit_limit'] = $request->input('credit_limit') != '' ? $this->commonUtil->num_uf($request->input('credit_limit')) : null;

                $business_id = $request->session()->get('user.business_id');

                $input['opening_balance'] = $this->commonUtil->num_uf($request->input('opening_balance'));

                if (!$this->moduleUtil->isSubscribed($business_id)) {
                    return $this->moduleUtil->expiredResponse();
                }

                $output = $this->supplierUtil->updateSupplier($input, $supplier->id, $business_id);

                $this->supplierUtil->activityLog($output['data'], 'edited');

            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output = ['success' => false,
                            'msg' => __("messages.something_went_wrong")
                        ];
            }

            return $output;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Supplier  $supplier
     * @return \Illuminate\Http\Response
     */
    public function destroy(Supplier $supplier)
    {
        if (!auth()->user()->can('supplier.delete') && !auth()->user()->can('customer.delete') && !auth()->user()->can('customer.view_own') && !auth()->user()->can('supplier.view_own')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->user()->business_id;

                //Check if any transaction related to this supplier exists
                $count = SupplierTransaction::where('business_id', $business_id)
                                    ->where('supplier_id', $supplier->id)
                                    ->count();
                if ($count == 0) {
                    $contact = Supplier::where('business_id', $business_id)->findOrFail($supplier->id);
                    if (!$contact->is_default) {

                        $log_properities = [
                            'id' => $contact->id,
                            'name' => $contact->name,
                            'supplier_business_name' => $contact->supplier_business_name
                        ];
                        $this->supplierUtil->activityLog($contact, 'contact_deleted', $log_properities);

                        //Disable login for associated users
                        // User::where('crm_contact_id', $contact->id)
                        //     ->update(['allow_login' => 0]);

                        $contact->delete();
                    }
                    $output = ['success' => true,
                                'msg' => __("supplier.deleted_success")
                                ];
                } else {
                    $output = ['success' => false,
                                'msg' => __("lang_v1.you_cannot_delete_this_supplier")
                                ];
                }
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output = ['success' => false,
                            'msg' => __("messages.something_went_wrong")
                        ];
            }

            return $output;
        }
    }

    private function indexSupplier()
    {   
        if (!auth()->user()->can('supplier.view') && !auth()->user()->can('supplier.view_own')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $supplier = $this->supplierUtil->getSupplierQuery($business_id);

        if (request()->has('has_purchase_due')) {
           $supplier->havingRaw('(total_purchase - purchase_paid) > 0');
        }

        if (request()->has('has_purchase_return')) {
           $supplier->havingRaw('total_purchase_return > 0');
        }

        if (request()->has('has_advance_balance')) {
           $supplier->where('balance', '>', 0);
        }

        if (request()->has('has_opening_balance')) {
           $supplier->havingRaw('opening_balance > 0');
        }

        if (!empty(request()->input('supplier_status'))) {
            $supplier->where('supplier.supplier_status', request()->input('supplier_status'));
        }

        // $is_admin = $this->contactUtil->is_admin(auth()->user());
        // if (!$is_admin) {
        //     $user_id = auth()->user()->id;
        //     $selected_contacts = User::isSelectedContacts($user_id);
        //     if ($selected_contacts) {
        //         $contact->join('user_contact_access AS uca', 'contacts.id', 'uca.contact_id')
        //         ->where('uca.user_id', $user_id);
        //     }
        // }
        return Datatables::of($supplier)
            ->addColumn('address', '{{implode(", ", array_filter([$address_line_1, $address_line_2, $city, $state, $country, $zip_code]))}}')
            ->addColumn(
                'due',
                '<span class="contact_due" data-orig-value="{{$total_purchase - $purchase_paid}}" data-highlight=false>@format_currency($total_purchase - $purchase_paid)</span>'
            )
            ->addColumn(
                'return_due',
                '<span class="return_due" data-orig-value="{{$total_purchase_return - $purchase_return_paid}}" data-highlight=false>@format_currency($total_purchase_return - $purchase_return_paid)'
            )
            ->addColumn(
                'action',
                function ($row) {
                    $html = '<div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                        data-toggle="dropdown" aria-expanded="false">' .
                        __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                    // $html .= '<li><a href="' . action('TransactionPaymentController@getPayContactDue', [$row->id]) . '?type=purchase" class="pay_purchase_due"><i class="fas fa-money-bill-alt" aria-hidden="true"></i>' . __("lang_v1.pay") . '</a></li>';

                    // $return_due = $row->total_purchase_return - $row->purchase_return_paid;
                    // if ($return_due > 0) {
                    //     $html .= '<li><a href="' . action('TransactionPaymentController@getPayContactDue', [$row->id]) . '?type=purchase_return" class="pay_purchase_due"><i class="fas fa-money-bill-alt" aria-hidden="true"></i>' . __("lang_v1.receive_purchase_return_due") . '</a></li>';
                    // }

                    if (auth()->user()->can('supplier.view') || auth()->user()->can('supplier.view_own')) {
                        $html .= '<li><a href="' . action('SupplierController@show', [$row->id]) . '"><i class="fas fa-eye" aria-hidden="true"></i>' . __("messages.view") . '</a></li>';
                    }
                    if (auth()->user()->can('supplier.update')) {
                        $html .= '<li><a href="' . action('SupplierController@edit', [$row->id]) . '" class="edit_supplier_button"><i class="glyphicon glyphicon-edit"></i>' .  __("messages.edit") . '</a></li>';
                    }
                    if (auth()->user()->can('supplier.delete')) {
                        $html .= '<li><a href="' . action('SupplierController@destroy', [$row->id]) . '" class="delete_supplier_button"><i class="glyphicon glyphicon-trash"></i>' . __("messages.delete") . '</a></li>';
                    }

                    if (auth()->user()->can('supplier.update')) {
                        $html .= '<li><a href="' . action('SupplierController@updateStatus', [$row->id]) . '"class="update_supplier_status"><i class="fas fa-power-off"></i>';

                        if ($row->supplier_status == "active") {
                            $html .= __("messages.deactivate");
                        } else {
                            $html .= __("messages.activate");
                        }

                        $html .= "</a></li>";
                    }

                    $html .= '<li class="divider"></li>';
                    if (auth()->user()->can('supplier.view')) {
                        $html .= '
                                <li>
                                    <a href="' . action('SupplierController@show', [$row->id]). '?view=ledger">
                                        <i class="fas fa-scroll" aria-hidden="true"></i>
                                        ' . __("lang_v1.ledger") . '
                                    </a>
                                </li>';

                        
                        $html .= '<li>
                            <a href="' . action('SupplierController@show', [$row->id]) . '?view=purchase">
                                <i class="fas fa-arrow-circle-down" aria-hidden="true"></i>
                                ' . __("purchase.purchases") . '
                            </a>
                        </li>
                        <li>
                            <a href="' . action('SupplierController@show', [$row->id]) . '?view=stock_report">
                                <i class="fas fa-hourglass-half" aria-hidden="true"></i>
                                ' . __("report.stock_report") . '
                            </a>
                        </li>';
                        

                        // if (in_array($row->type, ["both", "customer"])) {
                        //     $html .=  '<li>
                        //         <a href="' . action('ContactController@show', [$row->id]). '?view=sales">
                        //             <i class="fas fa-arrow-circle-up" aria-hidden="true"></i>
                        //             ' . __("sale.sells") . '
                        //         </a>
                        //     </li>';
                        // }

                        $html .= '<li>
                                <a href="' . action('SupplierController@show', [$row->id]) . '?view=documents_and_notes">
                                    <i class="fas fa-paperclip" aria-hidden="true"></i>
                                     ' . __("lang_v1.documents_and_notes") . '
                                </a>
                            </li>';
                    }
                    $html .= '</ul></div>';

                    return $html;
                }
            )
            ->editColumn('opening_balance', function ($row) {
                $html = '<span data-orig-value="' . $row->opening_balance . '">' . $this->supplierTransactionUtil->num_f($row->opening_balance, true) . '</span>';
                return $html;
            })
            ->editColumn('balance', function ($row) {
                $html = '<span data-orig-value="' . $row->balance . '">' . $this->supplierTransactionUtil->num_f($row->balance, true) . '</span>';

                return $html;
            })
            ->editColumn('pay_term', '
                @if(!empty($pay_term_type) && !empty($pay_term_number))
                    {{$pay_term_number}}
                    @lang("lang_v1.".$pay_term_type)
                @endif
            ')
            ->editColumn('name', function ($row) {
                if ($row->contact_status == 'inactive') {
                    return $row->name . ' <small class="label pull-right bg-red no-print">' . __("lang_v1.inactive") . '</small>';
                } else {
                    return $row->name;
                }
            })
            ->editColumn('created_at', '{{@format_date($created_at)}}')
            ->removeColumn('opening_balance_paid')
            ->removeColumn('type')
            ->removeColumn('id')
            ->removeColumn('total_purchase')
            ->removeColumn('purchase_paid')
            ->removeColumn('total_purchase_return')
            ->removeColumn('purchase_return_paid')
            ->filterColumn('address', function ($query, $keyword) {
                $query->where( function($q) use ($keyword){
                    $q->where('address_line_1', 'like', "%{$keyword}%")
                    ->orWhere('address_line_2', 'like', "%{$keyword}%")
                    ->orWhere('city', 'like', "%{$keyword}%")
                    ->orWhere('state', 'like', "%{$keyword}%")
                    ->orWhere('country', 'like', "%{$keyword}%")
                    ->orWhere('zip_code', 'like', "%{$keyword}%")
                    ->orWhereRaw("CONCAT(COALESCE(address_line_1, ''), ', ', COALESCE(address_line_2, ''), ', ', COALESCE(city, ''), ', ', COALESCE(state, ''), ', ', COALESCE(country, '') ) like ?", ["%{$keyword}%"]);
                });
            })
            ->rawColumns(['action', 'opening_balance', 'pay_term', 'due', 'return_due', 'name', 'balance'])
            ->make(true);
    }

    public function checkSupplierId(Request $request)
    {   
        $supplier_id = $request->input('supplier_id');

        $valid = 'true';
        if (!empty($supplier_id)) {
            $business_id = $request->session()->get('user.business_id');
            $hidden_id = $request->input('hidden_id');

            $query = Supplier::where('business_id', $business_id)
                            ->where('supplier_id', $supplier_id);
            if (!empty($hidden_id)) {
                $query->where('id', '!=', $hidden_id);
            }
            $count = $query->count();
            if ($count > 0) {
                $valid = 'false';
            }
        }
        echo $valid;
        exit;
    }

    public function checkMobile(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        $mobile_number = $request->input('mobile_number');

        $query = Supplier::where('business_id', $business_id)
                        ->where('mobile', 'like', "%{$mobile_number}");

        if (!empty($request->input('supplier_id'))) {
            $query->where('id', '!=', $request->input('supplier_id'));
        }

        $supplier = $query->pluck('name')->toArray();

        return [
            'is_mobile_exists' => !empty($supplier),
            'msg' => __('lang_v1.mobile_already_registered', ['supplier' => implode(', ', $supplier), 'mobile' => $mobile_number])
        ];
    }

    public function updateStatus($id)
    {   
        if (!auth()->user()->can('supplier.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $supplier = Supplier::where('business_id', $business_id)->find($id);
            $supplier->supplier_status = $supplier->supplier_status == 'active' ? 'inactive' : 'active';
            $supplier->save();

            $output = ['success' => true,
                                'msg' => __("supplier.updated_success")
                                ];
            return $output;
        }
    }

    /**
     * Display contact locations on map
     *
     */
    public function supplierMap()
    {
        if (!auth()->user()->can('supplier.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $query = Supplier::where('business_id', $business_id)
                        ->active()
                        ->whereNotNull('position');

        if (!empty(request()->input('supplier'))) {
            $query->whereIn('id', request()->input('supplier'));
        }
        $suppliers = $query->get();

        $all_suppliers = Supplier::where('business_id', $business_id)
                        ->active()
                        ->get();

        return view('supplier.supplier_map')
             ->with(compact('supplier', 'all_suppliers'));
    }

    /**
     * Shows ledger for contacts
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function getLedger()
    {   
        if (!auth()->user()->can('supplier.view') && !auth()->user()->can('supplier.view_own')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $supplier_id = request()->input('supplier_id');

        $start_date = request()->start_date;
        $end_date =  request()->end_date;

        $supplier = Supplier::find($supplier_id);

        if (!auth()->user()->can('supplier.view') && auth()->user()->can('supplier.view_own')) {
            if ($supplier->created_by != auth()->user()->id) {
                abort(403, 'Unauthorized action.');
            }
        }
       
        $ledger_details = $this->supplierTransactionUtil->getLedgerDetails($supplier_id, $start_date, $end_date);

        if (request()->input('action') == 'pdf') {
            $for_pdf = true;
            $html = view('supplier.ledger')
             ->with(compact('ledger_details', 'supplier', 'for_pdf'))->render();
            $mpdf = $this->getMpdf();
            $mpdf->WriteHTML($html);
            $mpdf->Output();
        }

        return view('supplier.ledger')
             ->with(compact('ledger_details', 'supplier'));
    }

    public function getSupplierStockReport($supplier_id)
    {   
        $pl_query_string = $this->commonUtil->get_pl_quantity_sum_string();
        $query = SupplierPurchaseLine::join('supplier_transactions as t', 't.id', '=', 'supplier_purchase_lines.supplier_transactions_id')
                        ->join('products as p', 'p.id', '=', 'supplier_purchase_lines.product_id')
                        ->join('variations as v', 'v.id', '=', 'supplier_purchase_lines.variation_id')
                        ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                        ->join('units as u', 'p.unit_id', '=', 'u.id')
                        ->whereIn('t.type', ['purchase', 'purchase_return'])
                        ->where('t.supplier_id', $supplier_id)
                        ->select(
                            'p.name as product_name',
                            'v.name as variation_name',
                            'pv.name as product_variation_name',
                            'p.type as product_type',
                            'u.short_name as product_unit',
                            'v.sub_sku',
                            DB::raw('SUM(quantity) as purchase_quantity'),
                            DB::raw('SUM(quantity_returned) as total_quantity_returned'),
                            DB::raw('SUM(quantity_sold) as total_quantity_sold'),
                            DB::raw("SUM( COALESCE(quantity - ($pl_query_string), 0) * purchase_price_inc_tax) as stock_price"),
                            DB::raw("SUM( COALESCE(quantity - ($pl_query_string), 0)) as current_stock")
                        )->groupBy('supplier_purchase_lines.variation_id');

        if (!empty(request()->location_id)) {
            $query->where('t.location_id', request()->location_id);
        }

        $product_stocks =  Datatables::of($query)
                            ->editColumn('product_name', function ($row) {
                                $name = $row->product_name;
                                if ($row->product_type == 'variable') {
                                    $name .= ' - ' . $row->product_variation_name . '-' . $row->variation_name;
                                }
                                return $name . ' (' . $row->sub_sku . ')';
                            })
                            ->editColumn('purchase_quantity', function ($row) {
                                $purchase_quantity = 0;
                                if ($row->purchase_quantity) {
                                    $purchase_quantity =  (float)$row->purchase_quantity;
                                }

                                return '<span data-is_quantity="true" class="display_currency" data-currency_symbol=false  data-orig-value="' . $purchase_quantity . '" data-unit="' . $row->product_unit . '" >' . $purchase_quantity . '</span> ' . $row->product_unit;
                            })
                            ->editColumn('total_quantity_sold', function ($row) {
                                $total_quantity_sold = 0;
                                if ($row->total_quantity_sold) {
                                    $total_quantity_sold =  (float)$row->total_quantity_sold;
                                }

                                return '<span data-is_quantity="true" class="display_currency" data-currency_symbol=false  data-orig-value="' . $total_quantity_sold . '" data-unit="' . $row->product_unit . '" >' . $total_quantity_sold . '</span> ' . $row->product_unit;
                            })
                            ->editColumn('stock_price', function ($row) {
                                $stock_price = 0;
                                if ($row->stock_price) {
                                    $stock_price =  (float)$row->stock_price;
                                }

                                return '<span class="display_currency" data-currency_symbol=true >' . $stock_price . '</span> ';
                            })
                            ->editColumn('current_stock', function ($row) {
                                $current_stock = 0;
                                if ($row->current_stock) {
                                    $current_stock =  (float)$row->current_stock;
                                }

                                return '<span data-is_quantity="true" class="display_currency" data-currency_symbol=false  data-orig-value="' . $current_stock . '" data-unit="' . $row->product_unit . '" >' . $current_stock . '</span> ' . $row->product_unit;
                            });

        return $product_stocks->rawColumns(['current_stock', 'stock_price', 'total_quantity_sold', 'purchase_quantity'])->make(true);
    }

    public function getSupplierPayments($supplier_id)
    {   
        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {

            $payments = SupplierTransactionPayments::leftjoin('supplier_transactions as t', 'supplier_transaction_payments.supplier_transaction_id', '=', 't.id')
            ->leftjoin('supplier_transaction_payments as parent_payment', 'supplier_transaction_payments.parent_id', '=', 'parent_payment.id')
            ->where('supplier_transaction_payments.business_id', $business_id)
            ->whereNull('supplier_transaction_payments.parent_id')
            ->with(['child_payments', 'child_payments.transaction'])
            ->where('supplier_transaction_payments.payment_for', $supplier_id)
                ->select(
                    'supplier_transaction_payments.id',
                    'supplier_transaction_payments.amount',
                    'supplier_transaction_payments.is_return',
                    'supplier_transaction_payments.method',
                    'supplier_transaction_payments.paid_on',
                    'supplier_transaction_payments.payment_ref_no',
                    'supplier_transaction_payments.parent_id',
                    'supplier_transaction_payments.transaction_no',
                    't.invoice_no',
                    't.ref_no',
                    't.type as transaction_type',
                    't.id as supplier_transactions_id',
                    'supplier_transaction_payments.cheque_number',
                    'supplier_transaction_payments.card_transaction_number',
                    'supplier_transaction_payments.bank_account_number',
                    'supplier_transaction_payments.id as DT_RowId',
                    'parent_payment.payment_ref_no as parent_payment_ref_no'
                )
                ->groupBy('supplier_transaction_payments.id')
                ->orderByDesc('supplier_transaction_payments.paid_on')
                ->paginate();
    
            $payment_types = $this->supplierTransactionUtil->payment_types(null, true, $business_id);

            return view('supplier.partials.supplier_payments_tab')
                    ->with(compact('payments', 'payment_types'));
        }
    }

    
}
