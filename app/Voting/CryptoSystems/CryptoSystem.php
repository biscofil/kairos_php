<?php


namespace App\Voting\CryptoSystems;

use App\Enums\CryptoSystemEnum;
use App\Models\Election;
use App\Voting\CryptoSystems\ElGamal\ElGamal;
use App\Voting\CryptoSystems\ExpElGamal\ExpElGamal;
use App\Voting\CryptoSystems\RSA\RSA;

/**
 * Class CryptoSystem
 * @package App\Voting\CryptoSystems
 */
abstract class CryptoSystem
{

    public const CRYPTOSYSTEMS = [
        CryptoSystemEnum::ElGamal => ElGamal::class,
        CryptoSystemEnum::ExponentialElGamal => ExpElGamal::class,
        CryptoSystemEnum::RSA => RSA::class
    ];

    // #############################################################################

    /**
     * @return string|null|\App\Voting\CryptoSystems\PublicKey
     */
    abstract public static function getPublicKeyClass() : ?string;

    /**
     * @return string|null|\App\Voting\CryptoSystems\SecretKey
     */
    abstract public static function getSecretKeyClass(): ?string;

    /**
     * @return string|null|\App\Voting\CryptoSystems\KeyPair
     */
    abstract public static function getKeyPairClass(): ?string;

    /**
     * @return string|null|\App\Voting\CryptoSystems\Plaintext
     */
    abstract public static function getPlainTextClass(): ?string;

    /**
     * @return string|null|\App\Voting\CryptoSystems\CipherText
     */
    abstract public static function getCipherTextClass(): ?string;

    /**
     * @return string|null|\App\Voting\CryptoSystems\ThresholdPolynomial
     */
    abstract public static function getThresholdPolynomialClass(): ?string;

    /**
     * @return string|null|\App\Voting\CryptoSystems\ThresholdBroadcast
     */
    abstract public static function getThresholdBroadcastClass(): ?string;

    /**
     * @return string|null|\App\Voting\CryptoSystems\ParameterSet
     */
    abstract public static function getParameterSetClass(): ?string;

    // #############################################################################

    /**
     * @param string $cryptoSystemIdentifier
     * @return string|\App\Voting\CryptoSystems\CryptoSystem
     */
    public static function getByIdentifier(string $cryptoSystemIdentifier): string
    {
        if (!array_key_exists($cryptoSystemIdentifier, self::CRYPTOSYSTEMS)) {
            throw new \RuntimeException('Invalid cryptosystem ' . $cryptoSystemIdentifier);
        }
        return CryptoSystem::CRYPTOSYSTEMS[$cryptoSystemIdentifier]; // ElGamal::class, RSA::class, ...
    }

    /**
     * @param \App\Voting\CryptoSystems\BelongsToCryptoSystem $obj
     * @return mixed
     */
    public static function getIdentifier(BelongsToCryptoSystem $obj): string
    {
        $v = array_flip(self::CRYPTOSYSTEMS); // [ ElGamal::class => 'eg', ... ]
        $key = $obj::getCryptosystem();
        if (!array_key_exists($key, $v)) {
            throw new \RuntimeException('unknown cryptosystem ' . $key);
        }
        return $v[$key];
    }

    // #############################################################################

    /**
     * @param Election $election
     */
    abstract public static function onElectionFreeze(Election &$election): void;

    /**
     * @param Election $election
     */
    public static function afterAnonymizationProcessEnds(Election &$election): void
    {
        // do nothing, Example: RSA
    }

}
