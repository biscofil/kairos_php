<?php


namespace App\Voting\CryptoSystems;



/**
 * Interface PartialDecryptionSecretKey
 * @package App\Voting\CryptoSystems
 */
interface PartialDecryptionSecretKey
{

    /**
     * @param \App\Voting\CryptoSystems\Ciphertext $cipher
     * @return \App\Voting\CryptoSystems\Ciphertext
     * @noinspection PhpMissingParamTypeInspection
     */
    public function partiallyDecrypt($cipher): CipherText;

}
