<?php


namespace App\Voting\CryptoSystems;


use App\Models\Cast\Castable;
use App\Models\Election;
use Illuminate\Http\Request;

/**
 * Class CipherText
 * @package App\Voting\CryptoSystems
 */
interface CipherText extends Castable, BelongsToCryptoSystem
{

    /**
     * @param array $data
     * @param PublicKey|null $publicKey
     * @param bool $ignoreParameterSet
     * @param int $base
     * @return CipherText
     * @noinspection PhpMissingParamTypeInspection
     */
    public static function fromArray(array $data, $publicKey = null, bool $ignoreParameterSet = false, int $base = 16): CipherText;

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
     * @param int $userID
     * @param \App\Models\Election $election
     * @param \Illuminate\Http\Request $request
     * @return array
     * @noinspection PhpMissingParamTypeInspection
     */
    public static function validateAndStoreVotes(int $userID, Election $election, Request $request): array;

}
