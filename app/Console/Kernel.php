<?php

namespace App\Console;

use DB;
use Carbon\Carbon;
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
                $companies = \App\Models\Company::with(['directors', 'shareholders'])->oldest('cipa_updated_at');

                //  ->without('addresses') or ->withOnly(['directors', 'shareholders']) to not eager load addresses

                Log::debug('Preparing to update companies - '.(Carbon::now())->format('d M Y H:i:s') .' - Found: '.$companies->count());

                //  Only query 100 companies at a time
                $companies->chunk(100, function ($companies) {

                    //  Foreach company we retrieved from the query
                    foreach ($companies as $company) {

                        Log::debug('Company UIN - '.$company->uin);

                        //  Update the company
                        $company->requestCipaUpdate(false);

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
