<?php


namespace App\Voting\CryptoSystems\RSA;


use App\Models\Election;
use App\Voting\CryptoSystems\CryptoSystem;

/**
 * Class RSA
 * @package App\Voting\CryptoSystems\RSA
 */
class RSA implements CryptoSystem
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
     * @return string|RSAKeyPair
     */
    public static function getKeyPairClass(): ?string
    {
        return RSAKeyPair::class;
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

    /**
     * @return string|RSAParameterSet
     */
    public static function getParameterSetClass(): ?string
    {
        return RSAParameterSet::class;
    }

    // #############################################################################

    /**
     * @param \App\Models\Election $election
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public static function afterAnonymizationProcessEnds(Election &$election): void
    {
    }

    /**
     * @param \App\Models\Election $election
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public static function onElectionFreeze(Election &$election): void
    {
        // TODO self::generateCombinedPublicKey($election);
    }

}
