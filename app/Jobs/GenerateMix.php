<?php

namespace App\Jobs;

use App\Models\Election;
use App\Models\Mix;
use App\Models\Trustee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Class GenerateMix
 * @package App\Jobs
 * @property Election election
 * @property \App\Models\Mix|null previousMix
 * @property \App\Models\Trustee|null trusteeRunningCode
 */
class GenerateMix implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public Election $election;
    public ?Mix $previousMix;
    public ?Trustee $trusteeRunningCode;

    public $timeout = 3600;

    /**
     * GenerateMix constructor.
     * @param \App\Models\Election $election
     * @param \App\Models\Mix|null $previousMix
     * @param \App\Models\Trustee|null $trusteeRunningCode
     */
    public function __construct(Election $election, ?Mix $previousMix = null, ?Trustee $trusteeRunningCode = null)
    {
        $this->election = $election;
        $this->previousMix = $previousMix;
        $this->trusteeRunningCode = $trusteeRunningCode;
    }

    /**
     * Execute the job.
     * @return void
     * @throws \Exception
     * @see \App\Voting\AnonymizationMethods\MixNets\MixNode::afterVotingPhaseEnds()
     */
    public function handle()
    {
        $start = now();
        $mixModel = Mix::generate($this->election, $this->previousMix, $this->trusteeRunningCode);
        $end = now();
        Log::debug('Mix generated in ' . $end->diffInMilliseconds($start) . ' milliseconds');
        $mixModel->afterGeneration();
    }
}
