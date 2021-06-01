<?php


namespace App\Jobs;


use App\Models\Mix;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Class VerifyReceivedMix
 * @package App\Jobs
 * @property Mix mixModel
 */
class VerifyReceivedMix implements ShouldQueue
{

    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public Mix $mixModel;

    /**
     * WaitAndRespond constructor.
     * @param \App\Models\Mix $mixModel
     */
    public function __construct(Mix $mixModel)
    {
        $this->mixModel = $mixModel;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $this->mixModel->verify();
    }
}
