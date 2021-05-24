<?php


namespace App\Voting\CryptoSystems;


/**
 * Interface SupportsTLThresholdEncryption
 * @package App\Voting\CryptoSystems
 */
interface SupportsTLThresholdEncryption
{

    /**
     * @return string|\App\Voting\CryptoSystems\ThresholdPolynomial
     */
    public static function getThresholdPolynomialClass(): string;

    /**
     * @return string|\App\Voting\CryptoSystems\ThresholdBroadcast
     */
    public static function getThresholdBroadcastClass(): string;

}
