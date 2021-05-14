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
 * @property PeerServer $me
 * @property string $message
 */
class WebsocketLog implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public PeerServer $server;
    public string $message;

    public function __construct(string $message)
    {
        $this->server = PeerServer::me();
        $this->message = $message;
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
