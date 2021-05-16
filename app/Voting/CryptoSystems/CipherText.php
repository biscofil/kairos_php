<?php


namespace App\Voting\CryptoSystems;


use App\Models\Cast\Castable;

/**
 * Class CipherText
 * @package App\Voting\CryptoSystems
 */
interface CipherText extends Castable
{

    /**
     * @param array $data
     * @param PublicKey|null $publicKey
     * @param bool $ignoreParameterSet
     * @param int $base
     * @return CipherText
     * @noinspection PhpMissingParamTypeInspection
     */
    public static function fromArray(array $data, $publicKey = null, bool $ignoreParameterSet = false, int $base = 16) : CipherText;

    /**
     * @param bool $includePublicKey
     * @param bool $ignoreParameterSet
     * @return array
     */
    public function toArray(bool $includePublicKey = false, bool $ignoreParameterSet = false): array;

    // ################################################################################################

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
