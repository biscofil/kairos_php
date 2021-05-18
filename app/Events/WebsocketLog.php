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
 * @property string $me
 * @property null|string $messageDestionationServer
 */
class WebsocketLog implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public string $me;
    public ?string $messageDestionationServer;

    public string $message;

    public function __construct(string $message, ?PeerServer $messageDestionationServer = null)
    {
        $this->me = PeerServer::me()->domain;
        $this->message = $message;
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
