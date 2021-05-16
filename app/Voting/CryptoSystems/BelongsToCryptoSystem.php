<?php


namespace App\Voting\CryptoSystems;

/**
 * Interface BelongsToCryptoSystem
 * @package App\Voting\CryptoSystems
 */
interface BelongsToCryptoSystem
{

    /**
     * @return string|\App\Voting\CryptoSystems\CryptoSystem
     */
    public static function getCryptosystem() : string;

}
