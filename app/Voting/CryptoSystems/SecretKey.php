<?php


namespace App\Voting\CryptoSystems;


abstract class SecretKey
{

    /**
     * @param array $data
     * @param bool $ignoreParameterSet
     * @param int $base
     * @return SecretKey
     */
    abstract public static function fromArray(array $data, bool $ignoreParameterSet = false, int $base = 16): SecretKey;

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
