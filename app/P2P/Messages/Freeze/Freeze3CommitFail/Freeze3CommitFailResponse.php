<?php


namespace App\P2P\Messages\Freeze\Freeze3CommitFail;


use App\Models\PeerServer;
use App\P2P\Messages\P2PMessageResponse;

/**
 * Class Freeze3CommitFailResponse
 * @package App\P2P\Messages\Freeze\Freeze3CommitFail
 */
class Freeze3CommitFailResponse extends P2PMessageResponse
{

    /**
     * @return array
     */
    public function serialize(): array
    {
        return [];
    }

    /**
     * @param \App\Models\PeerServer $requestDestination
     * @param array $messageData
     * @param $requestMessage
     * @return \App\P2P\Messages\P2PMessageResponse
     */
    public static function unserialize(PeerServer $requestDestination, array $messageData, $requestMessage): P2PMessageResponse
    {
        return new static($requestDestination, PeerServer::me());
    }

    /**
     * @param \App\Models\PeerServer $destPeerServer
     * @param $request
     */
    public function onResponseReceived(PeerServer $destPeerServer, $request): void
    {

    }

}
