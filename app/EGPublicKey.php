<?php


namespace App;

use PHP\Math\BigInteger\BigInteger;

/**
 * Class EGPublicKey
 * @package App
 * @property BigInteger $g
 * @property BigInteger $p
 * @property BigInteger $q
 * @property BigInteger $y
 */
class EGPublicKey
{

    public $g;
    public $p;
    public $q;
    public $y;

    public function __construct(BigInteger $g, BigInteger $p, BigInteger $q, BigInteger $y)
    {
        $this->g = $g;
        $this->p = $p;
        $this->q = $q;
        $this->y = $y;
    }

    /**
     * @param \stdClass $data
     * @return EGPublicKey
     */
    public static function fromArray(\stdClass $data) : EGPublicKey{
        return new EGPublicKey(
            new BigInteger($data->g),
            new BigInteger($data->p),
            new BigInteger($data->q),
            new BigInteger($data->y)
        );
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            "g" => $this->g->getValue(),
            "p" => $this->p->getValue(),
            "q" => $this->q->getValue(),
            "y" => $this->y->getValue()
        ];
    }

}
