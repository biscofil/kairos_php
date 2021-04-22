<?php


namespace App\Voting\CryptoSystems;


/**
 * Class KeyPair
 * @package App\Voting\CryptoSystems
 * @property $pk
 * @property $sk
 */
interface KeyPair
{

    /**
     * @param \App\Voting\CryptoSystems\CryptoSystemParameterSet|null $parameterSet
     * @return KeyPair
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
