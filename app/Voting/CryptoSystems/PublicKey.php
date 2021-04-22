<?php


namespace App\Voting\CryptoSystems;


/**
 * Interface PublicKey
 * @package App\Voting\CryptoSystems
 */
interface PublicKey
{

    /**
     * @param array $data
     * @param bool $ignoreParameterSet
     * @param int $base
     * @return PublicKey
     */
    public static function fromArray(array $data, bool $ignoreParameterSet = false, int $base = 16) : PublicKey;

    /**
     * @param bool $ignoreParameterSet
     * @return array
     */
    public function toArray(bool $ignoreParameterSet = false): array;

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
