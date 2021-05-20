<?php


namespace App\Voting\CryptoSystems\ExpElGamal;


use App\Voting\CryptoSystems\ElGamal\EGParameterSet;
use phpseclib3\Math\BigInteger;

/**
 * Class ExpEGParameterSet
 * @package App\Voting\CryptoSystems\ExpElGamal
 */
class ExpEGParameterSet extends EGParameterSet
{

    use BelongsToExpElgamal;

    // ############################################################

    /**
     * @param \phpseclib3\Math\BigInteger $m
     * @return \phpseclib3\Math\BigInteger
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function mapMessageIntoSubgroup(BigInteger $m): BigInteger
    {
        return $m;
    }

    /**
     * @param \phpseclib3\Math\BigInteger $m
     * @return \phpseclib3\Math\BigInteger
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function extractMessageFromSubgroup(BigInteger $m): BigInteger
    {
        return $m;
    }

    // ############################################################

}
