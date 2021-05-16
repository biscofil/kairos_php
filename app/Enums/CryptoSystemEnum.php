<?php

namespace App\Enums;

use App\Voting\CryptoSystems\CryptoSystem;
use BenSampo\Enum\Enum;

/**
 * @method static static RSA()
 * @method static static ElGamal()
 */
final class CryptoSystemEnum extends Enum
{
    public const RSA = 'rsa';
    public const ElGamal = 'eg';

    /**
     * @return string|CryptoSystem
     */
    public function getCryptoSystemClass(): string
    {
        return CryptoSystem::getByIdentifier($this->value);
    }
}
