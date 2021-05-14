<?php


namespace App\Models\Cast;


/**
 * Class ThresholdPolynomialCasterCryptosystem
 * @package App\Models\Cast
 */
class ThresholdPolynomialCasterCryptosystem extends DynamicCryptosystemClassCaster
{

    /**
     * Specify the name of the constant of a cryptosystem class (RSA/Elgamal) that contains the name of the class
     * we want to cast the value to
     * @return string
     * @see \App\Voting\CryptoSystems\CryptoSystem::ThresholdPolynomialClass
     */
    public function getTargetClassConstantName(): string
    {
        return 'ThresholdPolynomialClass';
    }


}
