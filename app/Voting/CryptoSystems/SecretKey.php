<?php


namespace App\Voting\CryptoSystems;


use Illuminate\Http\Request;

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
     * @param bool $onlyXY
     * @return array
     */
    public abstract function toArray(bool $onlyXY = false): array;

    /**
     * @param CipherText $cipherText
     * @return Plaintext
     * @noinspection PhpMissingParamTypeInspection
     */
    public abstract function decrypt($cipherText): Plaintext;

}
