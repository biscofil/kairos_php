<?php


namespace App\P2P\Messages\Heartbeat;


use App\Models\PeerServer;
use App\P2P\Messages\P2PMessageResponse;

class HeartBeatResponse extends P2PMessageResponse
{

    public function serialize(): array
    {
        return [];
    }

    public static function unserialize(PeerServer $requestDestination, array $messageData, $requestMessage): P2PMessageResponse
    {
        return new static($requestDestination, PeerServer::me());
    }

    public function onResponseReceived(PeerServer $destPeerServer, $request): void
    {
        // do nothing
    }
}
