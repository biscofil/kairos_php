<?php


namespace App\Voting\CryptoSystems;


/**
 * Interface CryptoSystemParameterSet
 * @package App\Voting\CryptoSystems
 */
interface CryptoSystemParameterSet
{

    /**
     * @return CryptoSystemParameterSet
     * @noinspection PhpMissingReturnTypeInspection
     */
    public static function default() : CryptoSystemParameterSet;

    /**
     * @return string
     */
    public function toString() : string;

    /**
     * @param array $data
     * @param int $base
     * @return CryptoSystemParameterSet
     * @noinspection PhpMissingReturnTypeInspection
     */
    public static function fromArray(array $data, int $base = 16) : CryptoSystemParameterSet;

    /**
     * @return array
     */
    public function toArray(): array;

    /**
     * @param CryptoSystemParameterSet $parameterSet
     * @return bool
     * @noinspection PhpMissingParamTypeInspection
     */
    public function equals($parameterSet): bool;

}
