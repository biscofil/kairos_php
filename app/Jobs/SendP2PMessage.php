<?php

namespace App\Jobs;

use App\P2P\Messages\P2PMessageRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Class SendP2PMessage
 * @package App\Jobs
 * @property P2PMessageRequest $message
 */
class SendP2PMessage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public P2PMessageRequest $message;

    /**
     * Create a new job instance.
     * @param P2PMessageRequest $message
     */
    public function __construct(P2PMessageRequest $message)
    {
        $this->message = $message;
    }

    /**
     * Execute the job.
     * @return void
     */
    public function handle()
    {
//        Log::debug('SendP2PMessage > SENDING....');
        $this->message->send();
//        Log::debug('SendP2PMessage > SENT');
    }
}
