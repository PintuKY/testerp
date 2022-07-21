<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SupplierProduct;
use Illuminate\Support\Facades\DB;
use App\Models\SupplierProductUnit;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;


class SupplierProductUnitController extends Controller
{
    public function index(Type $var = null)
    { 
        if (request()->ajax()) {
        $business_id = request()->session()->get('user.business_id');

        $unit = SupplierProductUnit::where('business_id', $business_id)
                    ->with(['base_unit'])
                    ->select(['name', 'short_name', 'id',
                        'base_unit_id', 'base_unit_multiplier']);

        return Datatables::of($unit)
            ->addColumn(
                'action', 
                '@can("unit.update")
                <button data-href="{{action(\'SupplierProductUnitController@edit\', [$id])}}" class="btn btn-xs btn-primary edit_supplier_product_unit_button"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                    &nbsp;
                @endcan
                @can("unit.delete")
                    <button data-href="{{action(\'SupplierProductUnitController@destroy\', [$id])}}" class="btn btn-xs btn-danger delete_supplier_product_unit_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>
                @endcan'
            )
            ->editColumn('name', function ($row) {
                if (!empty($row->base_unit_id)) {
                    return  $row->name . ' (' . (float)$row->base_unit_multiplier . $row->base_unit->short_name . ')';
                }
                return  $row->name;
            })
            ->removeColumn('id')
            ->rawColumns(['action'])
            ->make(true);
    }

    return view('supplier-product-unit.index');
    }
    public function create()
    {
        Log::info('in unit controller');
        if (!auth()->user()->can('unit.create')) {
            abort(403, 'Unauthorized action.');
        }
        $quick_add = false;
        if (!empty(request()->input('quick_add'))) {
            $quick_add = true;
        }
        $units = DB::table('supplier_product_units')->pluck('name','id');
        return view('supplier-product-unit.create')
                ->with(compact('units','quick_add'));
    }
    public function store(Request $request)
    {
        if (!auth()->user()->can('unit.create')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $input = $request->only(['name', 'short_name']);
            if ($request->has('define_base_unit')) {
                if (!empty($request->input('base_unit_id')) && !empty($request->input('base_unit_multiplier'))) {
                    if ($request->input('base_unit_multiplier') != 0) {
                        $input['base_unit_id']         = $request->input('base_unit_id');
                        $input['base_unit_multiplier'] = $request->input('base_unit_multiplier');
                    }
                }
            }
            $input['business_id'] =  $request->session()->get('user.business_id');
            $unit = SupplierProductUnit::create($input);
            $output = ['success' => true,
                    'data' => $unit,
                    'msg' => __("unit.added_success")
                ];
        }catch (\Exception $e) {
          \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

          $output = ['success' => false,
                      'msg' => __("messages.something_went_wrong")
                  ];
      }
      return $output;
    }
    public function edit($id)
    {
        if (!auth()->user()->can('unit.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $unit = SupplierProductUnit::where('business_id', $business_id)->find($id);

            $units = SupplierProductUnit::where('business_id', $business_id)->pluck('name','id');
            return view('supplier-product-unit.edit')
                ->with(compact('unit', 'units'));
        }
    }
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('unit.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $input       = $request->only(['name', 'short_name']);
                $business_id = $request->session()->get('user.business_id');

                $unit              = SupplierProductUnit::where('business_id', $business_id)->findOrFail($id);
                $unit->name        = $input['name'];
                $unit->short_name  = $input['short_name'];
                Log::info($request);
                if ($request->has('define_base_unit')) {
                    if (!empty($request->input('base_unit_id')) && !empty($request->input('base_unit_multiplier'))) {
                        if ($request->input('base_unit_multiplier') != 0) {
                            $unit->base_unit_id          = $request->input('base_unit_id');
                            $unit->base_unit_multiplier  = $request->input('base_unit_multiplier');
                        }
                    }
                }
                $unit->save();

                $output = ['success' => true,
                            'msg' => __("unit.updated_success")
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
        if (!auth()->user()->can('unit.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->user()->business_id;

                $unit = SupplierProductUnit::where('business_id', $business_id)->findOrFail($id);

                //check if any product associated with the unit
                $exists = SupplierProduct::where('unit_id', $unit->id)
                                ->exists();
                if (!$exists) {
                    $unit->delete();
                    $output = ['success' => true,
                            'msg' => __("unit.deleted_success")
                            ];
                } else {
                    $output = ['success' => false,
                            'msg' => __("lang_v1.unit_cannot_be_deleted")
                            ];
                }
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output = ['success' => false,
                            'msg' => '__("messages.something_went_wrong")'
                        ];
            }

            return $output;
        }
    }
}
