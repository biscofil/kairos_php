<?php


namespace App\P2P\Messages\WillYouBeAElectionTrusteeForMyElection;


use App\Models\PeerServer;
use App\P2P\Messages\P2PMessageResponse;
use Illuminate\Support\Facades\Log;

/**
 * Class WillYouBeAElectionTrusteeForMyElectionResponse
 * @package App\P2P\Messages\WillYouBeAElectionTrusteeForMyElection
 */
class WillYouBeAElectionTrusteeForMyElectionResponse extends P2PMessageResponse
{

    /**
     * WillYouBeAElectionTrusteeForMyElectionResponse constructor.
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
        return new static($requestDestination, PeerServer::me());
    }

    /**
     * We parse the public key and we assign it to the trustee
     * @param \App\Models\PeerServer $destPeerServer
     * @param \App\P2P\Messages\WillYouBeAElectionTrusteeForMyElection\WillYouBeAElectionTrusteeForMyElectionRequest $request
     */
    public function onResponseReceived(PeerServer $destPeerServer, $request): void
    {

        Log::debug($this->rawHttpResponse->json());

        $trustee = $request->election->getTrusteeFromPeerServer($this->requestSender);

        if ($trustee) {

            if (!$this->rawHttpResponse->ok()) {
                $trustee->delete();
                Log::info("Server {$this->requestSender->name} deleted as trustee");
                return;
            }

            Log::info("Server {$this->requestSender->name} added as trustee");

        } else {

            Log::error("Server {$this->requestSender->name} NOT added as trustee");

        }

    }
}
