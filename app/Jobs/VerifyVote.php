<?php

namespace App\Jobs;

use App\Models\CastVote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Class VerifyVote
 * @package App\Jobs
 * @property CastVote castVote
 */
class VerifyVote implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

//    use WithoutOverlapping;

    public $castVote;

    public $timeout = 3600;

    /**
     * Create a new job instance.
     *
     * @param CastVote $castVote
     */
    public function __construct(CastVote $castVote)
    {
        $this->castVote = $castVote->withoutRelations();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {


        $this->castVote->verify();

    }
}
