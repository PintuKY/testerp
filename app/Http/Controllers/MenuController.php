<?php

namespace App\Http\Controllers;

use App\Models\BusinessLocation;
use App\Models\Category;
use App\Models\Driver;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Recipe;
use App\Models\SellingPriceGroup;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class MenuController extends Controller
{

    /**
     * All Utils instance.
     *
     */
    protected $commonUtil;

    /**
     * Constructor
     *
     * @param
     * @return void
     */
    public function __construct(Util $commonUtil)
    {
        $this->commonUtil = $commonUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // if (!auth()->user()->can('product.create')) {
        //     abort(403, 'Unauthorized action.');
        // }
        $menu = Menu::active()->with(['location','recipe','category'])->get();
        $business_id = request()->session()->get('user.business_id');
        //Get all business locations
        $business_locations = BusinessLocation::forDropdown($business_id);
        $categories = Category::forDropdown($business_id, 'product');
        $recipe = Recipe::active()->pluck('name', 'id')->toArray();
        $menu_name_list = Menu::pluck('name','id')->toArray();
        if (request()->ajax()) {
            $menu = Menu::active()->with(['location','recipe','category'])->select(['id', 'name','business_location_id','category_id','recipe_id']);
            if (!empty(request()->menu_list_filter_name)) {
                $menu->where('id', request()->menu_list_filter_name);
            }
            if (!empty(request()->menu_list_location)) {
                $menu->where('business_location_id', request()->menu_list_location);
            }
            if (!empty(request()->menu_list_category)) {
                $menu->where('category_id', request()->menu_list_category);
            }
            if (!empty(request()->menu_list_recipe)) {
                $menu->where('recipe_id', request()->menu_list_recipe);
            }
            return Datatables::of($menu)
                ->addColumn(
                    'action',
                    '<a class="btn btn-xs btn-primary" href="{{action(\'MenuController@edit\', [$id])}}"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</a>

                        <button data-href="{{action(\'MenuController@destroy\', [$id])}}" class="btn btn-xs btn-danger delete_spg_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>'
                )->editColumn('business_location_id', function ($row){
                    $location_id = $row->location->name;
                    return $location_id;
                 })
                ->editColumn('category_id', function ($row){
                    $category_id = $row->category->name;
                    return $category_id;
                })
                ->editColumn('recipe_id', function ($row){
                    $recipe_id = $row->recipe->name;
                    return $recipe_id;
                })
                ->removeColumn('status')
                ->make(true);
        }
        return view('menu.index')
            ->with(compact('menu_name_list','menu', 'recipe', 'categories', 'business_locations'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        //Get all business locations
        $business_locations = BusinessLocation::forDropdown($business_id);
        $categories = Category::forDropdown($business_id, 'product');
        $recipe = Recipe::active()->pluck('name', 'id')->toArray();
        return view('menu.create')->with(compact('recipe', 'categories', 'business_locations'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required',
            'business_location_id' => 'required',
            'category_id' => 'required',
            'recipe_id' => 'required',
        ]);
        try {
            $input = $request->only(['name', 'business_location_id', 'category_id', 'recipe_id']);
            $input['created_at'] = Carbon::now();
            $input['updated_at'] = Carbon::now();
            Menu::insert($input);

            $output = ['success' => true,
                'data' => '',
                'msg' => __("lang_v1.added_success")
            ];
        } catch (\Exception $e) {
            dd($e->getMessage());
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }
        return redirect()
            ->action('MenuController@index')
            ->with('status', $output);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\SellingPriceGroup $sellingPriceGroup
     * @return \Illuminate\Http\Response
     */
    public function show(SellingPriceGroup $sellingPriceGroup)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\SellingPriceGroup $sellingPriceGroup
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }
        $menu = Menu::with(['location','recipe','category'])->where('id', $id)->first();
        $business_id = request()->session()->get('user.business_id');
        //Get all business locations
        $business_locations = BusinessLocation::forDropdown($business_id);
        $categories = Category::forDropdown($business_id, 'product');
        $recipe = Recipe::active()->pluck('name', 'id')->toArray();
        return view('menu.edit')
            ->with(compact('menu', 'recipe', 'categories', 'business_locations'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\SellingPriceGroup $sellingPriceGroup
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        if (!auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $input = $request->only(['name', 'business_location_id', 'category_id', 'recipe_id']);

            Menu::where('id', $id)->update($input);

            $output = ['success' => true,
                'msg' => __("lang_v1.updated_success")
            ];
        } catch (\Exception $e) {
            dd($e->getMessage());
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }
        return redirect()
            ->action('MenuController@index')
            ->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\SellingPriceGroup $sellingPriceGroup
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        if (request()->ajax()) {
            try {
                $spg = Menu::findOrFail($id);
                $spg->delete();

                $output = ['success' => true,
                    'msg' => __("lang_v1.deleted_success")
                ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output = ['success' => false,
                    'msg' => __("messages.something_went_wrong")
                ];
            }

            return $output;
        }
    }

}
