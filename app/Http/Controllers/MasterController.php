<?php

namespace App\Http\Controllers;

use App\Exports\MasterListExport;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\MasterList;
use App\Utils\AppConstant;
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
    public function __construct(BusinessUtil $businessUtil, ModuleUtil $moduleUtil)
    {
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;
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
        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $sells = MasterList::with(['transaction_sell_lines' => function ($query) {
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
                    $query->where('business_id', request()->location);
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
                }
                )
                ->addColumn('cancel_reason', function ($row) {
                    return getReasonName($row->cancel_reason);
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
                            if (str_contains($value->name, 'Serving Pax')) {
                                $pax[] = $value->value;
                            }
                        }
                    }
                    return implode(',', $pax);
                })
                ->addColumn('addon', function ($row) {
                    $addon = [];
                    if (isset($row->transaction_sell_lines->transactionSellLinesVariants)) {
                        foreach ($row->transaction_sell_lines->transactionSellLinesVariants as $value) {
                            if (str_contains($value->name, 'Add on')) {
                                $addon_pax = ($value->value != 'None') ? '+' . $value->value : '';
                                $addon[] = str_replace("Add on:", "", $value->name) . '' . $addon_pax;
                            }
                        }

                    }
                    return implode(',', $addon);
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
                ->make(true);
        }
        $business_locations = Business::forDropdown();
        $type = config('masterlist.product_type');
        return view('master.index', compact('masterListCols', 'business_locations', 'type'));

    }

    public function getMasterList($id)
    {
        $role = 'user';
        $masterListCols = config('masterlist.' . $role . '_columns');
        if (request()->ajax()) {
            $sells = MasterList::where('transaction_id',$id)->with(['transaction_sell_lines' => function ($query) {
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
                    $query->where('business_id', request()->location);
                });
            }

            return Datatables::of($sells)
                ->addColumn('cancel_reason', function ($row) {
                    return getReasonName($row->cancel_reason);
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
                    $pax = '';
                    if (isset($row->transaction_sell_lines->transactionSellLinesVariants)) {
                        foreach ($row->transaction_sell_lines->transactionSellLinesVariants as $value) {
                            if (str_contains($value->name, 'Serving Pax')) {
                                $pax = $value->pax;
                            }
                        }
                    }
                    return $pax;
                })
                ->addColumn('addon', function ($row) {
                    $addon = [];
                    if (isset($row->transaction_sell_lines->transactionSellLinesVariants)) {
                        foreach ($row->transaction_sell_lines->transactionSellLinesVariants as $value) {
                            if (str_contains($value->name, 'Add on')) {
                                $addon_pax = ($value->value != 'None') ? '+' . $value->value : '';
                                $addon[] = $value->addon;
                            }
                        }

                    }
                    return implode(',', $addon);
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

        return view('master.edit')
            ->with(compact('master_list'));
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
