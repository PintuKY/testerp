<?php

namespace App\Http\Controllers;

use App\Models\AccountTransaction;
use App\Models\PurchaseLine;
use App\Models\Transaction;
use App\Utils\AppConstant;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Models\BusinessLocation;
use App\Models\SupplierPurchaseLine;
use App\Models\SupplierTransaction;
use App\Utils\SupplierTransactionUtil;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;

class PurchaseReturnController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $supplierTransactionUtil;
    protected $productUtil;

    /**
     * Constructor
     *
     * @param TransactionUtil $transactionUtil
     * @return void
     */
    public function __construct(SupplierTransactionUtil $supplierTransactionUtil, ProductUtil $productUtil)
    {
        $this->supplierTransactionUtil = $supplierTransactionUtil;
        $this->productUtil = $productUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('purchase.view') && !auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $purchases_returns = SupplierTransaction::leftJoin('supplier', 'supplier_transactions.supplier_id', '=', 'supplier.id')
                    ->join(
                        'business_locations AS BS',
                        'supplier_transactions.location_id',
                        '=',
                        'BS.id'
                    )
                    ->leftJoin(
                        'supplier_transactions AS ST',
                        'supplier_transactions.return_parent_id',
                        '=',
                        'ST.id'
                    )
                    ->leftJoin(
                        'supplier_transaction_payments AS STP',
                        'supplier_transactions.id',
                        '=',
                        'STP.supplier_transaction_id'
                    )
                    ->where('supplier_transactions.business_id', $business_id)
                    ->where('supplier_transactions.type', 'purchase_return')
                    ->select(
                        'supplier_transactions.id',
                        'supplier_transactions.transaction_date',
                        'supplier_transactions.ref_no',
                        'supplier.name',
                        'supplier.supplier_business_name',
                        'supplier_transactions.status',
                        'supplier_transactions.payment_status',
                        'supplier_transactions.final_total',
                        'supplier_transactions.return_parent_id',
                        'BS.name as location_name',
                        'ST.ref_no as parent_purchase',
                        DB::raw('SUM(STP.amount) as amount_paid')
                    )
                    ->groupBy('supplier_transactions.id');

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $purchases_returns->whereIn('supplier_transactions.location_id', $permitted_locations);
            }

            if (!empty(request()->location_id)) {
                $purchases_returns->where('supplier_transactions.location_id', request()->location_id);
            }

            if (!empty(request()->supplier_id)) {
                $supplier_id = request()->supplier_id;
                $purchases_returns->where('supplier.id', $supplier_id);
            }
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $purchases_returns->whereDate('supplier_transactions.transaction_date', '>=', $start)
                            ->whereDate('supplier_transactions.transaction_date', '<=', $end);
            }
            return Datatables::of($purchases_returns)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                                    <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                                        data-toggle="dropdown" aria-expanded="false">' .
                                        __("messages.actions") .
                                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                        </span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right" role="menu">';
                    if (!empty($row->return_parent_id)) {
                        $html .= '<li><a href="' . action('PurchaseReturnController@add', $row->return_parent_id) . '" ><i class="glyphicon glyphicon-edit"></i>' .
                                __("messages.edit") .
                                '</a></li>';
                    } else {
                        $html .= '<li><a href="' . action('CombinedPurchaseReturnController@edit', $row->id) . '" ><i class="glyphicon glyphicon-edit"></i>' .
                                __("messages.edit") .
                                '</a></li>';
                    }

                    if ($row->payment_status != "paid") {
                        $html .= '<li><a href="' . action('SupplierTransactionPaymentController@addPayment', [$row->id]) . '" class="add_payment_modal"><i class="fas fa-money-bill-alt"></i>' . __("purchase.add_payment") . '</a></li>';
                    }

                    $html .= '<li><a href="' . action('SupplierTransactionPaymentController@show', [$row->id]) . '" class="view_payment_modal"><i class="fas fa-money-bill-alt"></i>' . __("purchase.view_payments") . '</a></li>';

                    $html .= '<li><a href="' . action('PurchaseReturnController@destroy', $row->id) . '" class="delete_purchase_return" ><i class="fa fa-trash"></i>' .
                                __("messages.delete") .
                                '</a></li>';
                    $html .= '</ul></div>';

                    return $html;
                })
                ->removeColumn('id')
                ->removeColumn('return_parent_id')
                ->editColumn(
                    'final_total',
                    '<span class="display_currency final_total" data-currency_symbol="true" data-orig-value="{{$final_total}}">{{$final_total}}</span>'
                )
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn('name', function($row){

                    $name = !empty($row->name) ? $row->name : "";
                    return $name . " " . $row->supplier_business_name;
                })
                ->editColumn(
                    'payment_status',
                    '<a href="{{ action("SupplierTransactionPaymentController@show", [$id])}}" class="view_payment_modal payment-status payment-status-label" data-orig-value="{{$payment_status}}" data-status-name="@if($payment_status != "paid"){{__(\'lang_v1.\' . $payment_status)}}@else{{__("lang_v1.received")}}@endif"><span class="label @payment_status($payment_status)">@if($payment_status != "paid"){{__(\'lang_v1.\' . $payment_status)}} @else {{__("lang_v1.received")}} @endif
                        </span></a>'
                )
                ->editColumn('parent_purchase', function ($row) {
                    $html = '';
                    if (!empty($row->parent_purchase)) {
                        $html = '<a href="#" data-href="' . action('SupplierPurchaseController@show', [$row->return_parent_id]) . '" class="btn-modal" data-container=".view_modal">' . $row->parent_purchase . '</a>';
                    }
                    return $html;
                })
                ->addColumn('payment_due', function ($row) {
                    $due = $row->final_total - $row->amount_paid;
                    return '<span class="display_currency payment_due" data-currency_symbol="true" data-orig-value="' . $due . '">' . $due . '</sapn>';
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("purchase.view")) {
                            $return_id = !empty($row->return_parent_id) ? $row->return_parent_id : $row->id;
                            return  action('PurchaseReturnController@show', [$return_id]) ;
                        } else {
                            return '';
                        }
                    }])
                ->rawColumns(['final_total', 'action', 'payment_status', 'parent_purchase', 'payment_due'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('purchase_return.index')->with(compact('business_locations'));
    }

    /**
     * Show the form for purchase return.
     *
     * @return \Illuminate\Http\Response
     */
    public function add($id)
    {
        if (!auth()->user()->can('purchase.update')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');

        $purchase = SupplierTransaction::where('business_id', $business_id)
                        ->where('type', 'purchase')
                        ->with(['supplierPurchaseLines', 'supplier', 'tax', 'returnParent', 'supplierPurchaseLines.subUnit', 'supplierPurchaseLines.product', 'supplierPurchaseLines.product.unit'])
                        ->find($id);

        foreach ($purchase->supplierPurchaseLines as $key => $value) {
            if (!empty($value->sub_unit_id)) {
                $formated_purchase_line = $this->productUtil->changePurchaseLineUnit($value, $business_id);
                $purchase->purchase_lines[$key] = $formated_purchase_line;
            }
        }

        foreach ($purchase->supplierPurchaseLines as $key => $value) {
            $qty_available = $value->quantity - $value->quantity_sold - $value->quantity_adjusted;

            $purchase->supplierPurchaseLines[$key]->formatted_qty_available = $this->supplierTransactionUtil->num_f($qty_available);
        }

        return view('purchase_return.add')
                    ->with(compact('purchase'));
    }

    /**
     * Saves Purchase returns in the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('purchase.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $purchase = SupplierTransaction::where('business_id', $business_id)
                        ->where('type', 'purchase')
                        ->with(['supplierPurchaseLines', 'supplierPurchaseLines.subUnit'])
                        ->findOrFail($request->input('supplier_transaction_id'));

            $return_quantities = $request->input('returns');
            $return_total = 0;

            DB::beginTransaction();

            foreach ($purchase->supplierPurchaseLines as $purchase_line) {
                $old_return_qty = $purchase_line->quantity_returned;

                $return_quantity = !empty($return_quantities[$purchase_line->id]) ? $this->productUtil->num_uf($return_quantities[$purchase_line->id]) : 0;

                $multiplier = 1;
                if (!empty($purchase_line->subUnit->base_unit_multiplier)) {
                    $multiplier = $purchase_line->subUnit->base_unit_multiplier;
                    $return_quantity = $return_quantity * $multiplier;
                }

                $purchase_line->quantity_returned = $return_quantity;
                $purchase_line->save();
                $return_total += $purchase_line->purchase_price_inc_tax * $purchase_line->quantity_returned;

                  //Decrease quantity in variation location details
                  if ($old_return_qty != $purchase_line->quantity_returned) {
                    $this->productUtil->decreaseSupplierProductQuantity(
                        $purchase_line->product_id,
                        $purchase->location_id,
                        $purchase_line->quantity_returned,
                        $old_return_qty
                    );
                }
            }
            $return_total_inc_tax = $return_total + $request->input('tax_amount');

            $return_transaction_data = [
                'total_before_tax' => $return_total,
                'final_total' => $return_total_inc_tax,
                'tax_amount' => $request->input('tax_amount'),
                'tax_id' => $purchase->tax_id
            ];

            if (empty($request->input('ref_no'))) {
                //Update reference count
                $ref_count = $this->supplierTransactionUtil->setAndGetReferenceCount('purchase_return');
                $return_transaction_data['ref_no'] = $this->supplierTransactionUtil->generateReferenceNumber('purchase_return', $ref_count);
            }

            $return_transaction = SupplierTransaction::where('business_id', $business_id)
                                            ->where('type', 'purchase_return')
                                            ->where('return_parent_id', $purchase->id)
                                            ->first();

            if (!empty($return_transaction)) {
                $return_transaction_before = $return_transaction->replicate();

                $return_transaction->update($return_transaction_data);

                $this->supplierTransactionUtil->activityLog($return_transaction, 'edited', $return_transaction_before);
            } else {
                $return_transaction_data['business_id'] = $business_id;
                $return_transaction_data['location_id'] = $purchase->location_id;
                $return_transaction_data['type'] = 'purchase_return';
                $return_transaction_data['status'] = AppConstant::FINAL;
                $return_transaction_data['supplier_id'] = $purchase->supplier_id;
                $return_transaction_data['transaction_date'] = Carbon::now();
                $return_transaction_data['created_by'] = request()->session()->get('user.id');
                $return_transaction_data['return_parent_id'] = $purchase->id;

                $return_transaction = SupplierTransaction::create($return_transaction_data);

                $this->supplierTransactionUtil->activityLog($return_transaction, 'added');
            }

            //update payment status
            $this->supplierTransactionUtil->updatePaymentStatus($return_transaction->id, $return_transaction->final_total);

            $output = ['success' => 1,
                            'msg' => __('lang_v1.purchase_return_added_success')
                        ];

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                            'msg' => __('messages.something_went_wrong')
                        ];
        }

        return redirect('purchase-return')->with('status', $output);
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!auth()->user()->can('purchase.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $purchase = Transaction::where('business_id', $business_id)
                        ->with(['return_parent', 'return_parent.tax', 'purchase_lines', 'contact', 'tax', 'purchase_lines.sub_unit', 'purchase_lines.product', 'purchase_lines.product.unit'])
                        ->find($id);

        foreach ($purchase->purchase_lines as $key => $value) {
            if (!empty($value->sub_unit_id)) {
                $formated_purchase_line = $this->productUtil->changePurchaseLineUnit($value, $business_id);
                $purchase->purchase_lines[$key] = $formated_purchase_line;
            }
        }

        $purchase_taxes = [];
        if (!empty($purchase->return_parent->tax)) {
            if ($purchase->return_parent->tax->is_tax_group) {
                $purchase_taxes = $this->transactionUtil->sumGroupTaxDetails($this->transactionUtil->groupTaxDetails($purchase->return_parent->tax, $purchase->return_parent->tax_amount));
            } else {
                $purchase_taxes[$purchase->return_parent->tax->name] = $purchase->return_parent->tax_amount;
            }
        }

        //For combined purchase return return_parent is empty
        if (empty($purchase->return_parent) && !empty($purchase->tax)) {
            if ($purchase->tax->is_tax_group) {
                $purchase_taxes = $this->transactionUtil->sumGroupTaxDetails($this->transactionUtil->groupTaxDetails($purchase->tax, $purchase->tax_amount));
            } else {
                $purchase_taxes[$purchase->tax->name] = $purchase->tax_amount;
            }
        }
        $return = !empty($purchase->return_parent) ? $purchase->return_parent : $purchase;
        $activities = Activity::forSubject($return)
           ->with(['causer', 'subject'])
           ->latest()
           ->get();

        return view('purchase_return.show')
                ->with(compact('purchase', 'purchase_taxes', 'activities'));
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


                $purchase_return = SupplierTransaction::where('id', $id)
                                ->where('business_id', $business_id)
                                ->where('type', 'purchase_return')
                                ->with(['supplierPurchaseLines'])
                                ->first();

                DB::beginTransaction();

                if (empty($purchase_return->return_parent_id)) {
                    $delete_purchase_lines = $purchase_return->supplierPurchaseLines;
                    $delete_purchase_line_ids = [];
                    foreach ($delete_purchase_lines as $purchase_line) {
                        $delete_purchase_line_ids[] = $purchase_line->id;
                        $this->productUtil->updateSupplierProductQuantity($purchase_return->location_id, $purchase_line->product_id, $purchase_line->variation_id, $purchase_line->quantity_returned, 0, null, false);
                    }
                    SupplierPurchaseLine::where('supplier_transactions_id', $purchase_return->id)
                                ->whereIn('id', $delete_purchase_line_ids)
                                ->delete();
                } else {
                    $parent_purchase = SupplierTransaction::where('id', $purchase_return->return_parent_id)
                                ->where('business_id', $business_id)
                                ->where('type', 'purchase')
                                ->with(['supplierPurchaseLines'])
                                ->first();

                    $updated_purchase_lines = $parent_purchase->supplierPurchaseLines;
                    foreach ($updated_purchase_lines as $purchase_line) {
                       // $this->productUtil->updateProductQuantity($parent_purchase->location_id, $purchase_line->product_id, $purchase_line->variation_id, $purchase_line->quantity_returned, 0, null, false);
                        $purchase_line->quantity_returned = 0;
                        $purchase_line->save();
                    }
                }

                //Delete Transaction
                $purchase_return->delete();

                //Delete account transactions
                AccountTransaction::where('transaction_id', $id)->delete();

                DB::commit();

                $output = ['success' => true,
                            'msg' => __('lang_v1.deleted_success')
                        ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => false,
                            'msg' => __('messages.something_went_wrong')
                        ];
        }

        return $output;
    }
}
