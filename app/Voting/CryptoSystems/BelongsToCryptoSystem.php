<?php


namespace App\Voting\CryptoSystems;

/**
 * Interface BelongsToCryptoSystem
 * @package App\Voting\CryptoSystems
 */
interface BelongsToCryptoSystem
{

    /**
     * @return string
     */
    public static function getCryptosystem() : string;

}
