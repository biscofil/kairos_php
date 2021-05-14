<?php


namespace App\Voting\CryptoSystems\ElGamal;


/**
 * Trait BelongsToElgamal
 * @package App\Voting\CryptoSystems\ElGamal
 */
trait BelongsToElgamal
{

    /**
     * @return string
     */
    public static function getCryptosystem() : string
    {
        return ElGamal::class;
    }

}
