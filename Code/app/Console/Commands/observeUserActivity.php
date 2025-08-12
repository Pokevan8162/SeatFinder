<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Status;
use Illuminate\Support\Facades\Log;

class observeUserActivity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:observe-user-activity';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consistently runs (every minute) to observe users activity. If the users status is not updated for two minutes, set it to be Away. Checks all users status every two minutes.
        Users computers will update their status every minute.';

    // This will run every minute on the server end through a windows scheduled task in CMD. It sets the user status to 'Away' if their entry hasn't been updated in 2 mins.
    // A script runs on user pcs that will update their status to 'Active' every minute. If their PC is turned off and does not
    // run the script, then this will detect that they are away and their desk location may not be accurate (last seen at this desk).
    public function handle()
    {
        Log::info('Observing user activity...');
        // Set a Carbon Date to now and compare all dates
        $threshold = Carbon::now()->subMinutes(2);

        // First, grab the users who will be sent to away for logging purposes
        $userIDs = Status::where('updated_at', '<', $threshold)
            ->where('status', '!=', 'Away')
            ->pluck('userID'); // get just the userID column

        // Grab all updated at values and the associated userIDs, and if we find a date that is older than two minutes, then we set their status to Away.
        Status::where('updated_at', '<', $threshold)
            ->where('status', '!=', 'Away')
            ->update(['status' => 'Away']);

        // Log each user that was sent to Away
        foreach ($userIDs as $userID) {
            Log::info('Marked ' . $userID . ' as away.');
        }
    }
}
