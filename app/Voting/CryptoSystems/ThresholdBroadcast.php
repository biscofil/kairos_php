<?php


namespace App\Voting\CryptoSystems;


use App\Models\Cast\Castable;
use phpseclib3\Math\BigInteger;

interface ThresholdBroadcast extends Castable, BelongsToCryptoSystem
{

    /**
     * @param array $data
     * @param bool $ignoreParameterSet
     * @param int $base
     * @return static
     */
    public static function fromArray(array $data, bool $ignoreParameterSet = false, int $base = 16) : self;

    /**
     * @param bool $ignoreParameterSet
     * @return array
     */
    public function toArray(bool $ignoreParameterSet = false): array;

    // ############################################################################################################

    /**
     * @param BigInteger $share_i_j
     * @param int $j
     * @return bool
     */
    public function isValid(BigInteger $share_i_j, int $j): bool;

    /**
     * @return string
     */
    public function toString(): string;

    /**
     * @param ThresholdBroadcast $broadcast
     * @return bool
     */
    public function equals($broadcast): bool;

}
