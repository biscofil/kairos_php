<?php


namespace App\P2P\Messages\ThisIsMyMixSet;


use App\Models\PeerServer;
use App\P2P\Messages\P2PMessageResponse;

class ThisIsMyMixSetResponse extends P2PMessageResponse
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

    /**
     * We parse the public key and we assign it to the trustee
     * @param \App\Models\PeerServer $destPeerServer
     * @param \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response $response
     * @throws \Exception
     */
    protected function _onResponseReceived(PeerServer $destPeerServer, $response): void
    {

//        $response = Validator::make($response->json(), [
//            'challenge_bits' => ['required', 'string']
//        ])->validate();
//
//        Log::error("Challenge bits received: " . $response['challenge_bits']);
//
//        // TODO generate proof in task
//
//        $election = Election::findOrFail($this->mixSet['id']);
//
//        RunP2PTask::dispatch(new GenerateShadowMixes(
//            $this->from,
//            [$destPeerServer],
//            $response['challenge_bits']
//        ));

        // TODO store received file

    }

}
