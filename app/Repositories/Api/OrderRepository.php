<?php

namespace App\Repositories\Api;

use Automattic\WooCommerce\Client;
use App\Models\Customer;
use Automattic\WooCommerce\HttpClient\HttpClientException;
use Illuminate\Support\Facades\DB;

class OrderRepository
{
    public $customers = [];
    public $customer;

    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    public function getConfiguration()
    {
        return new Client(
            config('api.url'),
            config('api.customer_key'),
            config('api.customer_secret'),
            [
                'version' => config('api.version'),
            ]
        );
    }

    public function getOrderDetails()
    {
        $orderEndpoint = config("api.order_endpoint") . '?page=1';
        $this->getData($this->getConfiguration(), $orderEndpoint);
    }

    public function getData($configuration, $endPoint)
    {
        return $configuration->get($endPoint, $parameters = []);
    }

    public function getCustomers($customerId)
    {
        $customerEndpoint = config("api.customer_endpoint");
        // dd($this->getData($this->getConfiguration(), $customerEndpoint));
        return $this->getData($this->getConfiguration(), $customerEndpoint);


    }

    public function setCustomers($customers)
    {
        $this->customer->store($this->getCustomerList($customers));
    }

    public function addOrUpdateOrderDetails()
    {

        $i = 1;
         while (true) {
                try {
                    DB::beginTransaction();
                    $orderEndpoint = config("api.order_endpoint") . '?page='. $i ;
                    $orders = $this->getData($this->getConfiguration(), $orderEndpoint);

                    if (count($orders) <= 0) {
                        break;
                    }
                    foreach ($orders as $value) {
                        dd($value);



                    }
                    DB::commit();
                } catch (HttpClientException $e) {
                    DB::rollback();
                    dd('Ex. - ', $e);
                }
                $i++;
        }
        dd('done');
    }

    public function getCustomerList($customers)
    {

        foreach ($customers as $item) {

            $this->customers[] = [
                'name'             =>      $item->first_name.' '.$item->last_name,
                'business_id'      =>      1,
                'customer_id'      =>      $item->id,
                'first_name'       =>  	   $item->first_name,
                'last_name'        =>      $item->last_name,
                'email'            =>      $item->email,

                'mobile'           =>      $item->billing->phone,
                'city'             =>      $item->billing->city,
                'state'            =>      $item->billing->state,
                'country'          =>      $item->billing->country,
                'zip_code'         =>      $item->billing->postcode,

                'address_line_1'   =>  	   $item->billing->address_1,
                'address_line_2'   =>  	   $item->billing->address_2,

                'shipping_address' =>       $item->shipping->address_1,

                'type'             =>       'customer',
                'position'         =>       'customer',
                'created_by'       =>       1,

                'customer_group_id'=>       1
            ];
        }
        return $this->customers;
    }
}
