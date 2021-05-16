<?php


namespace App\Voting\CryptoSystems\ElGamal;


use App\Models\Election;
use App\Models\Trustee;
use App\Voting\CryptoSystems\CryptoSystem;
use App\Voting\CryptoSystems\SupportsReEncryption;

/**
 * Class ElGamal
 * @package App\Voting\CryptoSystems\ElGamal
 */
class ElGamal extends CryptoSystem implements SupportsReEncryption
{

    // #############################################################################
    // #############################################################################
    // #############################################################################

    /**
     * @return string|EGPublicKey
     */
    public static function getPublicKeyClass(): ?string
    {
        return EGPublicKey::class;
    }

    /**
     * @return string|EGPrivateKey
     */
    public static function getSecretKeyClass(): ?string
    {
        return EGPrivateKey::class;
    }

    /**
     * @return string|EGPlaintext
     */
    public static function getPlainTextClass(): ?string
    {
        return EGPlaintext::class;
    }

    /**
     * @return string|EGCiphertext
     */
    public static function getCipherTextClass(): ?string
    {
        return EGCiphertext::class;
    }

    /**
     * @return string|EGThresholdPolynomial
     */
    public static function getThresholdPolynomialClass(): ?string
    {
        return EGThresholdPolynomial::class;
    }

    /**
     * @return string|EGThresholdBroadcast
     */
    public static function getThresholdBroadcastClass(): ?string
    {
        return EGThresholdBroadcast::class;
    }

    // #############################################################################
    // #############################################################################
    // #############################################################################

    /**
     * @return EGKeyPair
     * @noinspection PhpMissingReturnTypeInspection
     */
    public static function generateKeypair() : EGKeyPair
    {
        return EGKeyPair::generate();
    }

    // #########################################################################

    /**
     * @param \App\Models\Election $election
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public static function onElectionFreeze(Election &$election): void
    {
        // TODO only if no threshold
        self::generateCombinedPublicKey($election);
    }

    /**
     * @param \App\Models\Election $election
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public static function afterAnonymizationProcessEnds(Election &$election): void
    {
        self::generateCombinedPrivateKey($election);
    }

    // #########################################################################

    /**
     * Returns a public key which is the combination (product) of the public keys of the trustees
     * @param Election $election
     * @return void
     */
    public static function generateCombinedPublicKey(Election &$election): void
    {
        $election->public_key = $election->trustees()->get()->reduce(function (?EGPublicKey $carry, Trustee $trustee): EGPublicKey {
            return $trustee->public_key->combine($carry);
        });
    }

    /**
     * @param Election $election
     * @return void
     */
    public static function generateCombinedPrivateKey(Election &$election): void
    {
        /** @var EGPrivateKey $out */
        $election->private_key = $election->trustees()->get()->reduce(function (?EGPrivateKey $carry, Trustee $trustee): EGPrivateKey {
            return $trustee->private_key->combine($carry);
        });
    }

}
