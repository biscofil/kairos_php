<?php


namespace App\P2P\Tasks;

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
     * @throws \Exception
     */
    public function __construct(EGThresholdBroadcast $broadcast)
    {
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
