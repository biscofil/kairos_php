<?php


namespace App\P2P\Tasks;


use App\Models\PeerServer;

/**
 * Class Task
 * @package App\P2P\Tasks
 * @property PeerServer $from
 * @property PeerServer[] $to
 */
abstract class Task
{
    public PeerServer $from;
    public array $to;

    /**
     * P2PMessage constructor.
     * @param PeerServer $from
     * @param PeerServer[] $to
     * @throws \Exception
     */
    public function __construct(PeerServer $from, array $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    public abstract function run();
}
