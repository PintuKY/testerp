<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\BusinessLocation;
use App\Models\KitchenLocation;
use App\Models\InvoiceLayout;
use App\Models\InvoiceScheme;
use App\Models\SellingPriceGroup;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\File;
class BusinessLocationController extends Controller
{
    protected $moduleUtil;
    protected $commonUtil;

    /**
     * Constructor
     *
     * @param ModuleUtil $moduleUtil
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil, ModuleUtil $moduleUtil, Util $commonUtil)
    {
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;
        $this->commonUtil = $commonUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // if (!auth()->user()->can('business_settings.access')) {
        //     abort(403, 'Unauthorized action.');
        // }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $locations = BusinessLocation::where('business_locations.business_id', $business_id)
                ->leftjoin(
                    'invoice_schemes as ic',
                    'business_locations.invoice_scheme_id',
                    '=',
                    'ic.id'
                )
                ->leftjoin(
                    'invoice_layouts as il',
                    'business_locations.invoice_layout_id',
                    '=',
                    'il.id'
                )
                ->leftjoin(
                    'invoice_layouts as sil',
                    'business_locations.sale_invoice_layout_id',
                    '=',
                    'sil.id'
                )
                ->leftjoin(
                    'selling_price_groups as spg',
                    'business_locations.selling_price_group_id',
                    '=',
                    'spg.id'
                )
                ->select(['business_locations.name', 'location_id', 'landmark', 'city', 'zip_code', 'state',
                    'country', 'business_locations.id', 'spg.name as price_group', 'ic.name as invoice_scheme', 'il.name as invoice_layout', 'sil.name as sale_invoice_layout', 'business_locations.is_active', 'kitchen_location_id']);

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $locations->whereIn('business_locations.id', $permitted_locations);
            }

            $business_id = BusinessLocation::with(['kitchenLocation' => function ($q) {
                $q->select('name as kitchen_name', 'id');
            }]);

            return Datatables::of($business_id)
                ->addColumn('kitchen_location_name', function ($row) {
                    return $row->kitchenLocation->kitchen_name ?? '';
                })
                //Button part........
                ->addColumn(
                    'action',
                    '<button type="button" data-href="{{action(\'BusinessLocationController@edit\', [$id])}}" class="btn btn-xs btn-primary btn-modal" data-container=".location_edit_modal"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                        <a href="{{route(\'location.settings\', [$id])}}" class="btn btn-success btn-xs"><i class="fa fa-wrench"></i> @lang("messages.settings")</a>

                        <button type="button" data-href="{{action(\'BusinessLocationController@activateDeactivateLocation\', [$id])}}" class="btn btn-xs activate-deactivate-location @if($is_active) btn-danger @else btn-success @endif"><i class="fa fa-power-off"></i> @if($is_active) @lang("lang_v1.deactivate_location") @else @lang("lang_v1.activate_location") @endif </button>

                        @can("kitchen_location.delete")
                        <button data-href="{{action(\'BusinessLocationController@destroy\', [$id])}}" class="btn btn-xs btn-danger delete_business_location_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>
                        @endcan
                        '
                )
                ->removeColumn('id')
                ->removeColumn('is_active')
                ->rawColumns([11])
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('business_location.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not, then check for location quota
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        } elseif (!$this->moduleUtil->isQuotaAvailable('locations', $business_id)) {
            return $this->moduleUtil->quotaExpiredResponse('locations', $business_id);
        }

        $invoice_layouts = InvoiceLayout::where('business_id', $business_id)
            ->get()
            ->pluck('name', 'id');

        $invoice_schemes = InvoiceScheme::where('business_id', $business_id)
            ->get()
            ->pluck('name', 'id');

        $price_groups = SellingPriceGroup::forDropdown($business_id);

        $payment_types = $this->commonUtil->payment_types(null, false, $business_id);

        //Accounts
        $accounts = [];
        if ($this->commonUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }
        $kitchenlocation = KitchenLocation::pluck('name', 'id');

        return view('business_location.create')
            ->with(compact(
                'invoice_layouts',
                'invoice_schemes',
                'price_groups',
                'payment_types',
                'accounts',
                'kitchenlocation',
            ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            //Check if subscribed or not, then check for location quota
            if (!$this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse();
            } elseif (!$this->moduleUtil->isQuotaAvailable('locations', $business_id)) {
                return $this->moduleUtil->quotaExpiredResponse('locations', $business_id);
            }

            $input = $request->only(['name', 'landmark', 'city', 'state', 'country', 'zip_code', 'invoice_scheme_id',
                'invoice_layout_id', 'mobile', 'alternate_number', 'email', 'website', 'custom_field1', 'custom_field2', 'custom_field3', 'custom_field4', 'location_id', 'selling_price_group_id', 'default_payment_accounts', 'featured_products', 'sale_invoice_layout_id', 'kitchen_location_id']);

            //upload logo

            $logo_name = $this->businessUtil->uploadFiles($request, 'business_logo', 'business_location_logos', 'image');
            if (!empty($logo_name)) {
                $input['logo'] = $logo_name;
            }
            $input['business_id'] = $business_id;

            $input['default_payment_accounts'] = !empty($input['default_payment_accounts']) ? json_encode($input['default_payment_accounts']) : null;

            //Update reference count
            $ref_count = $this->moduleUtil->setAndGetReferenceCount('business_location');

            if (empty($input['location_id'])) {
                $input['location_id'] = $this->moduleUtil->generateReferenceNumber('business_location', $ref_count);
            }

            $location = BusinessLocation::create($input);

            //Create a new permission related to the created location
            Permission::create(['name' => 'location.' . $location->id]);

            $output = ['success' => true,
                'msg' => __("business.business_location_added_success")
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    /**
     * Display the specified resource.
     *
     * @param \App\StoreFront $storeFront
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\StoreFront $storeFront
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $location = BusinessLocation::where('business_id', $business_id)
            ->find($id);
        $invoice_layouts = InvoiceLayout::where('business_id', $business_id)
            ->get()
            ->pluck('name', 'id');
        $invoice_schemes = InvoiceScheme::where('business_id', $business_id)
            ->get()
            ->pluck('name', 'id');

        $price_groups = SellingPriceGroup::forDropdown($business_id);

        $payment_types = $this->commonUtil->payment_types(null, false, $business_id);

        //Accounts
        $accounts = [];
        if ($this->commonUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }
        $featured_products = $location->getFeaturedProducts(true, false);
        $kitchenlocation = KitchenLocation::pluck('name', 'id');
        return view('business_location.edit')
            ->with(compact(
                'location',
                'invoice_layouts',
                'invoice_schemes',
                'price_groups',
                'payment_types',
                'accounts',
                'featured_products',
                'kitchenlocation',
            ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\StoreFront $storeFront
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['name', 'landmark', 'city', 'state', 'country',
                'zip_code', 'invoice_scheme_id',
                'invoice_layout_id', 'mobile', 'alternate_number', 'email', 'website', 'custom_field1', 'custom_field2', 'custom_field3', 'custom_field4', 'location_id', 'selling_price_group_id', 'default_payment_accounts', 'featured_products', 'sale_invoice_layout_id', 'kitchen_location_id']);

            $business_id = $request->session()->get('user.business_id');

            $business_location = BusinessLocation::where('id', $id)->first();
            $input['default_payment_accounts'] = !empty($input['default_payment_accounts']) ? json_encode($input['default_payment_accounts']) : null;

            $input['featured_products'] = !empty($input['featured_products']) ? json_encode($input['featured_products']) : null;
//upload logo
            if ($request->hasFile('business_logo') && $request->file('business_logo')->isValid()) {

                $logo_name = $this->businessUtil->uploadFiles($request, 'business_logo', 'business_location_logos', 'image');
                if (!empty($logo_name)) {
                    Storage::disk('public')->delete("business_location_logos/{$business_location->logo}");
                    $input['logo'] = $logo_name;
                }
            }
            BusinessLocation::where('business_id', $business_id)
                ->where('id', $id)
                ->update($input);

            $output = ['success' => true,
                'msg' => __('business.business_location_updated_success')
            ];
        } catch (\Exception $e) {
            dd($e->getMessage());
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\StoreFront $storeFront
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('user.business_id')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->user()->business_id;

                $brand = BusinessLocation::find($id);
                $brand->delete();

                $output = ['success' => true,
                    'msg' => __("business.deleted_success")
                ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output = ['success' => false,
                    'msg' => __("business.something_went_wrong")
                ];
            }

            return $output;
        }
    }

    /**
     * Checks if the given location id already exist for the current business.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function checkLocationId(Request $request)
    {
        $location_id = $request->input('location_id');

        $valid = 'true';
        if (!empty($location_id)) {
            $business_id = $request->session()->get('user.business_id');
            $hidden_id = $request->input('hidden_id');

            $query = BusinessLocation::where('business_id', $business_id)
                ->where('location_id', $location_id);
            if (!empty($hidden_id)) {
                $query->where('id', '!=', $hidden_id);
            }
            $count = $query->count();
            if ($count > 0) {
                $valid = 'false';
            }
        }
        echo $valid;
        exit;
    }

    /**
     * Function to activate or deactivate a location.
     * @param int $location_id
     *
     * @return json
     */
    public function activateDeactivateLocation($location_id)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $business_location = BusinessLocation::where('business_id', $business_id)
                ->findOrFail($location_id);

            $business_location->is_active = !$business_location->is_active;
            $business_location->save();

            $msg = $business_location->is_active ? __('lang_v1.business_location_activated_successfully') : __('lang_v1.business_location_deactivated_successfully');

            $output = ['success' => true,
                'msg' => $msg
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
