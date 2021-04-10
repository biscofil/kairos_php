<?php


namespace App\P2P\Messages;


use App\Models\PeerServer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Class VoteReceived
 * @package App\P2P\Messages
 */
class VoteReceived extends P2PMessage
{

    public const name = 'vote_received';

    /**
     * @return JsonResponse
     */
    public function onRequestReceived(): JsonResponse
    {

        $host = $this->from;

        Log::debug("VoteReceived message received");

        if (PeerServer::query()->where('ip', '=', $host)->count() == 0) {

            $peer = new PeerServer();
            $peer->ip = $host;
            $peer->name = "server " . $host;
            $peer->save();

            Log::debug("Host $host added as peer");
        }

        return new JsonResponse([]);

    }

}
