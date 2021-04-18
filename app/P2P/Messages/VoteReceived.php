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

        Log::debug("VoteReceived message received");

        // TODO

        return new JsonResponse([]);

    }

}
