<?php

namespace App\Voting\CryptoSystems\ElGamal;

use phpseclib3\Math\BigInteger;

/**
 * TODO
 * Class Commitment
 * @package App\Voting\CryptoSystems\ElGamal;
 * @property BigInteger a
 * @property BigInteger b
 */
class Commitment
{

    public BigInteger $a;
    public BigInteger $b;

    public function __construct(BigInteger $a, BigInteger $b)
    {
        $this->a = $a;
        $this->b = $b;
    }

    /**
     * @return BigInteger
     */
    public function DLogChallengeGenerator(): BigInteger
    {
        $array_to_hash = [];
        $array_to_hash[] = $this->a->toString(); // TODO or hex?
        $array_to_hash[] = $this->b->toString(); // TODO or hex?
        $string_to_hash = implode(',', $array_to_hash);
        // compute sha1 of the commitment
        return BI(sha1(utf8_encode($string_to_hash)), 16);
    }

}
