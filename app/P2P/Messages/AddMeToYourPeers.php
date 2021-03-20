<?php


namespace App\P2P\Messages;


use App\Models\PeerServer;
use Illuminate\Support\Facades\Log;

/**
 * Class AddMeToYourPeers
 * @package App\P2P\Messages
 */
class AddMeToYourPeers extends P2PMessage
{

    public const name = 'add_me_to_your_peers';

    /**
     * @return array
     */
    public function onRequestReceived(): array
    {

        $host = $this->from;

        Log::debug("AddMeToYourPeers message received");

        if (PeerServer::query()->where('ip', '=', $host)->count() == 0) {

            $peer = new PeerServer();
            $peer->ip = $host;
            $peer->name = "server " . $host;
            $peer->save();

            Log::debug("Host $host added as peer");
        }

        return [];

    }

}
