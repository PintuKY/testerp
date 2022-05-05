<?php

namespace App\Http\Controllers;;
use Illuminate\Http\Request;
use App\Models\KitchenLocation;
use App\Models\BusinessLocation;
use Datatables;


class KitchenLocationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {

        if(request()->ajax()){
            $kitchens = KitchenLocation::all();


                return Datatables::of($kitchens)

                ->addColumn(
                    'action',
                    '@can("kitchen_location.update")
                    <button data-href="{{action(\'KitchenLocationController@edit\', [$id])}}" class="btn btn-xs btn-primary edit_kitchen_location_button"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                        &nbsp;
                    @endcan
                    @can("kitchen_location.delete")
                        <button data-href="{{action(\'KitchenLocationController@destroy\', [$id])}}" class="btn btn-xs btn-danger delete_kitchen_location_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>
                    @endcan'
                )



               ->removeColumn('id')
               ->removeColumn('is_active')
               ->rawColumns([2])
               ->rawColumns(['action'])
               ->make(true);
        }

        return view('kitchen_location.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('invoice_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        return view('kitchen_location.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        if (!auth()->user()->can('invoice_settings.access')) {
            abort(403, 'Unauthorized action.');
        }
        $request->validate([
            'name' => 'required',
            'landmark'=>'required',
            'email' => 'required',
            'mobile' => 'required',
            'city' => 'required',
            'state' => 'required',
            'country' => 'required',
            'zip_code' => 'required',
        ]);
        try {
            $input = $request->only(['name','landmark','country','state','city','zip_code','mobile','alternate_number','email']);
            $business_id = $request->session()->get('user.business_id');
            $input['business_id'] = $business_id;

            if (!empty($request->input('is_default'))) {
                //get_default
                $kitchens = KitchenLocation::find($id)
                                ->where('is_default', 1)
                                ->update(['is_default' => 0 ]);
                $input['is_default'] = 1;
            }
            KitchenLocation::create($input);
            $output = ['success' => true,
                            'msg' => __("kitchen.added_success")
                        ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => false,
                            'msg' => __("kitchen.something_went_wrong")
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
        if (!auth()->user()->can('invoice_settings.access')) {
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

        $business_id = request()->session()->get('user.business_id');
        $kitchens = KitchenLocation::find($id);
        return view('kitchen_location.edit')->with(compact('kitchens'));
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

        $business_id = request()->session()->get('user.business_id');
        if (!auth()->user()->can('kitchen.access')) {
            abort(403, 'Unauthorized action.');
        }
        $request->validate([
            'name' => 'required',
            'landmark'=>'required',
            'email' => 'required',
            'mobile' => 'required',
            'city' => 'required',
            'state' => 'required',
            'country' => 'required',
            'zip_code' => 'required',
        ]);

        if (request()->ajax()) {
            try {
                $input = $request->only(['name','landmark','country','state','city','zip_code','mobile','alternate_number','email']);

                $kitchens = KitchenLocation::find($id);

                $kitchens->update($input);

                $output = ['success' => true,
                            'msg' => __("kitchen.updated_success")
                            ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output = ['success' => false,
                            'msg' => __("kitchen.something_went_wrong")
                        ];
            }

            return $output;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('brand.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->user()->business_id;

                $brand =KitchenLocation::find($id);
                $brand->delete();

                $output = ['success' => true,
                            'msg' => __("kitchen.deleted_success")
                            ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output = ['success' => false,
                            'msg' => __("kitchen.something_went_wrong")
                        ];
            }

            return $output;
        }
    }


}
