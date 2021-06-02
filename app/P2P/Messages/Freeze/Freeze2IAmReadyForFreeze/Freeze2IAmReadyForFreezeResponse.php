<?php


namespace App\P2P\Messages\Freeze\Freeze2IAmReadyForFreeze;


use App\Models\PeerServer;
use App\P2P\Messages\P2PMessageResponse;

/**
 * Class Freeze2IAmReadyForFreezeResponse
 * @package App\P2P\Messages\Freeze\Freeze2IAmReadyForFreeze
 */
class Freeze2IAmReadyForFreezeResponse extends P2PMessageResponse
{


    public function __construct(PeerServer $requestDestination, PeerServer $requestSender)
    {
        parent::__construct($requestDestination, $requestSender);
    }

    // ########################################################################################

    /**
     * @return null[]
     */
    public function serialize(): array
    {
        return [];
    }

    /**
     * @param \App\Models\PeerServer $requestDestination
     * @param array $messageData
     * @param \App\P2P\Messages\Freeze\Freeze2IAmReadyForFreeze\Freeze2IAmReadyForFreezeResponse $requestMessage
     * @return static
     */
    public static function unserialize(PeerServer $requestDestination, array $messageData, $requestMessage): self
    {
        return new static($requestDestination, getCurrentServer());
    }

    // ########################################################################################

    /**
     * @param \App\Models\PeerServer $destPeerServer
     * @param $request
     * @throws \Exception
     */
    public function onResponseReceived(PeerServer $destPeerServer, $request): void
    {

    }

}
