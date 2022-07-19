<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SupplierProduct;
use Illuminate\Support\Facades\DB;
use App\Models\SupplierProductCategory;
use Yajra\DataTables\Facades\DataTables;


class SupplierProductCategoryController extends Controller
{
    public function index()
    {
        if (!auth()->user()->can('category.view') && !auth()->user()->can('category.create')) {
            abort(403, 'Unauthorized action.');
        }
        
        if (request()->ajax()) {
            $can_edit = true;
            if(!auth()->user()->can('category.update')) {
                $can_edit = false;
            }
            
            $can_delete = true;
            if(!auth()->user()->can('category.update')) {
                $can_delete = false;
            }
            
            $business_id = request()->session()->get('user.business_id');

            $category = SupplierProductCategory::where('business_id', $business_id)
                            ->select(['name', 'description', 'id']);

            return Datatables::of($category)
                ->addColumn(
                    'action', function ($row) use ($can_edit, $can_delete)
                    {
                        $html = '';
                        if ($can_edit) {
                            $html .= '<button data-href="' . action('SupplierProductCategoryController@edit', [$row->id]) . '" class="btn btn-xs btn-primary edit_supplier_product_category_button"><i class="glyphicon glyphicon-edit"></i>' . __("messages.edit") . '</button>';
                        }

                        if ($can_delete) {
                            $html .= '&nbsp;<button data-href="' . action('SupplierProductCategoryController@destroy', [$row->id]) . '" class="btn btn-xs btn-danger delete_supplier_product_category_button"><i class="glyphicon glyphicon-trash"></i> ' . __("messages.delete") . '</button>';
                        }

                        return $html;
                    }
                )
                ->editColumn('name', function ($row) {
                    return $row->name;
                })
                ->removeColumn('id')
                ->rawColumns(['action'])
                ->make(true);
        }


        return view('supplier-product-category.index');
    }
    public function create(Type $var = null)
    {
        $categories = DB::table('supplier_product_categories')->pluck('name','id');
        $quick_add = false;
        if (!empty(request()->input('quick_add'))) {
            $quick_add = true;
        }
        return view('supplier-product-category.create',compact('categories','quick_add'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->can('category.create')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $input    = $request->only(['name', 'description']);
            $input['business_id'] =  $request->session()->get('user.business_id');
            $category = SupplierProductCategory::create($input);
            $output = ['success' => true,
            'data' => $category,
            'msg' => __("category.added_success")
            ];
        } catch (Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
  
            $output = ['success' => false,
                        'msg' => __("messages.something_went_wrong")
                      ];
        }
        return $output;
    }

    public function edit($id) {
        if (!auth()->user()->can('category.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $category = SupplierProductCategory::where('business_id', $business_id)->find($id);

            return view('supplier-product-category.edit')
                ->with(compact('category'));
        }
        
    }
    public function update(Request $request,$id) {
        if (!auth()->user()->can('unit.update')) {
            abort(403, 'Unauthorized action.');
        }
        if (request()->ajax()) {
            try {
                $input       = $request->only(['name', 'description']);
                $business_id = $request->session()->get('user.business_id');

                $category               = SupplierProductCategory::where('business_id', $business_id)->findOrFail($id);
                $category->name         = $input['name'];
                $category->description  = $input['description'];
                $category->save();
                $output = ['success' => true,
                            'msg' => __("category.updated_success")
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

                $category = SupplierProductCategory::where('business_id', $business_id)->findOrFail($id);

                //check if any product associated with the unit
                $exists = SupplierProduct::where('unit_id', $category->id)
                                ->exists();
                if (!$exists) {
                    $category->delete();
                    $output = ['success' => true,
                            'msg' => __("category.deleted_success")
                            ];
                } else {
                    $output = ['success' => false,
                            'msg' => 'Cannot delete Category'
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
