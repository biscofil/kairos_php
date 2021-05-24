<?php


namespace App\Voting\CryptoSystems\ExpElGamal;


use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
use App\Voting\CryptoSystems\ElGamal\EGParameterSet;
use App\Voting\CryptoSystems\ElGamal\EGPublicKey;
use App\Voting\CryptoSystems\ElGamal\EGSecretKey;

/**
 * Class ExpEGKeyPair
 * @package App\Voting\CryptoSystems\ExpElGamal
 * @property ExpEGPublicKey pk
 * @property ExpEGSecretKey sk
 * @method static ExpEGKeyPair generate()
 */
class ExpEGKeyPair extends EGKeyPair
{

    use BelongsToExpElgamal;

    /**
     * ExpEGKeyPair constructor.
     * @param \App\Voting\CryptoSystems\ExpElGamal\ExpEGPublicKey $pk
     * @param \App\Voting\CryptoSystems\ExpElGamal\ExpEGSecretKey $sk
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(ExpEGPublicKey $pk, ExpEGSecretKey $sk)
    {
        $this->pk = $pk;
        $this->sk = $sk;
    }

}
