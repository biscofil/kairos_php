<?php

namespace App\Console\Commands;

use App\Models\Election;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class OpenElectionPhase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'open_election_phase';

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
            ->where(DB::raw('DATE_ADD(voting_starts_at, INTERVAL 3 MINUTE)'), '>=', $now)
            ->where('voting_starts_at', '<=', $now)
            ->whereNotNull('frozen_at') // frozen
            ->whereNull('voting_started_at')
            ->whereNull('voting_ended_at')
            ->update(['voting_started_at' => Carbon::now()]);

        if ($affected) {
            websocketLog("election phase opened for $affected elections");
        }

        return 0;
    }
}
