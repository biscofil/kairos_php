<?php


namespace App\Voting\CryptoSystems;


use App\Models\Cast\Castable;
use phpseclib3\Math\BigInteger;

interface ThresholdPolynomial extends Castable, BelongsToCryptoSystem
{

    /**
     * @param array $data
     * @param bool $ignoreParameterSet
     * @param int $base
     * @return static
     */
    public static function fromArray(array $data, bool $ignoreParameterSet = false, int $base = 16): self;

    /**
     * @param bool $ignoreParameterSet
     * @return array
     */
    public function toArray(bool $ignoreParameterSet = false): array;

    // ############################################################################################################

    /**
     * @return ThresholdBroadcast
     */
    public function getBroadcast(): ThresholdBroadcast;

    /**
     * @param int $j
     * @return \phpseclib3\Math\BigInteger
     */
    public function getShare(int $j): BigInteger;

}
