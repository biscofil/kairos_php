<?php


namespace App\Models\Cast;


/**
 * Class ThresholdPolynomialCaster
 * @package App\Models\Cast
 */
class ThresholdPolynomialCaster extends DynamicCryptosystemClassCaster
{

    /**
     * Specify the name of the constant of a cryptosystem class (RSA/Elgamal) that contains the name of the class
     * we want to cast the value to
     * @param string|\App\Voting\CryptoSystems\CryptoSystem $cs
     * @return string|\App\Voting\CryptoSystems\ThresholdPolynomial
     */
    public function getTargetClassConstantName(string $cs): string
    {
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        return $cs::getThresholdPolynomialClass();
    }

}
