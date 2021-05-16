<?php

namespace App\Jobs;

use App\Models\Election;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Class OnElectionFreezeTimeout
 * @package App\Jobs
 * @property Election $election
 */
class OnElectionFreezeTimeout implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public Election $election;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Election $election)
    {
        $this->election = $election;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // timeout reached after Freeze1IAmFreezingElection sent out

        // TODO
        //  check if enough peers are ok
        //    election is actually frozen
        //    if not the freeze is invalid

        Log::warning('OnElectionFreezeTimeout > Timeout expired after Freeze1IAmFreezingElection');

    }
}
