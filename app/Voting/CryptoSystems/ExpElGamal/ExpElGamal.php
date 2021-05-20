<?php


namespace App\Voting\CryptoSystems\ExpElGamal;


use App\Models\Election;
use App\Voting\CryptoSystems\CryptoSystem;
use App\Voting\CryptoSystems\KeyPair;

/**
 * Class ExpElGamal
 * @package App\Voting\CryptoSystems\ExpElGamal
 */
class ExpElGamal extends CryptoSystem
{

    /**
     * @return string|ExpEGPublicKey
     */
    public static function getPublicKeyClass(): ?string
    {
        return ExpEGPublicKey::class;
    }

    /**
     * @return string|ExpEGSecretKey
     */
    public static function getSecretKeyClass(): ?string
    {
        return ExpEGSecretKey::class;
    }

    /**
     * @return string|ExpEGKeyPair
     */
    public static function getKeyPairClass(): ?string
    {
        return ExpEGKeyPair::class;
    }

    /**
     * @return string|ExpEGPlaintext
     */
    public static function getPlainTextClass(): ?string
    {
        return ExpEGPlaintext::class;
    }

    /**
     * @return string|ExpEGCiphertext
     */
    public static function getCipherTextClass(): ?string
    {
        return ExpEGCiphertext::class;
    }

    /**
     * @return string|ExpEGThresholdPolynomial
     */
    public static function getThresholdPolynomialClass(): ?string
    {
        return ExpEGThresholdPolynomial::class;
    }

    /**
     * @return string|ExpEGThresholdBroadcast
     */
    public static function getThresholdBroadcastClass(): ?string
    {
        return ExpEGThresholdBroadcast::class;
    }

    /**
     * @return string|ExpEGParameterSet
     */
    public static function getParameterSetClass(): ?string
    {
        return ExpEGParameterSet::class;
    }

    public static function onElectionFreeze(Election &$election): void
    {
        // TODO: Implement onElectionFreeze() method.
    }
}
