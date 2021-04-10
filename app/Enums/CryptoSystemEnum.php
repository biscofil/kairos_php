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
    const RSA = 'rsa';
    const ElGamal = 'eg';

    /**
     * @return CryptoSystem
     */
    public function getCryptoSystemClass(): CryptoSystem
    {
        /** @var CryptoSystem $className */
        $className = (CryptoSystem::getByIdentifier($this->value));
        return $className::getInstance();
    }
}
