<?php


namespace App\Models\Cast;


/**
 * Class ThresholdBroadcastCaster
 * @package App\Models\Cast
 */
class ThresholdBroadcastCasterCryptosystem extends DynamicCryptosystemClassCaster
{

    /**
     * Specify the name of the constant of a cryptosystem class (RSA/Elgamal) that contains the name of the class
     * we want to cast the value to
     * @return string
     */
    public function getTargetClassConstantName(): string
    {
        return 'ThresholdBroadcastClass';
    }


}
