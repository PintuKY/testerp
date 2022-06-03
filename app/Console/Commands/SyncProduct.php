<?php

namespace App\Console\Commands;

use App\Models\ApiSetting;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Variation;
use Exception;
use Illuminate\Console\Command;

class SyncProduct extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:product {business_location_id=all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Product Sync According to Business Location if Business Location is not set then it will sync all products';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $business_location_id = $this->argument('business_location_id');
        if ($business_location_id !== 'all') {
            $this->syncProductDetails($business_location_id);
        } else {
            $apiSettings = ApiSetting::get();
            foreach ($apiSettings as $apiSetting) {
                $this->syncProductDetails($apiSetting->id);
            }
        }
        return true;
    }

    /**
     * syncProductDetails
     *
     * @return void
     */
    public function syncProductDetails($bussiness_location_id)
    {
        $i = 1;
        while (true) {
            try {
                $productEndpoint = config("api.product_endpoint") . '?page=' . $i;
                $products = getData(getConfiguration($bussiness_location_id), $productEndpoint);
                if (count($products) <= 0) {
                    break;
                }
                foreach ($products as $product) {
                    $category_id = $this->getCategoryId($product->sku) ? $this->getCategoryId($product->sku) : 1;
                    $admin_id = 1;
                    if ($product->status === 'publish') {
                        $newOrUpdatedproduct = Product::updateOrCreate(
                            [
                                'product_id' =>  $product->id,
                                'business_id' => $bussiness_location_id,
                            ],
                            [
                                'category_id' =>  $category_id,
                                'product_id' =>  $product->id,
                                'sub_category_id' => null,
                                'name' =>  $product->name,
                                'business_id' =>  $bussiness_location_id,
                                'product_description' => ($product->description) ? $product->description : $product->short_description,
                                'sku' =>  $product->sku,
                                'created_by' =>  $admin_id,
                                'is_inactive' =>  1,
                                'status' =>  $product->status,
                            ]
                        );
                        $newOrUpdatedproduct->product_locations()->sync([$bussiness_location_id]);

                        $productVariations = ProductVariation::updateOrCreate(
                            [
                                'product_id' =>  $newOrUpdatedproduct->id,
                            ],
                            [
                                'variation_template_id' => null,
                                'name' => 'Dummy',
                                'product_id' => $newOrUpdatedproduct->id,
                                'is_dummy' => 1
                            ]
                        );

                        $variations = Variation::updateOrCreate([
                            'product_id' => $newOrUpdatedproduct->id,
                            'product_variation_id' => $productVariations->id
                        ], [
                            'name' =>  'DUMMY',
                            'product_id' => $newOrUpdatedproduct->id,
                            'sub_sku' =>  '000' . $newOrUpdatedproduct->id,
                            'product_variation_id' => $productVariations->id,
                            'variation_value_id' => null,
                            'default_purchase_price' => $product->price,
                            'dpp_inc_tax' => $product->price,
                            'profit_percent' => 0,
                            'default_sell_price' => $product->price,
                            'sell_price_inc_tax' => $product->price,
                        ]);
                    } else {
                        Product::where([
                            'product_id' =>  $product->id,
                            'business_id' =>  $bussiness_location_id
                        ])->delete();
                    }
                }
            } catch (Exception $e) {
                dd('Ex. - ', $e);
            }
            $i++;
        }
    }


    /**
     * getCategoryId
     *
     * @param  mixed $categoryName
     * @return void
     */
    public function getCategoryId($categoryName)
    {
        return Category::where('name', $categoryName)->value('id');
    }
}
