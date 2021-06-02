<?php


namespace App\P2P\Messages\IReceivedTheseVotes;


use App\Models\PeerServer;
use App\P2P\Messages\P2PMessageResponse;

class IReceivedTheseVotesResponse extends P2PMessageResponse
{

    /**
     * IReceivedTheseVotesResponse constructor.
     * @param \App\Models\PeerServer $requestDestination
     * @param \App\Models\PeerServer $requestSender
     */
    public function __construct(PeerServer $requestDestination, PeerServer $requestSender)
    {
        parent::__construct($requestDestination, $requestSender);
    }

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
        return new static($requestDestination, getCurrentServer());
    }

    /**
     * @param \App\Models\PeerServer $destPeerServer
     * @param $request
     */
    public function onResponseReceived(PeerServer $destPeerServer, $request): void
    {
        // do nothing
    }
}
