<?php


namespace App\Voting\CryptoSystems\ElGamal;


use App\Models\Election;
use App\Models\Trustee;
use App\Voting\CryptoSystems\CryptoSystem;
use App\Voting\CryptoSystems\SupportsTLThresholdEncryption;

/**
 * Class ElGamal
 * @package App\Voting\CryptoSystems\ElGamal
 */
class ElGamal implements CryptoSystem, SupportsTLThresholdEncryption
{

    /**
     * @return string|EGPublicKey
     */
    public static function getPublicKeyClass(): string
    {
        return EGPublicKey::class;
    }

    /**
     * @return string|EGSecretKey
     */
    public static function getSecretKeyClass(): string
    {
        return EGSecretKey::class;
    }

    /**
     * @return string|EGKeyPair
     */
    public static function getKeyPairClass(): string
    {
        return EGKeyPair::class;
    }

    /**
     * @return string|EGPlaintext
     */
    public static function getPlainTextClass(): string
    {
        return EGPlaintext::class;
    }

    /**
     * @return string|EGCiphertext
     */
    public static function getCipherTextClass(): string
    {
        return EGCiphertext::class;
    }

    /**
     * @return string|EGThresholdPolynomial
     */
    public static function getThresholdPolynomialClass(): string
    {
        return EGThresholdPolynomial::class;
    }

    /**
     * @return string|EGThresholdBroadcast
     */
    public static function getThresholdBroadcastClass(): string
    {
        return EGThresholdBroadcast::class;
    }

    /**
     * @return string|EGParameterSet
     */
    public static function getParameterSetClass(): string
    {
        return EGParameterSet::class;
    }

    // #########################################################################

    /**
     * @param \App\Models\Election $election
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public static function onElectionFreeze(Election &$election): void
    {
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
     * @throws \Exception
     */
    public static function generateCombinedPublicKey(Election &$election): void
    {
        $election->public_key = $election->trustees()->get()->reduce(function (?EGPublicKey $carry, Trustee $trustee): EGPublicKey {
            if (is_null($trustee->public_key)) {
                throw new \Exception("Public key of trustee $trustee->id is null!");
            }
            return $trustee->public_key->combine($carry);
        });
    }

    /**
     * @param Election $election
     * @return void
     * @throws \Exception
     */
    public static function generateCombinedPrivateKey(Election &$election): void
    {
        $election->private_key = $election->trustees()->get()->reduce(function (?EGSecretKey $carry, Trustee $trustee): EGSecretKey {
            if (is_null($trustee->private_key)) {
                throw new \Exception("Private key of trustee $trustee->id is null!");
            }
            return $trustee->private_key->combine($carry);
        });
    }

}
