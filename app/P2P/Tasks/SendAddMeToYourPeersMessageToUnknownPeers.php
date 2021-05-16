<?php


namespace App\P2P\Tasks;


use App\Jobs\SendP2PMessage;
use App\Models\Election;
use App\Models\PeerServer;
use App\P2P\Messages\AddMeToYourPeers;
use Illuminate\Support\Facades\Log;

/**
 * Class PostFreezeHandshakes
 * @package App\P2P\Tasks
 * @property Election $election
 */
class SendAddMeToYourPeersMessageToUnknownPeers extends Task
{

    public Election $election;

    /**
     * @param \App\Models\Election $election
     * @throws \Exception
     */
    public function __construct(Election $election)
    {
        $this->election = $election;
    }

    /**
     *
     */
    public function run()
    {
        Log::debug('Running SendAddMeToYourPeersMessageToUnknownPeers task');

        // add missing peers
        $this->election->peerServers()->unknown()->each(function (PeerServer $server) {

            SendP2PMessage::dispatchSync(
                new AddMeToYourPeers\AddMeToYourPeersRequest(
                    PeerServer::me(),
                    $server,
                    PeerServer::me()->jwt_public_key,
                    $server->getNewToken()
                )
            );

        });

        Log::debug('SendAddMeToYourPeersMessageToUnknownPeers > DONE');

    }
}
