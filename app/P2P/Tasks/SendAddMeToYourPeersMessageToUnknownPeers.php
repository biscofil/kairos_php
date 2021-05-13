<?php


namespace App\P2P\Tasks;


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
            (new AddMeToYourPeers(
                PeerServer::me(),
                $server,
                getJwtRSAKeyPair()->pk,
                $server->getNewToken()
            ))->sendSync();
        });

        Log::debug('SendAddMeToYourPeersMessageToUnknownPeers > DONE');

    }
}
