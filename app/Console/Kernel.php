<?php

namespace App\Console;

use App\Console\Commands\UpdateDinnerMasterList;
use App\Console\Commands\UpdateStatusDeliveredOther;
use App\Console\Commands\UpdateStatusDeliveredTingkat;
use App\Utils\AppConstant;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        UpdateStatusDeliveredTingkat::class,
        UpdateStatusDeliveredOther::class,
    ];
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('lunch:orderUpdateStatus')->twiceDaily(AppConstant::DELIVERED_LUNCH_STATUS_TIME,AppConstant::DELIVERED_DINNER_STATUS_TIME);
        $schedule->command('other:orderUpdateStatus')->everyTwoHours();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
