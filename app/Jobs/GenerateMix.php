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
 * Class GenerateMix
 * @package App\Jobs
 * @property Election election
 * @property array|null votes
 */
class GenerateMix implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public Election $election;
    public ?array $votes;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Election $election, ?array $votes = null)
    {
        $this->election = $election;
        $this->votes = $votes;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (is_null($this->votes)) {
            // first step, take from bulletin board
            $this->votes = $this->election->votes()->get()->toArray();
        }
        Log::debug('Running mix on ' . count($this->votes) . ' votes');
        // TODO
    }
}
