<?php


namespace App\Voting\CryptoSystems\ExpElGamal;


use App\Voting\CryptoSystems\ElGamal\EGSecretKey;
use phpseclib3\Math\BigInteger;

/**
 * Class ExpEGSecretKey
 * @package App\Voting\CryptoSystems\ExpElGamal
 */
class ExpEGSecretKey extends EGSecretKey
{

    use BelongsToExpElgamal;

    /**
     * Do not reverse mapping, resolve DLOG
     * @param \phpseclib3\Math\BigInteger $m
     * @return \phpseclib3\Math\BigInteger
     * @noinspection PhpMissingParentCallCommonInspection
     * @see \App\Voting\CryptoSystems\ExpElGamal\ExpEGPublicKey::getMToEncrypt()
     */
    public function getMOnceFullyDecrypted(BigInteger $m): BigInteger
    {
        // TODO DLOG -> reverse g->powMod($m, $this->parameterSet->p); to extract m
        //  https://en.wikipedia.org/wiki/Pohlig%E2%80%93Hellman_algorithm
        return $m;
    }

}
