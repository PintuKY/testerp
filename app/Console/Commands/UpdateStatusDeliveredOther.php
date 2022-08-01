<?php

namespace App\Console\Commands;

use App\Models\Business;

use App\Models\MasterList;
use App\Models\Transaction;
use App\Utils\AppConstant;
use App\Utils\NotificationUtil;

use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateStatusDeliveredOther extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'other:orderUpdateStatus';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Master list update status delivered when order delivered succefully for lunch.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '512M');

            DB::beginTransaction();
            $master_list = MasterList::whereHas('transaction_sell_lines', function ($query){
                $query->where('unit_name','!=',AppConstant::TINGKAT);
            })->whereIn('status',[AppConstant::FINAL,AppConstant::COMPLETED,AppConstant::PROCESSING])->whereDate('start_date', '>', Carbon::now()->subHours(2)->format('Y-m-d H:i:s'))->get();
            if($master_list){
                foreach ($master_list as $delivered) {
                    Log::info('delivered=>' . $delivered->id);
                    MasterList::where('id', $delivered->id)->update([
                        'status' => AppConstant::STATUS_DELIVERED
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            die($e->getMessage());
        }
    }
}
