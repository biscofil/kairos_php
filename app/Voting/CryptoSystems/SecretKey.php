<?php


namespace App\Voting\CryptoSystems;


use App\Models\Cast\Castable;

abstract class SecretKey implements Castable
{

    /**
     * @param array $data
     * @param bool $ignoreParameterSet
     * @param int $base
     * @return static
     */
    abstract public static function fromArray(array $data, bool $ignoreParameterSet = false, int $base = 16): self;

    /**
     * @param bool $ignoreParameterSet
     * @return array
     */
    abstract public function toArray(bool $ignoreParameterSet = false): array;

    // ############################################################################################################

    /**
     * @param CipherText $cipherText
     * @return Plaintext
     * @noinspection PhpMissingParamTypeInspection
     */
    abstract public function decrypt($cipherText): Plaintext;

}
