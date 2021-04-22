<?php


namespace App\Voting\CryptoSystems;


abstract class SecretKey
{

    /**
     * @param array $data
     * @param bool $onlyXY
     * @param int $base
     * @return SecretKey
     */
    public static abstract function fromArray(array $data, bool $onlyXY = false, int $base = 16): SecretKey;

    /**
     * @param bool $ignoreParameterSet
     * @return array
     */
    abstract public function toArray(bool $ignoreParameterSet = false): array;

    /**
     * @param CipherText $cipherText
     * @return Plaintext
     * @noinspection PhpMissingParamTypeInspection
     */
    abstract public function decrypt($cipherText): Plaintext;

}
