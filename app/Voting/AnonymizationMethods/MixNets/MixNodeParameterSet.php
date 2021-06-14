<?php


namespace App\Voting\AnonymizationMethods\MixNets;


use App\Voting\AnonymizationMethods\BelongsToAnonymizationMethod;
use App\Voting\CryptoSystems\PublicKey;

/**
 * Class MixNodeParameterSet
 * @package App\Voting\MixNets
 * @property PublicKey pk // TODO remove
 * @property int[] permutation
 */
abstract class MixNodeParameterSet implements BelongsToAnonymizationMethod
{

    public PublicKey $pk; // TODO remove
    public array $permutation;

//    public function __clone() {
//        return static::fromArray(static::toArray());
//    }

    /**
     * MixNodeParameterSet constructor.
     * @param PublicKey $pk
     * @param int[] $permutation
     * @throws \Exception
     */
    public function __construct(PublicKey $pk, array $permutation)
    {
        $this->pk = $pk; // TODO remove
        $this->permutation = $permutation;
    }

    // ##########################################################################

    /**
     * @param PublicKey $pk // TODO remove
     * @param int $count
     * @return MixNodeParameterSet
     * @throws \Exception
     */
    abstract public static function create(PublicKey $pk, int $count): self;

    // ##########################################################################

    /**
     * @return array
     */
    abstract public function toArray(): array;

    /**
     * @param array $data
     * @return static
     */
    abstract public static function fromArray(array $data): self;

    // ##########################################################################

    /**
     * @return int[]
     */
    public function getShufflingOrderReversed(): array
    {
        $permutationInv = array_flip($this->permutation);
        ksort($permutationInv); // restore key order
        return $permutationInv;
    }

    /**
     * Permutes a generic array with the permutation
     * @param $input
     * @return array
     */
    public function permuteArray($input): array
    {
        $out = [];
        for ($i = 0; $i < count($this->permutation); $i++) {
            $out[] = $input[$this->permutation[$i]];
        }
        return $out;
    }

}
