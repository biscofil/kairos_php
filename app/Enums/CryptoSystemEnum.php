<?php

namespace App\Enums;

use App\Voting\CryptoSystems\CryptoSystem;
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

    /**
     * @return string|CryptoSystem
     */
    public function getCryptoSystemClass(): string
    {
        return CryptoSystem::getByIdentifier($this->value);
    }
}
