<?php


namespace App\Voting\CryptoSystems;


use App\Models\Cast\Castable;

interface SecretKey extends Castable, BelongsToCryptoSystem
{

    /**
     * @param array $data
     * @param bool $ignoreParameterSet
     * @param int $base
     * @return static
     */
    public static function fromArray(array $data, bool $ignoreParameterSet = false, int $base = 16): self;

    /**
     * @param bool $ignoreParameterSet
     * @return array
     */
    public function toArray(bool $ignoreParameterSet = false): array;

    // ############################################################################################################

    /**
     * @param CipherText $cipherText
     * @return Plaintext
     * @noinspection PhpMissingParamTypeInspection
     */
    public function decrypt($cipherText): Plaintext;

}
