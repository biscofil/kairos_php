<?php


namespace App\Voting\CryptoSystems;

use App\Models\Election;

/**
 * Class CryptoSystem
 * @package App\Voting\CryptoSystems
 */
interface CryptoSystem
{

    /**
     * @return string
     */
    public static function getPublicKeyClass(): string;

    /**
     * @return string|null|\App\Voting\CryptoSystems\SecretKey
     */
    public static function getSecretKeyClass(): string;

    /**
     * @return string|null|\App\Voting\CryptoSystems\KeyPair
     */
    public static function getKeyPairClass(): string;

    /**
     * @return string|null|\App\Voting\CryptoSystems\Plaintext
     */
    public static function getPlainTextClass(): string;

    /**
     * @return string|null|\App\Voting\CryptoSystems\CipherText
     */
    public static function getCipherTextClass(): string;

    /**
     * @return string|null|\App\Voting\CryptoSystems\ParameterSet
     */
    public static function getParameterSetClass(): string;

    // #############################################################################

    /**
     * @param Election $election
     */
    public static function onElectionFreeze(Election &$election): void;

    /**
     * @param Election $election
     */
    public static function afterAnonymizationProcessEnds(Election &$election): void;

    /**
     * @param \App\Models\Election $election
     * @return mixed
     */
    public static function tally(Election &$election);
}
