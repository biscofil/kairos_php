<?php


namespace App\P2P\Tasks;


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

        /** @var \Illuminate\Support\Collection|\App\Models\Trustee[] $trustees */
        $trustees = $this->election->trustees->keyBy('peer_server_id');

        /** @var \App\Models\Trustee $meTrustee */
        $meTrustee = $trustees->get(PeerServer::meID); // TODO what if same server is not a trustee?

        /**
         * the keypair and the polynomial are generated
         * in @see \App\P2P\Messages\Freeze\Freeze1IAmFreezingElection::getRequestResponse()
         */

        $meTrustee->save();

        $peerServers = $this->election->peerServers()->get();
        $sortedDomains = $peerServers->pluck('domain')->flip()->toArray();

        Log::debug('GenerateAndSendShares > ' . count($peerServers) . ' peer servers');

        $peerServers->each(function (PeerServer $server) use ($meTrustee, $trustees, $sortedDomains) {

            if ($server->id === PeerServer::meID // ignore self
                || $server->id === $this->election->peer_server_id // ignore creator
                || $server->domain < PeerServer::me()->domain) {// ignore those with lower key
                return;
            }

            $j = $sortedDomains[$server->domain] + 1; // TODO use Trustee::getIndex

            /** @var \App\Models\Trustee $trustee */
            $trustee = $trustees->get($server->id);

            $share = $meTrustee->polynomial->getShare($j);
            // store share we are about to send in DB
            $trustee->share_sent = $share;
            $trustee->save();

            (new ThisIsMyThresholdBroadcast(
                PeerServer::me(),
                [$server],
                $this->election,
                $meTrustee->broadcast,
                $share
            ))->sendSync();

        });

        Log::debug('GenerateAndSendShares > DONE');

    }

}
