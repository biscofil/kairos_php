<?php


namespace App\Voting\CryptoSystems;


use Illuminate\Http\Request;

/**
 * Class CipherText
 * @package App\Voting\CryptoSystems
 */
interface CipherText
{

    /**
     * @param array $data
     * @param bool $onlyY
     * @param PublicKey|null $pk
     * @return CipherText
     */
    //public static function fromArray(array $data, bool $onlyY = false, $pk = null): Ciphertext; // TODO

    /**
     * @param bool $includePublicKey
     * @param bool $onlyY
     * @return array
     */
    //public function toArray(bool $includePublicKey = false, bool $onlyY = false): array; // TODO

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
