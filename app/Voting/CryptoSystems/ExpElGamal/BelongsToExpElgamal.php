<?php


namespace App\Voting\CryptoSystems\ExpElGamal;


/**
 * Trait BelongsToExpElgamal
 * @package App\Voting\CryptoSystems\ExpElGamal
 */
trait BelongsToExpElgamal
{

    /**
     * @return string|ExpElGamal
     */
    public static function getCryptosystem() : string
    {
        return ExpElGamal::class;
    }

}
