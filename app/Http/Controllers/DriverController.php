<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\DriverAttendance;
use App\Utils\AppConstant;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\Facades\DataTables;
use App\Models\KitchenLocation;

class DriverController extends Controller
{

     /**
     * Constructor
     *
     * @param Util $commonUtil
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil,ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->businessUtil = $businessUtil;
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('driver.view') && !auth()->user()->can('user.create')) {
            abort(403, 'Unauthorized action.');
        }

        $driver_name_list = Driver::pluck('name','id')->toArray();
        Session::forget('filter_name');
        Session::forget('filter_start_date');
        Session::forget('filter_end_date');
        $driver_name = (request()->driver_name)?request()->driver_name:'';
        $start_date = (request()->start_date)?request()->start_date:'';
        $end_date = (request()->end_date)?request()->end_date:'';
        Session::put('filter_name',$driver_name);
        Session::put('filter_start_date',$start_date);
        Session::put('filter_end_date',$end_date);
        if (request()->ajax()) {
            $driver = Driver::with('driverAttendance','kitchenLocation');
            if (!empty(request()->driver_name)) {
                $driver->where('id', request()->driver_name);
            }

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end = request()->end_date;
                $driver->whereHas('driverAttendance', function ($query) use($start,$end){
                    $query->whereDate('attendance_date', '>=', $start)
                        ->whereDate('attendance_date', '<=', $end);
                });
            }
            return DataTables::of($driver)
                ->addColumn(
                    'action',
                    '@can("driver.update")
                        <a href="{{action(\'DriverController@edit\', [$id])}}" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</a>
                        &nbsp;
                    @endcan
                    @can("driver.delete")
                        <button data-href="{{action(\'DriverController@destroy\', [$id])}}" class="btn btn-xs btn-danger delete_driver_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>
                    @endcan'
                )->editColumn('driver_type',function ($row){
                    $type = getDriverType($row->driver_type);
                    return $type;
                })->editColumn('kitchen_location_id',function ($row){
                    return $row->kitchenLocation->name ?? null;
                })
                // ->filterColumn('name', function ($query, $keyword) {
                //     $query->where('name',, $keyword]);
                // })
                ->make(true);
        }

        return view('driver.index')->with(compact('driver_name_list','driver_name','start_date','end_date'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        if (!auth()->user()->can('driver.create')) {
            abort(403, 'Unauthorized action.');
        }
        $kitchens = KitchenLocation::pluck('name', 'id');
        return view('driver.create',compact('kitchens'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('driver.create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required',
            'email' => 'required|unique:drivers,email',
            'phone' => 'required|unique:drivers,phone',
            'address_line_1' => 'required',
            'city' => 'required',
            'state' => 'required',
            'country' => 'required',
            'driver_type' => 'required',
            'kitchen_location_id' => 'required'
        ]);

        try {
            $driver_details = $request->only(['name', 'email','phone','address_line_1','address_line_2','city','state','country','is_active','driver_type','kitchen_location_id']);
            $driver_details['status'] = !empty($request->input('is_active')) ? $request->input('is_active') : AppConstant::STATUS_INACTIVE;
            $driver = Driver::create($driver_details);

            $output = ['success' => 1,
                        'msg' => __("driver.driver_added")
                    ];
        } catch (\Exception $e) {
            dd($e->getMessage());
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                        'msg' => __("messages.something_went_wrong")
                    ];
        }

        return redirect('driver')->with('status', $output);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('driver.update')) {
            abort(403, 'Unauthorized action.');
        }

        $driver = Driver::findOrFail($id);
        $kitchens = KitchenLocation::pluck('name', 'id');

        if ($driver->status == AppConstant::STATUS_ACTIVE) {
            $is_checked_checkbox = true;
        } else {
            $is_checked_checkbox = false;
        }

        return view('driver.edit')
                ->with(compact('driver', 'is_checked_checkbox','kitchens'));
    }

    /**
     * Show the form for editing the all resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function editAll()
    {
        if (!auth()->user()->can('driver.update')) {
            abort(403, 'Unauthorized action.');
        }
        $filter_name = Session::get('filter_name');
        $filter_start_date = Session::get('filter_start_date');
        $filter_end_date = Session::get('filter_end_date');

        Log::info($filter_start_date);
        Log::info($filter_end_date);
        Log::info($filter_name);
        $driver = DriverAttendance::with('driver');

        if (!empty(\request()->select_date) && !empty(\request()->select_date)) {
            $driver->whereDate('attendance_date', '>=', \request()->select_date)
                    ->whereDate('attendance_date', '<=', \request()->select_date);
        }
        $drivers = $driver->get();
        /*switch ($val){
            case AppConstant::YESTERDAY:
                $drivers = DriverAttendance::with('driver')->where('attendance_date', '=', Carbon::yesterday()->format('Y-m-d'))->get();

                break;
            case AppConstant::ALL:
                $drivers = DriverAttendance::with('driver')->get();
                break;
            Default:
                break;
        }*/
        return view('driver.partials.edit_all')
            ->with(compact('drivers', ));
    }

    public function show(){

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
        if (!auth()->user()->can('driver.update')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required',
            'email' => 'required|unique:drivers,email,'.$id,
            'phone' => 'required|unique:drivers,phone,'.$id,
            'address_line_1' => 'required',
            'city' => 'required',
            'state' => 'required',
            'country' => 'required',
            'driver_type' => 'required',
            'kitchen_location_id' => 'required'
        ]);

        try {
            $driver_details = $request->only(['name', 'email','phone','address_line_1','address_line_2','city','state','country','is_active','driver_type','kitchen_location_id']);
            $driver_details['status'] = !empty($request->input('is_active')) ? $request->input('is_active') : AppConstant::STATUS_INACTIVE;
            $driver = driver::findOrFail($id);
            $driver->update($driver_details);
            $this->moduleUtil->activityLog($driver, 'edited', null, ['name' => $driver->name]);
            $output = ['success' => 1,'msg' => __("driver.driver_update_success")];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            $output = ['success' => 0,'msg' => $e->getMessage()];
        }
        return redirect('driver')->with('status', $output);
    }
    /**
     * Update all the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateAll(Request $request)
    {

        if (!auth()->user()->can('driver.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $drivers = $request->drivers;
            foreach($drivers as $key => $driver){
                $driver_attendance = DriverAttendance::findOrFail($driver['id']);
                $driver_attendance->is_half_day = (array_key_exists('is_half_day',$driver))?$driver['is_half_day']:AppConstant::HALF_DAY_NO;
                $driver_attendance->in_or_out = (array_key_exists('in_or_out',$driver))?$driver['in_or_out']:AppConstant::ATTENDANCE_OUT;
                $driver_attendance->leave_reason = (array_key_exists('leave_reason',$driver))?$driver['leave_reason']:AppConstant::STATUS_INACTIVE;
                $driver_attendance->leave_reason_description = (array_key_exists('leave_reason_description',$driver))?$driver['leave_reason_description']:'';
                $driver_attendance->save();

                $this->moduleUtil->activityLog($driver_attendance, 'edited', null, ['name' => $driver['name']]);
            }
            $output = ['success' => 1,'msg' => __("driver.driver_update_success")];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            $output = ['success' => 0,'msg' => $e->getMessage()];
        }
        return redirect('driver')->with('status', $output);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('driver.delete')) {
            abort(403, 'Unauthorized action.');
        }
        if (request()->ajax()) {
            try {
                $driver = Driver::findOrFail($id);
                $this->moduleUtil->activityLog($driver, 'deleted', null, ['name' => $driver->name, 'id' => $driver->id]);
                $driver->delete();
                $output = ['success' => true,'msg' => __("driver.driver_delete_success")];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
                $output = ['success' => false,'msg' => __("messages.something_went_wrong")];
            }
            return $output;
        }
    }

    /**
     * Handles the check phone number already registered
     *
     * @return \Illuminate\Http\Response
    */

    public function checkMobile(Request $request)
    {
        $mobile_number = $request->input('phone');
        $query = Driver::where('phone', 'like', "%{$mobile_number}");
        if (!empty($request->input('driver_id'))) {
            $driver_id = $request->input('driver_id');
            $query->where('id', '!=', $driver_id);
        }
        $driver = $query->pluck('name')->toArray();
        if ($driver) {
            return response()->json(['is_mobile_exists' => !empty($driver),'msg' => __('lang_v1.driver_mobile_already_registered', ['driver' => implode(', ', $driver), 'mobile' => $mobile_number])]);
        } else {
            return response()->json('true');
        }
    }

    /**
     * Handles the validation email
     *
     * @return \Illuminate\Http\Response
     */
    public function checkEmail(Request $request)
    {
        $email = $request->input('email');
        $query = Driver::where('email', $email);
        if (!empty($request->input('driver_id'))) {
            $driver_id = $request->input('driver_id');
            $query->where('id', '!=', $driver_id);
        }
        $exists = $query->exists();
        if (!$exists) {
            echo "true";
            exit;
        } else {
            echo "false";
            exit;
        }
    }

}
