<?php


namespace App\Models\Cast;


/**
 * Class SecretKeyCaster
 * @package App\Models\Cast
 */
class SecretKeyCaster extends DynamicCryptosystemClassCaster
{

    /**
     * Given a cryptosystem, specify the target class
     * @param string|\App\Voting\CryptoSystems\CryptoSystem $cs
     * @return string|\App\Voting\CryptoSystems\SecretKey
     */
    public function getTargetClassConstantName(string $cs): string
    {
        return $cs::getSecretKeyClass();
    }

}
