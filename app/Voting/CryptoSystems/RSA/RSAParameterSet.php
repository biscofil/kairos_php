<?php


namespace App\Voting\CryptoSystems\RSA;


use App\Voting\CryptoSystems\CryptoSystemParameterSet;

/**
 * Class RSAParameterSet
 * @package App\Voting\CryptoSystems\ElGamal
 */
class RSAParameterSet implements CryptoSystemParameterSet
{

    /**
     * RSAParameterSet constructor.
     */
    public function __construct()
    {
    }

    /**
     *
     */
    public static function default() : RSAParameterSet
    {
        return new static();
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return '';
    }

    /**
     * @param array $data
     * @param int $base
     * @return static
     */
    public static function fromArray(array $data, int $base = 16): RSAParameterSet
    {
        return new static();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
        ];
    }

    /**
     * @param \App\Voting\CryptoSystems\RSA\RSAParameterSet $parameterSet
     * @return bool
     */
    public function equals($parameterSet): bool
    {
        return true;
    }

}
