<?php

namespace App\Repositories\Api;

use Automattic\WooCommerce\Client;
use App\Models\product;
use App\Models\Category;
use App\Models\Business;
use App\Models\User;
use Automattic\WooCommerce\HttpClient\HttpClientException;
use Illuminate\Support\Facades\DB;

class ProductRepository
{
    public $products = [];
    public $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
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

    public function addOrUpdateProductDetails()
    {

        $i = 1;
        $business_id = null;
        while (true) {
            try {
                // dd($this->getConfiguration(),config("api.product_endpoint"));
                DB::beginTransaction();
                $productEndpoint = config("api.order_endpoint") . '?page=1';
                // https://s.angelconfinementmeals.com.sg/wp-json/wc/v3/products
                $products = $this->getData($this->getConfiguration(), $productEndpoint);
                dd($products);

                dd($products,$this->getBusinessId());
                if (count($products) <= 0) {
                    break;
                }
                foreach ($products as $value) {
                    $business_id = $this->getBusinessId();

                    $category_id =  1;
                    // $category_id =  $this->getCategoryId($value->sku);
                    $admin_id = 1;
                    if ($value->status === 'publish') {
                        $product = Product::updateOrCreate(
                            [
                                'name' =>  $value->name,
                                'business_id' =>  $business_id
                            ],
                            [
                                'category_id' =>  $category_id,
                                'sub_category_id' => $category_id,
                                'name' =>  $value->name,
                                'business_id' =>  $business_id,
                                'product_description' =>  $value->short_description,
                                'sku' =>  $value->sku,
                                'created_by' =>  $admin_id,
                                'is_inactive' =>  1,
                                'status' =>  $value->status,
                            ]
                        );
                    } else {
                        Product::where([
                            'name' =>  $value->name,
                            'business_id' =>  $business_id
                        ])->delete();
                    }
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

    public function getBusinessId()
    {
        $url = config('api.url');
        if (str_contains($url, '.com.sg')) {
            if (str_contains($url, 'https://www')) {
                $business_id = config('api.business.' . $this->get_string_between($url, 'https://www.', '.com.sg'));
            } else if (str_contains($url, 'https://s')) {
                $business_id = config('api.business.' . $this->get_string_between($url, 'https://www.', '.com.sg'));
            }
        } elseif (str_contains($url, '.com')) {
            $business_id = config('api.business.' . $this->get_string_between($url, 'https://www.', '.com'));
        } else {
            $business_id = config('api.business.' . $this->get_string_between($url, 'https://www.', '.sg'));
        }
        return $business_id;
    }

    public function get_string_between($string, $start, $end)
    {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }

    public function getData($configuration, $endPoint)
    {
        return $configuration->get($endPoint, $parameters = []);
    }

    public function getCategoryId($categoryName)
    {
        return Category::where('name', $categoryName)->get('id');
    }


    public function getAdminId()
    {
        // User::
    }
    // public function getProductList($products)
    // {

    //     foreach ($products as $item) {

    //     //     $this->products[] = [
    //     //         'name'             =>      $item->first_name.' '.$item->last_name,
    //     //         'business_id'      =>      1,
    //     //         'product_id'      =>      $item->id,
    //     //         'first_name'       =>  	   $item->first_name,
    //     //         'last_name'        =>      $item->last_name,
    //     //         'email'            =>      $item->email,

    //     //         'mobile'           =>      $item->billing->phone,
    //     //         'city'             =>      $item->billing->city,
    //     //         'state'            =>      $item->billing->state,
    //     //         'country'          =>      $item->billing->country,
    //     //         'zip_code'         =>      $item->billing->postcode,

    //     //         'address_line_1'   =>  	   $item->billing->address_1,
    //     //         'address_line_2'   =>  	   $item->billing->address_2,

    //     //         'shipping_address' =>       $item->shipping->address_1,

    //     //         'type'             =>       'product',
    //     //         'position'         =>       'product',
    //     //         'created_by'       =>       1,

    //     //         'product_group_id'=>       1
    //     //     ];
    //     }
    //     return $this->products;
    // }
}
