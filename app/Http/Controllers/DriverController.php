<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class DriverController extends Controller
{
    
     /**
     * Constructor
     *
     * @param Util $commonUtil
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
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

        if (request()->ajax()) {
            $driver = Driver::active()->get();

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
                )
                // ->filterColumn('name', function ($query, $keyword) {
                //     $query->where('name',, $keyword]);
                // })
                ->make(true);
        }

        return view('driver.index');
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
        return view('driver.create');
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
        ]);

        try {

            $driver_details = $request->only(['name', 'email','phone','address_line_1','address_line_2','city','state','country','is_active']);
            $driver_details['status'] = !empty($request->input('is_active')) ? $request->input('is_active') : 'inactive';
            $driver = Driver::create($driver_details);
           
            $output = ['success' => 1,
                        'msg' => __("driver.driver_added")
                    ];
        } catch (\Exception $e) {
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

        if ($driver->status == 'active') {
            $is_checked_checkbox = true;
        } else {
            $is_checked_checkbox = false;
        }

        return view('driver.edit')
                ->with(compact('driver', 'is_checked_checkbox'));
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
        ]);

        try {
            $driver_details = $request->only(['name', 'email','phone','address_line_1','address_line_2','city','state','country','is_active']);
            $driver_details['status'] = !empty($request->input('is_active')) ? $request->input('is_active') : 'inactive';
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
