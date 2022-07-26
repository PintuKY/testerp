<?php

namespace App\Console\Commands;

use App\Models\ApiSetting;
use App\Models\BusinessLocation;
use App\Models\Contact;
use Exception;
use Illuminate\Console\Command;
use App\Utils\ContactUtil;

class SyncCustomer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:customer {business_location_id=all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Customer Sync According to Business Location if Business Location is not set then it will sync all customers';

    /**
     * Execute the console command.
     *
     * @return int
     */
    protected $contactUtil;

    public function handle(ContactUtil $contactUtil)
    {
        $this->contactUtil = $contactUtil;
        $business_location_id = $this->argument('business_location_id');
        if ($business_location_id !== 'all') {
            $this->syncCustomerDetails($business_location_id);
        } else {
            $apiSettings = ApiSetting::get();
            foreach ($apiSettings as $apiSetting) {
                $this->syncCustomerDetails($apiSetting->business_locations_id);
            }
        }
        return true;
    }

        /**
     * syncCustomerDetails
     *
     * @return void
     */
    public function syncCustomerDetails($bussiness_location_id)
    {
        $i = 1;
        while (true) {
            try {
                $customerEndpoint = config("api.customer_endpoint") . '?page=' . $i. '&orderby=registered_date&order=desc';
                $customers = getData(getConfiguration($bussiness_location_id), $customerEndpoint);
                if (count($customers) <= 0) {
                    break;
                }
                if (isset($customers)) {
                    foreach ($customers as $customer) {
                        $contact = Contact::updateOrCreate(
                            [
                                'contact_id' => $customer->id,
                                'business_location_id' => $bussiness_location_id
                            ],
                            [
                                'business_id' => 1,
                                'business_location_id' => $bussiness_location_id,
                                'type' => $customer->role,
                                'name' => $customer->first_name . ' ' . $customer->last_name,
                                'first_name' => $customer->first_name,
                                'last_name' => $customer->last_name,
                                'email' => $customer->email,
                                'contact_id' => $this->getContactId($bussiness_location_id).''.$customer->id,
                                'city' => optional($customer->billing)->city,
                                'state' => optional($customer->billing)->state,
                                'country' => optional($customer->billing)->country,
                                'address_line_1' => optional($customer->billing)->address_1,
                                'address_line_2' => optional($customer->billing)->address_2,
                                'zip_code' => optional($customer->billing)->postcode,
                                'mobile' => optional($customer->billing)->phone,
                                'created_by' => 1,
                                'shipping_address' => null,
                                'shipping_custom_field_details' => null,
                                'billing_phone' => optional($customer->billing)->phone,
                                'billing_email' => optional($customer->billing)->email,
                                'shipping_address_1' => optional($customer->shipping)->address_1,
                                'shipping_address_2' => optional($customer->shipping)->address_2,
                                'shipping_city' => optional($customer->shipping)->city,
                                'shipping_state' => optional($customer->shipping)->state,
                                'shipping_zipcode' => optional($customer->shipping)->postcode,
                                'customer_group_id' => 1

                            ]
                        );
                        if($contact->wasRecentlyCreated){
                            $this->contactUtil->activityLog($contact, 'added');
                        } else {
                            $this->contactUtil->activityLog($contact, 'updated');
                        }

                    }
                } else {
                    break;
                }
            } catch (Exception $e) {
                dd('Ex. - ', $e);
            }
            $i++;
        }
    }

    public function getContactId($business_location_id)
    {
        return BusinessLocation::where('id', $business_location_id)->value('location_id');
    }
}
