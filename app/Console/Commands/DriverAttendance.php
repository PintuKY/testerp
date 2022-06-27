<?php

namespace App\Console\Commands;

use App\Models\Business;

use App\Models\Driver;
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

class DriverAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'driver:attendance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Driver attendance daily.';

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

            $drivers = Driver::active()->where('driver_type',AppConstant::FULL_TIME)->get();
            foreach ($drivers as $driver) {
                $attendance = \App\Models\DriverAttendance::where('driver_id',$driver->id)->whereDate('attendance_date','=',Carbon::parse(now())->format('Y-m-d'))->first();

                if(empty($attendance)){
                    \App\Models\DriverAttendance::insert([
                        'driver_id' => $driver->id,
                        'attendance_date' => Carbon::parse(now())->format('Y-m-d'),
                        'is_half_day' => AppConstant::HALF_DAY_NO,
                        'in_or_out' => AppConstant::ATTENDANCE_IN,
                        'leave_reason' => AppConstant::STATUS_INACTIVE,
                        'leave_reason_description' => null,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
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
