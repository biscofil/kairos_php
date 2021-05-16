<?php


namespace App\Models\Cast;

use App\Voting\CryptoSystems\CryptoSystem;

/**
 * Class PublicKeyCaster
 * @package App\Models\Cast
 */
class PublicKeyCaster extends DynamicCryptosystemClassCaster
{

    /**
     * Specify the name of the constant of a cryptosystem class (RSA/Elgamal) that contains the name of the class
     * we want to cast the value to
     * @param string|CryptoSystem $cs
     * @return string|\App\Voting\CryptoSystems\PublicKey
     */
    public function getTargetClassConstantName(string $cs): string
    {
        return $cs::getPublicKeyClass();
    }

}
