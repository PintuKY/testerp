<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
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
        if (!auth()->user()->can('user.view') && !auth()->user()->can('user.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $driver = Driver::all();

            return DataTables::of($driver)
                ->addColumn(
                    'action',
                    '@can("user.update")
                        <a href="{{action(\'ManageUserController@edit\', [$id])}}" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</a>
                        &nbsp;
                    @endcan
                    @can("user.view")
                    <a href="{{action(\'ManageUserController@show\', [$id])}}" class="btn btn-xs btn-info"><i class="fa fa-eye"></i> @lang("messages.view")</a>
                    &nbsp;
                    @endcan
                    @can("user.delete")
                        <button data-href="{{action(\'ManageUserController@destroy\', [$id])}}" class="btn btn-xs btn-danger delete_user_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>
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
        
        if (!auth()->user()->can('user.create')) {
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
        if (!auth()->user()->can('user.create')) {
            abort(403, 'Unauthorized action.');
        }
        dd('demo');
        try {

            if (!empty($request->input('dob'))) {
                $request['dob'] = $this->moduleUtil->uf_date($request->input('dob'));
            }

            $request['cmmsn_percent'] = !empty($request->input('cmmsn_percent')) ? $this->moduleUtil->num_uf($request->input('cmmsn_percent')) : 0;

            $request['max_sales_discount_percent'] = !is_null($request->input('max_sales_discount_percent')) ? $this->moduleUtil->num_uf($request->input('max_sales_discount_percent')) : null;

            $user = $this->moduleUtil->createUser($request);

            $output = ['success' => 1,
                        'msg' => __("user.user_added")
                    ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                        'msg' => __("messages.something_went_wrong")
                    ];
        }

        return redirect('users')->with('status', $output);
    }
}
