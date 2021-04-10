<?php


namespace Tests\Unit\Voting\CryptoSystems\ElGamal;


use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
use App\Voting\CryptoSystems\ElGamal\EGThresholdPolynomial;

/**
 * Class Peer
 * @package Tests\Unit\Voting\CryptoSystems\ElGamal
 * @property int $id
 * @property EGThresholdPolynomial $f_i
 */
class Peer
{

    private int $id;
    private EGThresholdPolynomial $f_i;

    public function __construct(int $id, EGKeyPair $keyPair, int $t)
    {
        $this->id = $id;
        $this->f_i = $keyPair->generatePolynomial($t);
    }

}
