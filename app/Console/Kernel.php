<?php

namespace App\Console;

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
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $jamMasukReminder = \Carbon\Carbon::parse(config('absensi.jam_masuk'))->subHour()->format('H:i');
        $jamPulang        = \Carbon\Carbon::parse(config('absensi.jam_pulang'))->format('H:i');

        $schedule->command('attendance:remind masuk')->dailyAt($jamMasukReminder);
        $schedule->command('attendance:remind pulang')->dailyAt($jamPulang);
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
