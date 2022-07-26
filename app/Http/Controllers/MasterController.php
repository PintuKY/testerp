<?php

namespace App\Http\Controllers;

use App\Exports\MasterListExport;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\MasterList;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionActivity;
use App\Models\TransactionSellLine;
use App\Models\TransactionSellLinesDay;
use App\Utils\AppConstant;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MasterController extends Controller
{
    /**
     * Constructor
     *
     * @param Util $commonUtil
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil,BusinessUtil $businessUtil, ModuleUtil $moduleUtil,ProductUtil $productUtil)
    {
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $role = 'user';
        $masterListCols = config('masterlist.' . $role . '_columns');
        $masterListStatus= config('masterlist.' . $role . '_status');
        $business_id = request()->session()->get('user.business_id');
        $sells = MasterList::whereHas('transasction', function ($query) use($masterListStatus){
            $query->whereIn('status',$masterListStatus);
        })->with(['transaction_sell_lines','transaction_sell_lines.transactionSellLinesVariants']
        );

        /*$business_id = BusinessLocation::with(['kitchenLocation' => function ($q) {
            $q->select('name as kitchen_name', 'id');
        }]);*/

        if (!empty(request()->start_date) && !empty(request()->end_date)) {
            $start = request()->start_date;
            $end = request()->end_date;
            $sells->whereDate('master_list.delivery_date', '>=', $start)
                ->whereDate('master_list.delivery_date', '<=', $end);
        }

        if (!empty(request()->type)) {
            $type = request()->type;
            $sells->where('master_list.time_slot', '=', $type);
        }

        if (!empty(request()->location)) {
            $sells->whereHas('transasction', function ($query) {
                $query->where('location_id', request()->location);
            });
        }
        $sell = $sells->get();
        $lunch = $sell->where('time_slot',AppConstant::LUNCH)->count();
        $dinner = $sell->where('time_slot',AppConstant::DINNER)->count();
        if (request()->ajax()) {
            $sells = MasterList::whereHas('transasction', function ($query) use($masterListStatus){
                $query->whereIn('status',$masterListStatus);
            })->with(['transaction_sell_lines','transaction_sell_lines.transactionSellLinesVariants']
            );

            /*$business_id = BusinessLocation::with(['kitchenLocation' => function ($q) {
                $q->select('name as kitchen_name', 'id');
            }]);*/

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end = request()->end_date;
                $sells->whereDate('master_list.delivery_date', '>=', $start)
                    ->whereDate('master_list.delivery_date', '<=', $end);
            }

            if (!empty(request()->type)) {
                $type = request()->type;
                $sells->where('master_list.time_slot', '=', $type);
            }

            if (!empty(request()->location)) {
                $sells->whereHas('transasction', function ($query) {
                    $query->where('location_id', request()->location);
                });
            }
            return Datatables::of($sells)
                ->addColumn(
                    'action', function ($row) {
                    $html = '<div class="btn-group"><button type="button" class="btn btn-info dropdown-toggle btn-xs"
                        data-toggle="dropdown" aria-expanded="false">' .
                        __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right" role="menu">
                    <li><a target="_blank" href="' . action('MasterController@edit', [$row->id]) . '"><i class="fas fa-edit"></i> ' . __("messages.edit") . '</a></li>
                    </ul></div>';
                    return $html;
                })
                ->addColumn('cancel_reason', function ($row) {
                    return getReasonName($row->cancel_reason);
                })
                ->addColumn('type', function ($row) {
                    if($row->transaction_sell_lines){
                        if($row->transaction_sell_lines_id == $row->transaction_sell_lines->id){
                            $type = $row->transaction_sell_lines->product_name. '('.$row->transaction_sell_lines->unit_name.')';
                        }
                    }
                    return $type;
                })
                ->addColumn('compensate', function ($row) {
                    if ($row->is_compensate == AppConstant::COMPENSATE_NO) {
                        $data = 'No';
                    } else {
                        $data = 'Yes';
                    }
                    return $data;
                })
                ->addColumn('pax', function ($row) {
                    $pax = [];
                    if (isset($row->transaction_sell_lines->transactionSellLinesVariants)) {
                        foreach ($row->transaction_sell_lines->transactionSellLinesVariants as $value) {
                            if (str_contains($value->pax, 'Serving Pax')) {
                                $pax[] = $value->addon;
                            }
                        }
                    }
                    return implode(',', $pax);
                })
                ->addColumn('addon', function ($row) {
                    $addon = [];
                    if (isset($row->transaction_sell_lines->transactionSellLinesVariants)) {
                        foreach ($row->transaction_sell_lines->transactionSellLinesVariants as $value) {
                            if (str_contains($value->pax, 'Add on')) {
                                $addon_pax = ($value->addon  != 'None') ? '+'.$value->addon : '';
                                $addon[] = str_replace("Add on:","",$value->pax).''.$addon_pax;
                            }
                        }
                    }
                    return implode(',', $addon);
                })
                ->editColumn('date', function ($row) {
                    if($row->time_slot == AppConstant::STATUS_INACTIVE){
                        $date = $row->start_date;
                    }else{
                        $date = $row->delivery_date;
                    }
                    return $date;
                })
                ->addColumn('address', function ($row) {
                    return $row->shipping_address_line_1;
                })
                ->addColumn('postal', function ($row) {
                    return $row->shipping_zip_code;
                })
                ->addColumn('remark', function ($row) {
                    return 'fsfsfsfsd';
                })
                ->addColumn('hp_number', function ($row) {
                    return 'fsfsfsf';
                })
                ->addColumn('driver_name', function ($row) {
                    return 'Amar';
                })
                ->addColumn('meal_type', function ($row) {
                    return getMealTypes($row->time_slot);
                })
                ->filterColumn('date', function ($query, $keyword) {
                    $query->whereRaw("DATE_FORMAT(delivery_date,'%Y-%m-%d') LIKE ?", ["%$keyword%"]);
                })
                ->make(true);
        }
        $business_locations = BusinessLocation::forDropdown($business_id);
        $type = config('masterlist.product_type');
        return view('master.index', compact('masterListCols', 'business_locations', 'type','lunch','dinner'));

    }

    public function getMasterList($id,$sell_id)
    {

        $role = 'user';
        $masterListCols = config('masterlist.' . $role . '_columns');
        $masterListStatus= config('masterlist.' . $role . '_status');
        $sells = MasterList::whereHas('transasction', function ($query) use($masterListStatus){
            $query->whereIn('status',$masterListStatus);
        })->with(['transaction_sell_lines','transaction_sell_lines.transactionSellLinesVariants']
        );

        /*$business_id = BusinessLocation::with(['kitchenLocation' => function ($q) {
            $q->select('name as kitchen_name', 'id');
        }]);*/

        if (!empty(request()->start_date) && !empty(request()->end_date)) {
            $start = request()->start_date;
            $end = request()->end_date;
            $sells->whereDate('master_list.delivery_date', '>=', $start)
                ->whereDate('master_list.delivery_date', '<=', $end);
        }

        if (!empty(request()->type)) {
            $type = request()->type;
            $sells->where('master_list.time_slot', '=', $type);
        }

        if (!empty(request()->location)) {
            $sells->whereHas('transasction', function ($query) {
                $query->where('location_id', request()->location);
            });
        }

        $lunch = $sells->where('time_slot',AppConstant::LUNCH)->get();
        $dinner = $sells->where('time_slot',AppConstant::DINNER)->get();
        dd('aaa'.$lunch);
        if (request()->ajax()) {
            $sells = MasterList::where(['transaction_id'=>$id,'transaction_sell_lines_id'=>$sell_id])->with(['transaction_sell_lines' => function ($query) {
                $query->with('transactionSellLinesVariants');
            }, 'transasction']);

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end = request()->end_date;
                $sells->whereDate('master_list.delivery_date', '>=', $start)
                    ->whereDate('master_list.delivery_date', '<=', $end);
            }

            if (!empty(request()->type)) {
                $type = request()->type;
                $sells->where('master_list.time_slot', '=', $type);
            }

            if (!empty(request()->location)) {
                $sells->whereHas('transasction', function ($query) {
                    $query->where('location_id', request()->location);
                });
            }

            return Datatables::of($sells)
                ->addColumn('cancel_reason', function ($row) {
                    return getReasonName($row->cancel_reason);
                })
                ->addColumn('type', function ($row) {
                    if($row->transaction_sell_lines){
                        if($row->transaction_sell_lines_id == $row->transaction_sell_lines->id){
                            $type = $row->transaction_sell_lines->product_name. '('.$row->transaction_sell_lines->unit_name.')';
                        }
                    }
                    return $type;
                })
                ->addColumn('compensate', function ($row) {
                    if ($row->is_compensate == AppConstant::COMPENSATE_NO) {
                        $data = 'No';
                    } else {
                        $data = 'Yes';
                    }
                    return $data;
                })
                ->addColumn('pax', function ($row) {
                    //dd($row->transaction_sell_lines->transactionSellLinesVariants[0]->pax);
                    $pax = [];
                    if (isset($row->transaction_sell_lines->transactionSellLinesVariants)) {
                        foreach ($row->transaction_sell_lines->transactionSellLinesVariants as $value) {
                            if (str_contains($value->pax, 'Serving Pax')) {
                                $pax[] = $value->pax;
                            }
                        }
                    }
                    return implode(',',$pax);
                })
                ->addColumn('addon', function ($row) {
                    $addon = [];
                    if (isset($row->transaction_sell_lines->transactionSellLinesVariants)) {
                        foreach ($row->transaction_sell_lines->transactionSellLinesVariants as $value) {
                            if (str_contains($value->pax, 'Add on')) {
                                $addon_pax = ($value->value != 'None') ? '+' . $value->value : '';
                                $addon[] = str_replace("Add on:", "", $value->pax) . '' . $addon_pax;
                            }
                        }

                    }
                    return $row->transaction_sell_lines->transactionSellLinesVariants[0]->addon;
                })
                ->addColumn('date', function ($row) {
                    if($row->time_slot == AppConstant::STATUS_INACTIVE){
                        $date = $row->start_date;
                    }else{
                        $date = $row->delivery_date. ' ' . $row->delivery_time;
                    }
                    return $date;
                })
                ->addColumn('address', function ($row) {
                    return $row->shipping_address_line_1;
                })
                ->addColumn('postal', function ($row) {
                    return $row->shipping_zip_code;
                })
                ->addColumn('remark', function ($row) {
                    return 'fsfsfsfsd';
                })
                ->addColumn('hp_number', function ($row) {
                    return 'fsfsfsf';
                })
                ->addColumn('driver_name', function ($row) {
                    return 'Amar';
                })
                ->addColumn('meal_type', function ($row) {
                    return getMealTypes($row->time_slot);
                })
                ->make(true);
        }
        $business_locations = Business::forDropdown();
        $type = config('masterlist.product_type');


    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('direct_sell.update') && !auth()->user()->can('so.update')) {
            abort(403, 'Unauthorized action.');
        }
        $default_datetime = $this->businessUtil->format_date('now', true);
        $default_time = $this->businessUtil->format_times(Carbon::parse(now())->format('H:i'));

        return view('sell.partials.compensate')->with(compact('default_datetime', 'default_time'));
    }


    public function exportExcel($type)
    {
        return \Excel::download(new MasterListExport, 'masterList.' . $type);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        if (!auth()->user()->can('direct_sell.update') && !auth()->user()->can('so.update')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $total_cansel_sell = MasterList::where(['transaction_id' => $request->transaction_id, 'is_compensate' => AppConstant::COMPENSATE_NO/*, 'status' => AppConstant::STATUS_CANCEL*/])->whereNotNull('cancel_reason')->count();
            $total_compensate = MasterList::where(['transaction_id' => $request->transaction_id, 'is_compensate' => AppConstant::COMPENSATE_YES])->count();
            $compensates = $total_cansel_sell - $total_compensate;
            if ($compensates > 0) {
                $compensate = MasterList::where(['transaction_id' => $request->transaction_id, 'is_compensate' => AppConstant::COMPENSATE_NO])->whereNotNull('cancel_reason')->first();
                $add_compensate = $compensate->replicate();
                $add_compensate->time_slot = $request->time_slot;
                $add_compensate->is_compensate = AppConstant::COMPENSATE_YES;
                $add_compensate->cancel_reason = null;
                $add_compensate->delivery_date = Carbon::parse($request->delivery_date)->format('Y-m-d');
                $add_compensate->delivery_time = Carbon::parse($request->delivery_date)->format('H:i');
                $add_compensate->save();
                $output = ['success' => true,
                    'msg' => __("master.master_list_compensate_add_success")
                ];

                TransactionActivity::insert([
                    'type' => TransactionActivityTypes()['Auto'],
                    'transaction_id' => $request->transaction_id,
                    'comment' => 'compensate added for '. $compensate->delivery_date ,
                    'created_at' => Carbon::now()->toDateTimeString(),
                    'updated_at' => Carbon::now()->toDateTimeString()
                ]);
            } else {
                $output = ['success' => false,
                    'msg' => __("master.master_list_compensate_not_add")
                ];
            }

        } catch (\Exception $e) {
            dd($e->getMessage());
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;

    }


    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $current_time = Carbon::parse(now())->format('H:i');

        if ($current_time == AppConstant::DELIVERED_LUNCH_STATUS_TIME) {
            $master_list = MasterList::where(['status' => AppConstant::STATUS_ACTIVE, 'time_slot' => AppConstant::LUNCH])->whereDate('delivery_date', '=', date('Y-m-d'))->get();
            foreach ($master_list as $delivered) {
                MasterList::where('id', $delivered->id)->update([
                    'status' => AppConstant::STATUS_DELIVERED
                ]);
            }
        }
        if ($current_time == AppConstant::DELIVERED_DINNER_STATUS_TIME) {
            $master_list = MasterList::where(['status' => AppConstant::STATUS_ACTIVE, 'time_slot' => AppConstant::DINNER])->whereDate('delivery_date', '=', date('Y-m-d'))->get();
            foreach ($master_list as $delivered) {
                MasterList::where('id', $delivered->id)->update([
                    'status' => AppConstant::STATUS_DELIVERED
                ]);
            }
        }
        $master_list = MasterList::findOrFail($id);

        $transaction = Transaction::findOrFail($master_list->transaction_id);
        $sell_line = TransactionSellLine::findOrFail($master_list->transaction_sell_lines_id);
        $location_id = $transaction->location_id;
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
            ->where('transaction_sell_lines.transaction_id', $master_list->transaction_id)
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
                    if ($transaction->status != AppConstant::FINAL || $transaction->status != AppConstant::COMPLETED || $transaction->status != AppConstant::PROCESSING) {
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
                    $business_id = request()->session()->get('user.business_id');
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
                        if($transaction->status == AppConstant::FINAL || $transaction->status == AppConstant::COMPLETED || $transaction->status == AppConstant::PROCESSING){
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
        $tran_sell_days = TransactionSellLinesDay::where('transaction_sell_lines_id',$master_list->transaction_sell_lines_id)->pluck('day')->toArray();
        $days = [];
        foreach($tran_sell_days as $day){
            $days[] = getDayNameByDayNumber($day);
        }
        $transaction_sell_lines_days_val = implode(',', $days);
        return view('master.edit')
            ->with(compact('master_list','transaction','sell_line','product_ids','product_count','edit_product','product_names','sell_details','transaction_sell_lines_days_val'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'cancel_reason' => 'required',
            ]);
            $master_list_details = $request->only(['cancel_reason', 'status']);
            $master_list = MasterList::findOrFail($id);
            $master_list->update($master_list_details);
            $getReason = getReasonName($request->cancel_reason);
            $this->moduleUtil->activityLog($master_list, 'edited', null, ['id' => $master_list->id, 'reason' => $getReason]);
            $output = ['success' => 1, 'msg' => __("master.master_list_update_success")];
        } catch (\Exception $e) {
            dd($e->getMessage());
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];
        }
        return redirect('master');
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
