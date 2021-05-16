<?php


namespace App\P2P\Tasks;


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
     * @param string $challengeBits
     * @throws \Exception
     */
    public function __construct(string $challengeBits)
    {
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
