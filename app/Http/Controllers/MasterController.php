<?php

namespace App\Http\Controllers;

use App\Models\MasterList;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use function GuzzleHttp\Promise\all;

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
        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            $sells = MasterList::with(['transaction_sell_lines' => function($query){
                $query->with('transactionSellLinesVariants');
            }]);

            return Datatables::of($sells)
                ->addColumn(
                    'action',function ($row) {
                    $html='<div class="btn-group"><button type="button" class="btn btn-info dropdown-toggle btn-xs"
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
                ->addColumn('cancel_reason', function($row){
                    return getReasonName($row->cancel_reason);
                })
                ->addColumn('compensate', function ($row){
                    if($row->is_compensate == 0){
                        $data = 'No';
                    }else{
                        $data = 'Yes';
                    }
                    return $data;
                })
                ->addColumn('addon', function ($row) {
                    $addon = [];
                    if (isset($row->transaction_sell_lines->transactionSellLinesVariants)) {
                        foreach ($row->transaction_sell_lines->transactionSellLinesVariants as $value) {
                            if (str_contains($value->name, 'Add on')) {
                                $addon_pax = ($value->value  != 'None') ? '+'.$value->value : '';
                                $addon[] = str_replace("Add on:","",$value->name).''.$addon_pax;
                            }
                        }

                    }
                    return implode(',', $addon);
                })
                ->editColumn(
                    'hp_number',
                    '8df98sdf8dsif'
                )
                ->editColumn(
                    'driver_name',
                    'driver name'
                )
                ->rawColumns(['pax', 'addon', 'hp_number', 'driver_name', 'action'])
                ->make(true);
        }
        return view('master.index');

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

        return view('sell.partials.compensate')->with(compact('default_datetime','default_time'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        if (!auth()->user()->can('direct_sell.update') && !auth()->user()->can('so.update')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $total_cansel_sell = MasterList::where(['transaction_id'=>$request->transaction_id,'is_compensate'=>'0','status'=>'1'])->whereNotNull('cancel_reason')->count();
            $total_compensate = MasterList::where(['transaction_id'=>$request->transaction_id,'is_compensate'=>'1'])->count();
            $compensates = $total_cansel_sell - $total_compensate;
            if($compensates > 0){
                $compensate = MasterList::where(['transaction_id'=>$request->transaction_id,'is_compensate'=>'0'])->whereNotNull('cancel_reason')->first();
                $add_compensate = $compensate->replicate();
                $add_compensate->time_slot = $request->time_slot;
                $add_compensate->is_compensate = '1';
                $add_compensate->cancel_reason = null;
                $add_compensate->delivery_date = Carbon::parse($request->delivery_date)->format('Y-m-d');
                $add_compensate->delivery_time =Carbon::parse($request->delivery_date)->format('H:i');
                $add_compensate->save();
                $output = ['success' => true,
                    'msg' => __("master.master_list_compensate_add_success")
                ];
            }else{
                $output = ['success' => false,
                    'msg' => __("master.master_list_compensate_not_add")
                ];
            }

        } catch (\Exception $e) {
            dd($e->getMessage());
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $master_list = MasterList::findOrFail($id);

        return view('master.edit')
            ->with(compact('master_list'));
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
        try {
            $request->validate([
                'cancel_reason' => 'required',
            ]);
            $master_list_details = $request->only(['cancel_reason','status']);
            $master_list = MasterList::findOrFail($id);
            $master_list->update($master_list_details);
            $getReason = getReasonName($request->cancel_reason);
            $this->moduleUtil->activityLog($master_list, 'edited', null, ['id' => $master_list->id,'reason'=>$getReason]);
            $output = ['success' => 1,'msg' => __("master.master_list_update_success")];
        }catch (\Exception $e) {
            dd($e->getMessage());
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];
        }
        return redirect('master');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
