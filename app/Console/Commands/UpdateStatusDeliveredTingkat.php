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

class UpdateStatusDeliveredTingkat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lunch:orderUpdateStatus';

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
            $current_time = Carbon::parse(now())->format('H');

            if ($current_time == AppConstant::DELIVERED_LUNCH_STATUS_TIME) {
                $master_list = MasterList::where(['status' => AppConstant::STATUS_ACTIVE, 'time_slot' => AppConstant::LUNCH])->whereDate('delivery_date', '=', date('Y-m-d'))->get();
                foreach ($master_list as $delivered) {
                    Log::info('delivered=>'.$delivered->id);
                    MasterList::where('id', $delivered->id)->update([
                        'status' => AppConstant::STATUS_DELIVERED
                    ]);
                }
            }
            if ($current_time == AppConstant::DELIVERED_DINNER_STATUS_TIME) {
                $master_list = MasterList::where(['status' => AppConstant::STATUS_ACTIVE, 'time_slot' => AppConstant::DINNER])->whereDate('delivery_date', '=', date('Y-m-d'))->get();
                foreach ($master_list as $delivered) {
                    Log::info('delivered=>'.$delivered->id);
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
