<?php

namespace App\Console;

use DB;
use Illuminate\Support\Facades\Log;
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
        // $schedule->command('inspire')->hourly();
        try {

            $schedule->call(function () {

                \App\Models\Company::outdatedWithCipa()->oldest('cipa_updated_at')->limit(100)->chunk(25, function ($companies) {
                    foreach ($companies as $key => $company) {

                        Log::debug('Updating company #'.($key+1).' - '.$company->uid);

                        $company->requestCipaUpdate();
                    }
                });

            })->everyMinute()->name('update_company_record')->withoutOverlapping();

        } catch (\Exception $e) {

            Log::error('ERROR Updating company: '.$e->getMessage());

        }

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
