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
 * @property P2PMessageRequest[] $messages
 */
class SendP2PMessage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public array $messages;

    /**
     * Create a new job instance.
     * @param $messages
     */
    public function __construct($messages)
    {
        if (!is_array($messages)) {
            $messages = [$messages];
        }
        $this->messages = $messages;
    }

    /**
     * Execute the job.
     * @return void
     */
    public function handle()
    {
        foreach ($this->messages as $message) {
            $message->send();
        }
    }
}
