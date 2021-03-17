<?php


namespace App\P2P\Tasks;


use App\P2P\Messages\TakeBackN;

/**
 * Class WaitAndRespond
 * @package App\P2P\Tasks
 * @property string $from
 * @property string $to
 * @property int $n
 * @property int $m
 */
class WaitAndRespond extends Task
{

    private $n;
    private $m;
    private $from;
    private $to;

    public function __construct(int $n, int $m, string $from, string $to)
    {
        $this->from = $from;
        $this->to = $to;
        $this->n = $n;
        $this->m = $m;
    }
    public function run()
    {
        sleep($this->m);
        (new TakeBackN($this->n, $this->m, $this->from, $this->to))->sendSync();
    }
}
