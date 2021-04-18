<?php


namespace App\P2P\Tasks;

use App\Models\PeerServer;
use App\Voting\CryptoSystems\ElGamal\EGThresholdBroadcast;

/**
 * Class SendBroadcastComplaint
 * @package App\P2P\Tasks
 * @property EGThresholdBroadcast $broadcast
 */
class SendBroadcastComplaint extends Task
{

    /**
     * VerifyBroadcast constructor.
     * @param EGThresholdBroadcast $broadcast
     * @param PeerServer $from
     * @param PeerServer[] $to
     * @throws \Exception
     */
    public function __construct(EGThresholdBroadcast $broadcast, PeerServer $from, array $to)
    {
        parent::__construct($from, $to);
        $this->broadcast = $broadcast;
    }

    /**
     *
     */
    public function run()
    {
        // TODO SendBroadcastComplaint task
        // TODO send to all but original sender
    }
}
