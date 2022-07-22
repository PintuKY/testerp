<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\JobForApiData;
use Illuminate\Support\Facades\Artisan;

class ManageDataOfProductOrderAndCustomerController extends Controller
{

    /**
     * syncAll
     *
     * @return void
     */
    public function syncAllDetails()
    {
        dispatch(new JobForApiData($business_location_id = null , $type = 'all'));

        return back()->with('success', 'Sync All Successfully');

    }

     /**
     * syncOrderDetails
     *
     * @return void
     */
    public function syncOrderDetails($business_location_id)
    {

        dispatch(new JobForApiData($business_location_id , $type = 'order'));

        return back()->with('success', 'Sync Order Successfully');

    }


    /**
     * syncProductDetails
     *
     * @return void
     */
    public function syncProductDetails($business_location_id)
    {

        dispatch(new JobForApiData($business_location_id , $type = 'product'));

        return back()->with('success', 'Sync Product Successfully');

    }

     /**
     * syncCustomerDetails
     *
     * @return void
     */
    public function syncCustomerDetails($business_location_id)
    {
        dispatch(new JobForApiData($business_location_id , $type = 'customer'));

        return back()->with('success', 'Sync Customer Successfully');
    }
}
