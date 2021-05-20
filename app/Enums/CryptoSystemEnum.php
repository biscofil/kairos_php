<?php

namespace App\Enums;

use App\Voting\CryptoSystems\BelongsToCryptoSystem;
use App\Voting\CryptoSystems\CryptoSystem;
use App\Voting\CryptoSystems\ElGamal\ElGamal;
use App\Voting\CryptoSystems\ExpElGamal\ExpElGamal;
use App\Voting\CryptoSystems\RSA\RSA;
use BenSampo\Enum\Enum;

/**
 * @method static static RSA()
 * @method static static ElGamal()
 * @method static static ExponentialElGamal()
 */
final class CryptoSystemEnum extends Enum
{
    public const RSA = 'rsa';
    public const ElGamal = 'eg';
    public const ExponentialElGamal = 'exp_eg';

    public const CRYPTOSYSTEMS = [
        CryptoSystemEnum::ElGamal => ElGamal::class,
        CryptoSystemEnum::ExponentialElGamal => ExpElGamal::class,
        CryptoSystemEnum::RSA => RSA::class
    ];

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

    /**
     * @param string $cryptoSystemIdentifier
     * @return string|\App\Voting\CryptoSystems\CryptoSystem
     */
    public static function getByIdentifier(string $cryptoSystemIdentifier): string
    {
        if (!array_key_exists($cryptoSystemIdentifier, self::CRYPTOSYSTEMS)) {
            throw new \RuntimeException('Invalid cryptosystem ' . $cryptoSystemIdentifier);
        }
        return self::CRYPTOSYSTEMS[$cryptoSystemIdentifier]; // ElGamal::class, RSA::class, ...
    }

    /**
     * @return string|CryptoSystem
     */
    public function getCryptoSystemClass(): string
    {
        return self::getByIdentifier($this->value);
    }
}
