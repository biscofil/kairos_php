<?php


namespace App\P2P\Tasks;


use App\Models\Election;
use App\Models\PeerServer;
use App\P2P\Messages\AddMeToYourPeers;

/**
 * Class PostFreezeHandshakes
 * @package App\P2P\Tasks
 * @property Election $election
 */
class PostFreezeHandshakes extends Task
{

    public Election $election;

    /**
     * @param PeerServer $from
     * @param PeerServer[] $to
     * @param \App\Models\Election $election
     * @throws \Exception
     */
    public function __construct(PeerServer $from, array $to, Election $election)
    {
        parent::__construct($from, $to);
        $this->election = $election;
    }

    /**
     *
     */
    public function run()
    {
        $this->election->peerServers()->unknown()->each(function (PeerServer $server) {
            (new AddMeToYourPeers(
                PeerServer::me(),
                [$server],
                getJwtRSAKeyPair()->pk,
                $server->getNewToken()
            ))->sendSync();
        });
    }
}
