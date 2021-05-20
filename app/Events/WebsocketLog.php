<?php

namespace App\Events;

use App\Models\PeerServer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class WebsocketLog
 * @package App\Events
 * @property string $message
 * @property null|string $messageSenderServer
 * @property null|string $messageDestionationServer
 */
class WebsocketLog implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public ?string $messageSenderServer;
    public ?string $messageDestionationServer;

    public string $message;

    public function __construct(string $message, ?PeerServer $messageDestionationServer = null, ?PeerServer $messageSenderServer = null)
    {
        $this->message = $message;
        $this->messageSenderServer = $messageSenderServer ? $messageSenderServer->domain : null;
        $this->messageDestionationServer = $messageDestionationServer ? $messageDestionationServer->domain : null;
    }

    public function broadcastOn()
    {
        return ['my-channel'];
    }

    public function broadcastAs()
    {
        return 'my-event';
    }
}
