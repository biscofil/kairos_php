<?php


namespace App\P2P\Messages\ThisIsMySecretKey;


use App\Models\PeerServer;
use App\P2P\Messages\P2PMessageResponse;

/**
 * Class ThisIsMySecretKeyResponse
 * @package App\P2P\Messages\ThisIsMySecretKey
 * @property \App\Voting\CryptoSystems\SecretKey secretKey
 * @property \App\Models\Election election
 */
class ThisIsMySecretKeyResponse extends P2PMessageResponse
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
        // nothing
    }
}
