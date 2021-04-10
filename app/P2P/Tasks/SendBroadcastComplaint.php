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
     * @param string $from
     * @param string[] $to
     * @throws \Exception
     */
    public function __construct(EGThresholdBroadcast $broadcast, string $from, array $to)
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
