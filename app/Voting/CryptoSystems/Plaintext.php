<?php


namespace App\Voting\CryptoSystems;


/**
 * Interface Plaintext
 * @package App\Voting\CryptoSystems
 */
interface Plaintext
{

    /**
     * @param Plaintext $b
     * @return bool
     */
    public function equals(self $b): bool;

}
