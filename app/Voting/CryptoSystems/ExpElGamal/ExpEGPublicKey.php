<?php


namespace App\Voting\CryptoSystems\ExpElGamal;


use App\Voting\CryptoSystems\ElGamal\EGPublicKey;
use phpseclib3\Math\BigInteger;

/**
 * Class ExpEGPublicKey
 * @package App\Voting\CryptoSystems\ExpElGamal
 * @method ExpEGCiphertext encrypt(ExpEGPlaintext $plainText)
 */
class ExpEGPublicKey extends EGPublicKey
{

    use BelongsToExpElgamal;

    /**
     * @param \phpseclib3\Math\BigInteger $m
     * @return \phpseclib3\Math\BigInteger
     * @noinspection PhpMissingParentCallCommonInspection
     * @see \App\Voting\CryptoSystems\ExpElGamal\ExpEGSecretKey::getMOnceFullyDecrypted()
     */
    public function getMToEncrypt(BigInteger $m): BigInteger
    {
        // no subgroup mapping, exponential operation instead
        return $this->parameterSet->g->powMod($m, $this->parameterSet->p);
    }

}
