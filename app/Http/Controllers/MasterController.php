<?php

namespace App\Http\Controllers;

use App\Models\MasterList;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
class MasterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            $sells = MasterList::with(['transaction_sell_lines' => function($query){
                $query->with('transactionSellLinesVariants');
            }]);

            return Datatables::of($sells)
                ->addColumn(
                    'action',
                    '<div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                        data-toggle="dropdown" aria-expanded="false">' .
                        __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                        </span>
                    </button>

                    </div>'
                )
                ->addColumn('pax', function ($row) {
                    $pax = [];
                    if (isset($row->transaction_sell_lines->transactionSellLinesVariants)) {
                        foreach ($row->transaction_sell_lines->transactionSellLinesVariants as $value) {
                            if (str_contains($value->name, 'Serving Pax')) {
                                $pax[] = $value->value;
                            }
                        }

                    }
                    return implode(',', $pax);
                })
                ->addColumn('addon', function ($row) {
                    $addon = [];
                    if (isset($row->transaction_sell_lines->transactionSellLinesVariants)) {
                        foreach ($row->transaction_sell_lines->transactionSellLinesVariants as $value) {
                            if (str_contains($value->name, 'Add on')) {
                                $addon_pax = ($value->value  != 'None') ? '+'.$value->value : '';
                                $addon[] = str_replace("Add on:","",$value->name).''.$addon_pax;
                            }
                        }

                    }
                    return implode(',', $addon);
                })
                ->editColumn(
                    'hp_number',
                    '8df98sdf8dsif'
                )
                ->editColumn(
                    'driver_name',
                    'driver name'
                )
                ->rawColumns(['pax', 'addon', 'hp_number', 'driver_name', 'action'])
                ->make(true);
        }
        return view('master.index');

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
