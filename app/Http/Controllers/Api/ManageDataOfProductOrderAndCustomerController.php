<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        Artisan::call("sync:order");
        return back();
    }

     /**
     * syncOrderDetails
     *
     * @return void
     */
    public function syncOrderDetails($business_location_id)
    {
        Artisan::call("sync:order",[
            'business_location_id' => $business_location_id
        ]);
        return back();
    }


    /**
     * syncProductDetails
     *
     * @return void
     */
    public function syncProductDetails($business_location_id)
    {
        Artisan::call("sync:product",[
            'business_location_id' => $business_location_id
        ]);
        return back();
    }

     /**
     * syncCustomerDetails
     *
     * @return void
     */
    public function syncCustomerDetails($business_location_id)
    {
        Artisan::call("sync:customer",[
            'business_location_id' => $business_location_id
        ]);
        return back();
    }
}
