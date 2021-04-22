<?php


namespace App\Models\Cast;


/**
 * Class PrivateKeyCaster
 * @package App\Models\Cast
 */
class PrivateKeyCasterCryptosystem extends DynamicCryptosystemClassCaster
{

    /**
     * Given a cryptosystem, specify the target class
     * @return string
     * @see \App\Voting\CryptoSystems\CryptoSystem::SecretKeyClass
     */
    public function getTargetClassConstantName(): string
    {
        return 'SecretKeyClass';
    }

}
