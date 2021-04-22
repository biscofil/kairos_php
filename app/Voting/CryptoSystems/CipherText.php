<?php


namespace App\Voting\CryptoSystems;


/**
 * Class CipherText
 * @package App\Voting\CryptoSystems
 */
interface CipherText
{

    /**
     * @param array $data
     * @param bool $ignoreParameterSet
     * @param PublicKey|null $pk
     * @return CipherText
     */
    public static function fromArray(array $data, bool $ignoreParameterSet = false, $pk = null) : CipherText;

    /**
     * @param bool $includePublicKey
     * @param bool $ignoreParameterSet
     * @return array
     */
    public function toArray(bool $includePublicKey = false, bool $ignoreParameterSet = false): array;

    /**
     * @return string
     */
    public function getFingerprint(): string;

    /**
     * @param Ciphertext $b
     * @return bool
     * @throws \Exception
     * @noinspection PhpMissingParamTypeInspection
     */
    public function equals($b): bool;

    /**
     * @param array $data
     * @return array
     */
    public static function validate(array $data): array;

}
