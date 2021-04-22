<?php


namespace App\P2P\Tasks;


use App\Models\PeerServer;

/**
 * Class GenerateShadowMixes
 * @package App\P2P\Tasks
 * @property string $challengeBits
 */
class GenerateShadowMixes extends Task
{

    public string $challengeBits;

    /**
     * WaitAndRespond constructor.
     * @param PeerServer $from
     * @param PeerServer[] $to
     * @param string $challengeBits
     * @throws \Exception
     */
    public function __construct(PeerServer $from, array $to, string $challengeBits)
    {
        parent::__construct($from, $to);
        $this->challengeBits = $challengeBits;
    }

    /**
     *
     */
    public function run()
    {
        // TODO honest-verifier zero knowledge
//        sleep($this->m);
//        (new TakeBackN($this->n, $this->m, $this->from, $this->to))->sendSync();
    }
}
