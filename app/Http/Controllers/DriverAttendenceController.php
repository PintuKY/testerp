<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\DriverAttendance;
use App\Utils\AppConstant;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class DriverAttendenceController extends Controller
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
        $default_date = $this->businessUtil->format_dates(Carbon::parse(now())->format('Y-m-d'));
        $driver = DriverAttendance::with('driver');

        if (request()->ajax()) {
            /*if (!empty(\request()->select_date) && !empty(\request()->select_date)) {
                $driver->whereDate('attendance_date', '=', \request()->select_date);
            }*/
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end = request()->end_date;
                $driver->whereDate('attendance_date', '>=', $start)
                        ->whereDate('attendance_date', '<=', $end);

            }
           // $drivers = $driver->get();
            return DataTables::of($driver)
                ->addColumn(
                    'action',
                    '@can("driver.update")
                        <a href="{{action(\'DriverAttendenceController@edit\', [$id])}}" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</a>
                        &nbsp;
                    @endcan
                    @can("driver.delete")
                        <button data-href="{{action(\'DriverAttendenceController@destroy\', [$id])}}" class="btn btn-xs btn-danger delete_driver_attendence_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>
                    @endcan'
                )->editColumn('is_half_day',function ($row){
                    if($row->is_half_day == \App\Utils\AppConstant::HALF_DAY_YES){
                        $half_day = 'Yes';
                    }else{
                        $half_day = 'No';
                    }
                    return $half_day;
                })
                ->editColumn('in_or_out',function ($row){
                    if($row->in_or_out == \App\Utils\AppConstant::ATTENDANCE_IN){
                        $in_or_out = 'In';
                    }else{
                        $in_or_out = 'Out';
                    }
                    return $in_or_out;
                })->editColumn('name',function ($row){
                    return $row->driver->name;
                })
                ->editColumn('email',function ($row){
                    return $row->driver->email;
                })->editColumn('leave_reason',function ($row){
                    return getLeaveReasonType($row->leave_reason);
                })->editColumn('attendance_date',function ($row){
                    return $row->attendance_date;
                })->editColumn('leave_reason_description',function ($row){
                    return $row->leave_reason_description;
                })
                ->make(true);

        }
        return view('driver.partials.driver_attendence')
            ->with(compact('default_date', ));
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
        $driver = Driver::active()->pluck('name','id')->toArray();
        $default_date = $this->businessUtil->format_dates(Carbon::parse(now())->format('Y-m-d'));

        return view('driver.attendence_create')->with(compact('driver', 'default_date'));
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
            'attendence_date' => 'required',
        ]);
        try {
            $driver_details = $request->only(['name']);
            $driver_details['driver_id'] = !empty($request->input('name')) ? $request->input('name') : '';
            $driver_details['attendance_date'] = !empty($request->input('attendence_date')) ? $request->input('attendence_date') : Carbon::parse(now())->format('Y-m-d');
            $driver_details['leave_reason'] = !empty($request->input('leave_reason')) ? $request->input('leave_reason') : '';
            $driver_details['in_or_out'] = !empty($request->input('in_or_out')) ? $request->input('in_or_out') : '';
            $driver_details['is_half_day'] = !empty($request->input('is_half_day')) ? $request->input('is_half_day') : '';
            $driver_details['leave_reason_description'] = !empty($request->input('leave_reason_description')) ? $request->input('leave_reason_description') : '';
            $driver = DriverAttendance::create($driver_details);

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

        return redirect('driver/attendence')->with('status', $output);
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

        $driver = DriverAttendance::with('driver')->where('id',$id)->first();

        return view('driver.attendence_edit')
                ->with(compact('driver'));
    }

    /**
     * Show the form for editing the all resource.
     *
     * @return \Illuminate\Http\Response
     */

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

        try {

            $driver = DriverAttendance::where('id',$id)->update(
               [
                   "attendance_date" => ($request->attendence_date) ? $request->attendence_date : '',
                   "leave_reason" => $request->leave_reason,
                   "in_or_out" => ($request->in_or_out) ? $request->in_or_out : '0',
                   "is_half_day" => ($request->is_half_day) ? $request->is_half_day : '0',
                   "leave_reason_description" => $request->leave_reason_description,
               ]
            );

            $output = ['success' => 1,'msg' => __("driver.driver_update_success")];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            $output = ['success' => 0,'msg' => $e->getMessage()];
        }
        return redirect('driver/attendence')->with('status', $output);
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
                $driver = DriverAttendance::findOrFail($id);
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
