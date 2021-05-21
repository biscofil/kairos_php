<?php

namespace App\Console\Commands;

use App\Models\Election;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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

        $affected = Election::query()
            ->where(DB::raw('DATE_ADD(voting_ends_at, INTERVAL 3 MINUTE)'), '>=', $now)
            ->where('voting_ends_at', '<=', $now)
            ->whereNotNull('frozen_at') // frozen
            ->whereNotNull('voting_started_at') // open
            ->whereNull('voting_ended_at')
            ->get()
            ->reduce(function (int $carry, Election $election) {
                $result = $election->closeVotingPhase();
                return $carry + intval($result);
            },0);

        if ($affected) {
            websocketLog("election phase ended for $affected elections");
        }

        return 0;
    }
}
