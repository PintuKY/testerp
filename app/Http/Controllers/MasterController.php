<?php

namespace App\Http\Controllers;

use App\Exports\MasterListExport;
use App\Models\Business;
use App\Models\BusinessLocation;
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
        $role = 'user';
        $masterListCols = config('masterlist.'.$role.'_columns');
        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $sells = MasterList::with(['transaction_sell_lines' => function($query){
                $query->with('transactionSellLinesVariants');
            }, 'transasction']);

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $sells->whereDate('master_list.delivery_date', '>=', $start)
                        ->whereDate('master_list.delivery_date', '<=', $end);
            }

            if (!empty(request()->type)) {
                $type = request()->type;
                $sells->where('master_list.time_slot', '=', $type);
            }

            if (!empty(request()->location)) {
                $sells->whereHas('transasction' , function ($query) {
                    $query->where('business_id', request()->location);
                });
            }

            return Datatables::of($sells)
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

                ->addColumn('address', function ($row) {
                    return $row->shipping_address_line_1;
                })
                ->addColumn('postal', function ($row) {
                    return $row->shipping_zip_code;
                 })
                ->addColumn('remark', function ($row) {
                    return 'fsfsfsfsd';
                 })
                 ->addColumn('hp_number', function ($row) {
                    return 'fsfsfsf';
                 })
                ->addColumn('driver_name', function ($row) {
                    return 'Amar';
                 })
                ->make(true);
        }
        $business_locations = Business::forDropdown();
        $type = config('masterlist.product_type');
        return view('master.index',compact('masterListCols','business_locations','type'));

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

    public function exportExcel($type)
    {
        return \Excel::download(new MasterListExport, 'masterList.'.$type);
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
