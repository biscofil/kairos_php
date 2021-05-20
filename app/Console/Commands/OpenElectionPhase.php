<?php

namespace App\Console\Commands;

use App\Models\Election;
use Carbon\Carbon;
use Illuminate\Console\Command;

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
        $nMinutesAgo = $now->clone()->addMinutes(-3);

        $affected = Election::query()
            ->where('voting_starts_at', '>=', $nMinutesAgo)
            ->where('voting_starts_at', '<=', $now)
            ->whereNull('voting_started_at')
            ->whereNotNull('frozen_at') // frozen
            ->update(['voting_started_at' => Carbon::now()]);

        if ($affected) {
            $this->info("election phase opened for $affected elections");
        }

        return 0;
    }
}
