<?php


namespace App\Voting\CryptoSystems\RSA;


/**
 * Trait BelongsToRSA
 * @package App\Voting\CryptoSystems\RSA
 */
trait BelongsToRSA
{

    /**
     * @return string
     */
    public static function getCryptosystem() : string
    {
        return RSA::class;
    }

}
