<?php


namespace App\Voting\CryptoSystems\RSA;


use App\Voting\CryptoSystems\CryptoSystem;

/**
 * Class RSA
 * @package App\Voting\CryptoSystems\RSA
 */
class RSA extends CryptoSystem
{

    // #############################################################################
    // #############################################################################
    // #############################################################################

    /**
     * @return string|\App\Voting\CryptoSystems\RSA\RSAPublicKey
     */
    public static function getPublicKeyClass(): ?string
    {
        return RSAPublicKey::class;
    }

    /**
     * @return string|\App\Voting\CryptoSystems\RSA\RSASecretKey
     */
    public static function getSecretKeyClass(): ?string
    {
        return RSASecretKey::class;
    }

    /**
     * @return string|\App\Voting\CryptoSystems\RSA\RSAPlaintext
     */
    public static function getPlainTextClass(): ?string
    {
        return RSAPlaintext::class;
    }

    /**
     * @return string|\App\Voting\CryptoSystems\RSA\RSACiphertext
     */
    public static function getCipherTextClass(): ?string
    {
        return RSACiphertext::class;
    }

    /**
     * @return null
     */
    public static function getThresholdPolynomialClass(): ?string
    {
        return null;
    }

    /**
     * @return null
     */
    public static function getThresholdBroadcastClass(): ?string
    {
        return null;
    }

    // #############################################################################
    // #############################################################################
    // #############################################################################

    /**
     * @return RSAKeyPair
     * @noinspection PhpMissingReturnTypeInspection
     */
    public static function generateKeypair() : RSAKeyPair
    {
        return RSAKeyPair::generate();
    }

}
