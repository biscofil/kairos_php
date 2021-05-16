<?php


namespace App\Voting\CryptoSystems;


use App\Models\Cast\Castable;

/**
 * Interface PublicKey
 * @package App\Voting\CryptoSystems
 */
interface PublicKey extends Castable
{

    /**
     * @param array $data
     * @param bool $ignoreParameterSet
     * @param int $base
     * @return static
     */
    public static function fromArray(array $data, bool $ignoreParameterSet = false, int $base = 16) : self;

    /**
     * @param bool $ignoreParameterSet
     * @return array
     */
    public function toArray(bool $ignoreParameterSet = false): array;

    // ############################################################################################################

    /**
     * @return string
     */
    public function getFingerprint(): string;

    /**
     * @param Plaintext $plainText
     * @return CipherText
     * @noinspection PhpMissingParamTypeInspection
     */
    public function encrypt($plainText) : CipherText;

    /**
     * @param $b
     */
    public function ensureSameParameters($b): void;

}
