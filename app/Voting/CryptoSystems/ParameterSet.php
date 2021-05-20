<?php


namespace App\Voting\CryptoSystems;


/**
 * Interface ParameterSet
 * @package App\Voting\CryptoSystems
 */
interface ParameterSet extends BelongsToCryptoSystem
{

    /**
     * @return ParameterSet
     * @noinspection PhpMissingReturnTypeInspection
     */
    public static function getDefault(): self;

    /**
     * @return string
     */
    public function toString(): string;

    /**
     * @param array $data
     * @param int $base
     * @return ParameterSet
     * @noinspection PhpMissingReturnTypeInspection
     */
    public static function fromArray(array $data, int $base = 16): self;

    /**
     * @return array
     */
    public function toArray(): array;

    /**
     * @param ParameterSet $parameterSet
     * @return bool
     * @noinspection PhpMissingParamTypeInspection
     */
    public function equals($parameterSet): bool;

}
