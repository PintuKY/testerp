<?php

namespace App\Http\Controllers;

use App\Models\ApiSetting;
use App\Models\BusinessLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;


class ApiController extends Controller
{
    public function index()
    {
        $apisetting = ApiSetting::with('businesslocation')->get();

        if (request()->ajax()) {
            $apisetting = ApiSetting::all();

            return Datatables::of($apisetting)
                ->addColumn('business_locations_id',function($raw){
                        return $raw->businesslocation->name ?? '';
                    })
                ->addColumn(
                    'action',
                    '@can("")
                    <button data-href="{{action(\'ApiController@index\')}}" class="btn btn-xs btn-info "> @lang("messages.sync")</button>
                        &nbsp;
                    @endcan
                    @can("api.update")
                    <button data-href="{{action(\'ApiController@edit\', [$id])}}" class="btn btn-xs btn-primary edit_api_button"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                        &nbsp;
                    @endcan
                    @can("api.delete")
                        <button data-href="{{action(\'ApiController@destroy\', [$id])}}" class="btn btn-xs btn-danger delete_api_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>
                    @endcan'
                )
                ->editColumn('checkbox', static function () {
                    return '<input type="checkbox" name="checkbox" value=""/>';
                })
                ->rawColumns(['checkbox'])
                ->rawColumns(['action'])
                ->removeColumn('id')
                ->make(true);
        }

        return view('api_setting.index',compact('apisetting'));
    }

    public function create(){
        if (!auth()->user()->can('api.create')) {
            abort(403, 'Unauthorized action.');
        }
        $apisetting = ApiSetting::all();
        $businesslocation = BusinessLocation::pluck('name','id');
        return view('api_setting.create',compact('apisetting','businesslocation'));

    }

    public function store(Request $request)
   {
         $validator =$request->validate(
            [
                'consumer_key' => 'required',
                'consumer_secret' => 'required',
                'url' => 'required|url',
                'business_locations_id' => 'required',
                'status' => 'required',

            ]);


            try {
                $input = $request->only([ 'consumer_key', 'consumer_secret', 'url', 'business_locations_id', 'status']);

                $apisetting = ApiSetting::create($input);

                $output = ['success' => true,
                            'data' => $apisetting,
                            'msg' => __("lang_v1.added_success")
                        ];

            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output = ['success' => false,
                                'msg' => __("messages.something_went_wrong")
                            ];
            }

            return $output;

   }

    public function edit($id)
    {
        if (!auth()->user()->can('api.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $apisetting = ApiSetting::find($id);
            $businesslocation = BusinessLocation::pluck('name','id');

            return view('api_setting.edit')
                ->with(compact('apisetting','businesslocation'));
        }

    }

    public function update(Request $request, $id)
        {
            $validator = $request->validate(
                [
                    'consumer_key' => 'required',
                    'consumer_secret' => 'required',
                    'url' => 'required|url',
                    'business_locations_id' => 'required',
                    'status' => 'required',

                ]);

            if (request()->ajax()) {
                try {
                    $input = $request->only([ 'consumer_key', 'consumer_secret', 'url', 'business_locations_id', 'status']);

                    $apisetting = ApiSetting::find($id);

                    $apisetting->update($input);

                    $output = ['success' => true,
                                'data' => $apisetting,
                                'msg' => __("lang_v1.updated_success")
                                ];
                } catch (\Exception $e) {
                    \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                    $output = ['success' => false,
                                'msg' => __("messages.something_went_wrong")
                            ];
                }

                return $output;
            }
        }


    public function destroy($id)
    {
        if (!auth()->user()->can('api.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {

                $apisetting = Apisetting::find($id);
                $apisetting->delete();

                $output = ['success' => true,
                            'msg' => __("api-setting.deleted_success")
                            ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output = ['success' => false,
                            'msg' => __("messages.something_went_wrong")
                        ];
            }

            return $output;
        }
    }

}

