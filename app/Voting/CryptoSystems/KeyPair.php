<?php


namespace App\Voting\CryptoSystems;


/**
 * Class KeyPair
 * @package App\Voting\CryptoSystems
 * @property $pk
 * @property $sk
 */
interface KeyPair extends BelongsToCryptoSystem
{

    /**
     * @param \App\Voting\CryptoSystems\ParameterSet|null $parameterSet
     * @return KeyPair
     * @noinspection PhpMissingParamTypeInspection
     */
    public static function generate($parameterSet = null): KeyPair;

    /**
     * @param string $filePath
     */
    public function storeToFile(string $filePath): void;

    /**
     * @param string $filePath Example: "/home/private_key.json"
     * @return KeyPair
     */
    public static function fromFile(string $filePath): KeyPair;
}
