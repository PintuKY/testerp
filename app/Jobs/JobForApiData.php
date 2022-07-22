<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class JobForApiData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $business_location_id;
    public $type;
    public function __construct($business_location_id , $type)
    {
        $this->business_location_id = $business_location_id;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->type === 'customer') {
            Artisan::call("sync:customer",[
                'business_location_id' => $this->business_location_id
            ]);
        }

        if ($this->type === 'product') {
            Artisan::call("sync:product",[
                'business_location_id' => $this->business_location_id
            ]);
        }

        if ($this->type === 'order') {
            Artisan::call("sync:order",[
                'business_location_id' => $this->business_location_id
            ]);
        }

        if ($this->type === 'all')
        {
            Artisan::call("sync:order");
        }

    }
}
