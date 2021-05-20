<?php

namespace App\Console\Commands;

use App\Models\Election;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CloseElectionPhase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'close_election_phase';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $now = Carbon::now();
        $nMinutesAgo = $now->clone()->addMinutes(-3);

        $affected = Election::query()
            ->where('voting_ends_at', '>=', $nMinutesAgo)
            ->where('voting_ends_at', '<=', $now)
            ->whereNotNull('frozen_at') // frozen
            ->whereNotNull('voting_started_at') // open
            ->whereNull('voting_ended_at')
            ->update(['voting_ended_at' => Carbon::now()]);

        if ($affected) {
            websocketLog("election phase ended for $affected elections");
        }

        return 0;
    }
}
