<?php

namespace App\Console;

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

                /** Get the companies from the oldest to newest updated
                 *
                 *  The oldest() method will allow us to order by the following rules:
                 *
                 *  1) Start with the companies that have the "cipa_updated_at" set to NULL
                 *  2) Continue to the companies that have the "cipa_updated_at" from old to new
                 *
                 *  This order ensures that we update in the following manner:
                 *
                 *  1) Update the companies that have not been imported yet (cipa_updated_at should be NULL)
                 *  2) Update the companies that were last updated a while ago
                 *  3) Finish by updating companies that were updated recently
                 *
                 *  We don't need too much info about the companies, we just want the Eloquent Model instance
                 *  and the company UIN. Therefore to avoid "Running Out Of Memory" because of pulling too
                 *  much data we will only request the "uin" field since this is the only field required
                 *  to search the matching record on CIPA side ($this->uin). The Eloquest instance can
                 *  then be used to update the company e.g $this->update([ ... ]);
                 */
                $companies_to_update = \App\Models\Company::whereNotNull('uin')->oldest('cipa_updated_at');

                /**
                 *  We need the id, uin and name to later search for any duplicates so
                 *  that we can sync any company records that should match.
                 */
                $companies_to_sync = \App\Models\Company::select(['id', 'uin', 'name', 'old_uins', 'company_status', 'incorporation_date', 're_registration_date'])->get();

                //  Only query 100 companies at a time
                $companies_to_update->chunk(100, function ($companies) use ($companies_to_sync){

                    //  Foreach company we retrieved from the query
                    foreach ($companies as $company) {

                        //  Set the companies to sync
                        $company->companies_to_sync = $companies_to_sync;

                        //  Update the company
                        $company->requestCipaUpdate();

                    }
                });

            })->name('update_company_record')->everyMinute()->withoutOverlapping();

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
