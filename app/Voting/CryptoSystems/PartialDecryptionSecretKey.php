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
     * @param bool $lastStep
     * @return \App\Voting\CryptoSystems\Ciphertext
     */
    public function partiallyDecrypt(CipherText $cipher, bool $lastStep = false): CipherText;

}
