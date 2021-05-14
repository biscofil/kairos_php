<?php


namespace App\Voting\CryptoSystems;


use phpseclib3\Math\BigInteger;

interface ThresholdPolynomial
{

    /**
     * @return ThresholdBroadcast
     */
    public function getBroadcast(): ThresholdBroadcast;

    /**
     * @param int $j
     * @return \phpseclib3\Math\BigInteger
     */
    public function getShare(int $j) : BigInteger;

}
