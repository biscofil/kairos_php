<?php

namespace App\Jobs;

use App\P2P\Messages\P2PMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Class SendP2PMessage
 * @package App\Jobs
 * @property P2PMessage $message
 */
class SendP2PMessage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public P2PMessage $message;

    /**
     * Create a new job instance.
     * @param P2PMessage $message
     */
    public function __construct(P2PMessage $message)
    {
        $this->message = $message;
    }

    /**
     * Execute the job.
     * @return void
     * @throws \ReflectionException
     */
    public function handle()
    {
//        Log::debug('SendP2PMessage > SENDING....');
        $this->message->send();
//        Log::debug('SendP2PMessage > SENT');
    }
}
