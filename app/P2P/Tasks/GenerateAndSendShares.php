<?php


namespace App\P2P\Tasks;


use App\Jobs\SendP2PMessage;
use App\Models\Election;
use App\Models\PeerServer;
use App\P2P\Messages\Freeze\ThisIsMyThresholdBroadcast;
use Illuminate\Support\Facades\Log;

/**
 * Class GenerateAndSendShares
 * @package App\P2P\Tasks
 * @property Election $election
 */
class GenerateAndSendShares extends Task
{

    public Election $election;

    /**
     * GenerateAndSendShares constructor.
     * @param \App\Models\Election $election
     * @throws \Exception
     */
    public function __construct(Election $election)
    {
        $this->election = $election;
    }

    /**
     * @throws \Exception
     */
    public function run()
    {

        Log::debug('RUNNING GenerateAndSendShares');

        $peerServerTrustees = $this->election->trustees->keyBy('peer_server_id');

        /** @var \App\Models\Trustee|null $meTrustee */
        $meTrustee = $peerServerTrustees->get(PeerServer::meID);
        // This in invoked once the first message of the three phase freeze commit is received
        // thus the server running this code is a trustee
        if (is_null($meTrustee)) {
            throw new \Exception('This server is not a trustee of election ' . $this->election->uuid);
        }

        /**
         * the keypair and the polynomial are generated
         * in @see \App\P2P\Messages\Freeze\Freeze1IAmFreezingElection\Freeze1IAmFreezingElectionRequest::onRequestReceived()
         */

        $peerServers = $this->election->peerServers()->get();
        $sortedDomains = $peerServers->pluck('domain')->flip()->toArray();

        Log::debug('GenerateAndSendShares > ' . count($peerServers) . ' peer servers');

        $peerServers->each(function (PeerServer $server) use ($meTrustee, $peerServerTrustees, $sortedDomains) {

            if ($server->id === PeerServer::meID // ignore self
                || $server->id === $this->election->peer_server_id // ignore creator
                || $server->domain < PeerServer::me()->domain) {// ignore those with lower key
                return;
            }

            $j = $sortedDomains[$server->domain] + 1; // TODO use Trustee::getIndex

            /** @var \App\Models\Trustee $trustee */
            $trustee = $peerServerTrustees->get($server->id);

            $share = $meTrustee->polynomial->getShare($j);
            // store share we are about to send in DB
            $trustee->share_sent = $share;
            $trustee->save();

            SendP2PMessage::dispatchSync(
                new ThisIsMyThresholdBroadcast\ThisIsMyThresholdBroadcastRequest(
                    PeerServer::me(),
                    $server,
                    $this->election,
                    $meTrustee->public_key,
                    $meTrustee->broadcast,
                    $share
                )
            );

        });

        Log::debug('GenerateAndSendShares > DONE');

    }

}
