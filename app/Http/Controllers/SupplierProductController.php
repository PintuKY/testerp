<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Media;
use App\Models\TaxRate;
use App\Models\Supplier;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use Illuminate\Http\Request;
use App\Models\SupplierProduct;
use Illuminate\Support\Facades\DB;
use App\Models\SupplierProductUnit;
use Illuminate\Support\Facades\Log;
use App\Models\SupplierProductCategory;
use Yajra\DataTables\Facades\DataTables;


class SupplierProductController extends Controller
{
    public function __construct(ProductUtil $productUtil, ModuleUtil $moduleUtil)
    {
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;

        //barcode types
        $this->barcode_types = $this->productUtil->barcode_types();
    }

    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            $supplier_products = DB::table('supplier_products')
            ->leftjoin(
                'supplier',
                'supplier.id',
                '=',
                'supplier_products.supplier_id'
            )
            ->leftjoin(
                'supplier_product_categories',
                'supplier_product_categories.id',
                '=',
                'supplier_products.category_id'
            )
            ->leftjoin(
                'supplier_product_units',
                'supplier_product_units.id',
                '=',
                'supplier_products.unit_id'
            )
            ->select(
                'supplier_products.id as id',
                'supplier_products.name as name',
                'supplier_products.sku as sku',
                'supplier_products.purchase_price as price',
                'supplier_product_categories.name as category',
                'supplier_product_units.name as unit',
                'supplier.name as supplier',
            )->where('supplier_products.deleted_at','=',null);

            $category_id = request()->get('category_id', null);
            if (!empty($category_id)) {
                $supplier_products->where('supplier_products.category_id', $category_id);
            }

            $supplier_id = request()->get('supplier_id', null);
            if (!empty($supplier_id)) {
                $supplier_products->where('supplier_products.supplier_id', $supplier_id);
            }
            
            return Datatables::of($supplier_products)
            ->addColumn(
                'action',
                function ($row) {
                    $html =
                    '<div class="btn-group"><button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">'. __("messages.actions") . '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button><ul class="dropdown-menu dropdown-menu-left" role="menu">';

                    if (auth()->user()->can('product.view')) {
                        $html .=
                        '<li><a href="' . action('SupplierProductController@show', [$row->id]) . '" class="view-supplier-product"><i class="fa fa-eye"></i> ' . __("messages.view") . '</a></li>';
                    }
                    if (auth()->user()->can('product.update')) {
                        $html .=
                        '<li><a href="' . action('SupplierProductController@edit', [$row->id]) . '"><i class="glyphicon glyphicon-edit supplier-product-edit"></i> ' . __("messages.edit") . '</a></li>';
                    }
                    if (auth()->user()->can('product.delete')) {
                        $html .=
                        '<li><a href="' . action('SupplierProductController@destroy', [$row->id]) . '" class="delete-supplier-product"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                    }
                    $html .= '</ul></div>';
                    return $html;
                }
            )->make(true);
        }
        $categories  = DB::table('supplier_product_categories')->where('business_id',$business_id)->pluck('name','id');
        $suppliers   = DB::table('supplier')->where('business_id',$business_id)->pluck('name','id');

     return view('supplier-product.index',compact('categories','suppliers'));
    }

    public function show($supplier_product_id)
    {
        if (!auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }
        $supplier_product = SupplierProduct::with('supplier','unit','category')
        ->where('id',$supplier_product_id)->first();
        return view('supplier-product.show',compact('supplier_product'));
    }
    public function create()
    {
       $business_id  =  request()->session()->get('user.business_id');
       $units        = DB::table('supplier_product_units')->where('business_id',$business_id)->pluck('name','id');
       $categories   = DB::table('supplier_product_categories')->where('business_id',$business_id)->pluck('name','id');
       $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);
       $taxes          = $tax_dropdown['tax_rates'];
       $tax_attributes = $tax_dropdown['attributes'];
       $default_profit_percent = request()->session()->get('business.default_profit_percent');;

       return view('supplier-product.create',compact('units','categories','taxes','tax_attributes','default_profit_percent'));
    }
    public function edit($supplier_product_id)
    {
        $suppliers   = Supplier::where('business_id',request()->session()->get('user.business_id'))->pluck('name','id');
        $business_id =  request()->session()->get('user.business_id');
        $units       = DB::table('supplier_product_units')->where('business_id',$business_id)->pluck('name','id');
        $categories  = DB::table('supplier_product_categories')->where('business_id',$business_id)->pluck('name','id');
        $supplier_product = SupplierProduct::find($supplier_product_id);
      return view('supplier-product.edit',compact('supplier_product','units','categories','suppliers'));

    }
    public function store(Request $request) {
        try {
        $data = $request->validate([
            'name'                   => 'required',
            'purchase_price'         => 'required|numeric',
            'category_id'            => 'required',
            'unit_id'                => 'required',
            'description'            => 'required',
            'weight'                 => 'sometimes',
            'purchase_price_inc_tax' => 'required|numeric',
            'tax'                    => 'sometimes',
            'weight'                 => 'sometimes',
            'alert_quantity'         => 'sometimes',
        ]);
        
        $data['business_id'] =  $request->session()->get('user.business_id');
        DB::beginTransaction();
        $data['image']       = $this->productUtil->uploadFile($request, 'supplier_product_image', config('constants.product_img_path'), 'image');
        $supplier_product    = SupplierProduct::create($data);
        
        if (empty(trim($request->input('sku')))) {
            $sku = $this->productUtil->generateProductSku($supplier_product->id);
            $supplier_product->sku = $sku;
            $supplier_product->save();
        }
        Media::uploadMedia($supplier_product->business_id, $supplier_product, $request, 'product_brochure', true);
        DB::commit();

        $output = ['success' => true,
       'msg' => 'Product Added Successfully'];
        }catch (\Exception $e) {
            DB::rollback();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            $output = ['success' => false,
                        'msg' => __("messages.something_went_wrong")];
        }
       return redirect('/supplier-products')->with('status',$output);
    }

    public function update(Request $request,$supplier_product_id)
    {
     try {
      $data = $request->validate([
        'name'          =>'required',
        'supplier_id'   =>'required',
        'purchase_price'=>'required|numeric',
        'category_id'   =>'required',
        'unit_id'       =>'required',
        'description'   =>'required'
      ]);
       $supplier_product = SupplierProduct::find($supplier_product_id);
       $supplier_product->update($data);
       $output = ['success' => true,
       'msg' => 'Product Updated Successfully'];
        }catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            $output = ['success' => false,
                        'msg' => __("messages.something_went_wrong")];
        }
       return redirect('/supplier-products')->with('status',$output);
    }
    public function unitCreate()
    {
        if (!auth()->user()->can('unit.create')) {
            abort(403, 'Unauthorized action.');
        }
        $units = DB::table('supplier_product_units')->pluck('name','id');
        return view('supplier-product.unit_create')
                ->with(compact('units'));
    }
    public function unitStore(Request $request)
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
    public function categoryCreate(Type $var = null)
    {
       $categories = DB::table('supplier_product_categories')->pluck('name','id');
       return view('supplier-product.category_create',compact('categories'));
    }
    public function categoryStore(Request $request)
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
    public function destroy($supplier_product_id) {
        if(request()->ajax()) {
            try {
                $supplier_product = SupplierProduct::find($supplier_product_id);
                $supplier_product->delete();
                $output = ['success' => true,
                'msg' => __("lang_v1.product_delete_success")];
            }catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
    
                $output = ['success' => false,
                           'msg' => __("messages.something_went_wrong")
                           ];
            }
            return $output;
        }
    }
}
