<?php


namespace App\P2P\Tasks;


/**
 * Class Task
 * @package App\P2P\Tasks
 * @property string $from
 * @property string[] $to
 */
abstract class Task
{
    public string $from;
    public array $to;

    /**
     * P2PMessage constructor.
     * @param string $from
     * @param string[] $to
     * @throws \Exception
     */
    public function __construct(string $from, array $to)
    {
        $this->from = extractDomain($from);
        $this->to = array_map(function (string $to) {
            return extractDomain($to);
        }, $to);
    }

    public abstract function run();
}
