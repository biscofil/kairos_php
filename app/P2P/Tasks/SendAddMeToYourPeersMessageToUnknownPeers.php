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
        $messagesToSend = $this->election->peerServers()->unknown()->get()->each(function (PeerServer $server) {
            return new AddMeToYourPeers\AddMeToYourPeersRequest(
                getCurrentServer(),
                $server,
                getCurrentServer()->jwt_public_key,
                $server->getNewToken()
            );
        });
        SendP2PMessage::dispatchSync($messagesToSend->toArray());

        Log::debug('SendAddMeToYourPeersMessageToUnknownPeers > DONE');

    }
}
