<?php


namespace App\P2P\Messages;


use App\Models\PeerServer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Class AddMeToYourPeers
 * @package App\P2P\Messages
 */
class AddMeToYourPeers extends P2PMessage
{

    public const name = 'add_me_to_your_peers';

    /**
     * @return JsonResponse
     */
    public function onRequestReceived(): JsonResponse
    {

        $host = $this->from;

        Log::debug("AddMeToYourPeers message received");

        if (PeerServer::query()->where('ip', '=', $host)->count() == 0) {

            $peer = new PeerServer();
            // TODO resolve domain, store both IP and host
            $peer->ip = $host;
            $peer->name = "server " . $host;
            $peer->save();

            Log::debug("Host $host added as peer");
        }

        return new JsonResponse([]);

    }

}
