<?php

namespace App\Http\Controllers;

use DB;
use Excel;
use Carbon\Carbon;
use App\Utils\Util;
use App\Models\Recipe;
use App\Models\TaxRate;
use App\Models\Variation;
use App\Models\Ingredient;
use App\Models\RecipeItem;
use Illuminate\Http\Request;
use App\Models\SupplierProduct;
use App\Models\SellingPriceGroup;
use App\Models\VariationGroupPrice;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class RecipeController extends Controller
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

        if (request()->ajax()) {
            $menu = Recipe::with('recipe_items')->select(['id', 'name']);

            return Datatables::of($menu)
                ->addColumn(
                    'action',

                    '<a class="btn btn-xs btn-primary" href="{{action(\'RecipeController@edit\', [$id])}}"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</a>

                        <button data-href="{{action(\'RecipeController@destroy\', [$id])}}" class="btn btn-xs btn-danger delete_spg_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>'
                )/*->addColumn('ingredient_name',function ($row){
                    if($row->recipe_items->ingredient_id != null){
                        $name = Ingredient::where('id',$row->recipe_items->ingredient_id)->first();
                        $ing_name = $name->name;
                    }else{
                        $ing_name = 'NA';
                    }
                    return $ing_name;
                })->addColumn('measure_type',function ($row){

                    return getingredientMeasure($row->recipe_items->measure_type);
                })*/
                ->removeColumn('status')
                /*->removeColumn('id')*/
                ->make(true);
        }

        return view('recipe.index');
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
        // $parent_ingredient = Ingredient::whereNull('ingredient_parent_id')->active()->pluck('name', 'id')->toArray();

        return view('recipe.create');
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

        try {
            $input = $request->recipe;
            if (sizeof($input)) {
                $menu = Recipe::create([
                    'name' => $request->name,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
                foreach ($input as $key => $item) {
                    $spg = RecipeItem::create([
                        'recipe_id' => $menu->id,
                        'ingredient_id' => $key,
                        // 'measure_type' => $item['measure_type'],
                        'quantity' => $item['quantity'],
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);

                }
            }
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
            ->action('RecipeController@index')
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
        $menu = Recipe::with(['recipe_items','recipe_items.ingredient.unit'])->where('id', $id)->first();
        $ing_id = [];
        foreach ($menu->recipe_items as $item) {
            $ing_id[] = $item->ingredient_id;
        }
        // $selected_ingredient = Ingredient::whereIn('id', $ing_id)->get();

        // $parent_ingredient = Ingredient::whereNull('ingredient_parent_id')->active()->pluck('name', 'id')->toArray();
        return view('recipe.edit')
            ->with(compact('menu'));

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
            $input = $request->recipe;

            if (sizeof($input)) {
                $menu = Recipe::findOrFail($id);
                $menu->name = $request->name;
                $menu->save();
                $ingredient_id = [];
                $menu_item = RecipeItem::where(['recipe_id'=>$id])->pluck('ingredient_id')->toArray();

                foreach ($input as $key => $item) {
                    $ingredient_id[] = $key;

                    /*if($menu_item){
                        RecipeItem::where('id',$menu_item->id)->update([
                            'measure_type' => $item['measure_type'],
                            'quantity' => $item['quantity'],
                        ]);
                    }else{
                        $spg = RecipeItem::create([
                            'recipe_id' => $menu->id,
                            'ingredient_id' => $key,
                            'measure_type' => $item['measure_type'],
                            'quantity' => $item['quantity'],
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]);
                    }*/
                }
                $old_val = $menu_item;
                $new_val = $ingredient_id;
                sort($new_val);
                sort($old_val);
                $old_value = implode(',', $old_val);
                $new_value = implode(',', $new_val);
                if ($new_val != $old_val) {
                    RecipeItem::where('recipe_id',$id)->delete();
                    foreach ($input as $key => $item) {
                        RecipeItem::create([
                            'recipe_id' => $menu->id,
                            'ingredient_id' => $key,
                            // 'measure_type' => $item['measure_type'],
                            'quantity' => $item['quantity'],
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]);
                    }
                }else{
                    foreach ($input as $key => $item) {
                        RecipeItem::where(['recipe_id'=> $id, 'ingredient_id'=>$key])->update([
                            // 'measure_type' => $item['measure_type'],
                            'quantity' => $item['quantity'],
                        ]);
                    }
                }
            }

            $output = ['success' => true,
                'msg' => __("lang_v1.updated_success")
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }
        return redirect()
            ->action('RecipeController@index')
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
                $spg = Recipe::findOrFail($id);
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

    public function getIngredients()
    {

        if (request()->ajax()) {
            $search_term = request()->input('term', '');
            $search_fields = request()->get('search_fields', ['name']);
            $query = SupplierProduct::where('deleted_at',null);
            // Log::info($query);
            // $query = Ingredient::whereNotNull('ingredient_parent_id')->active();
            //Include search
            if (!empty($search_term)) {
                $query->where(function ($query) use ($search_term, $search_fields) {
                    if (in_array('name', $search_fields)) {
                        $query->where('supplier_products.name', 'like', '%' . $search_term . '%');
                    }
                });
            }
            $query->select(
                'supplier_products.id as product_id',
                'supplier_products.name',
            );
            $result = $query->get();
            return json_encode($result);
        }
    }

    public function getIngRow($id)
    {
        if ($id != '') {
            $ingredient = SupplierProduct::with('unit')->where('id', $id)->first();
            $output['html_content'] = view('recipe.product_row')
                ->with(compact('ingredient'))
                ->render();
        } else {
            $output['html_content'] = '';
        }
        $output['success'] = true;
        return $output;
    }

}
