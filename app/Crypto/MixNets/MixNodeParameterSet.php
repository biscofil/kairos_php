<?php


namespace App\Crypto\MixNets;


use App\Crypto\EGPublicKey;
use phpseclib3\Math\BigInteger;

/**
 * Class MixNodeParameterSet
 * @package App\Crypto\MixNets
 * @property BigInteger[] reEncryptionFactors
 * @property int[] permutation
 */
class MixNodeParameterSet
{

    /**
     * MixNodeParameterSet constructor.
     * @param BigInteger[] $reEncryptionFactors
     * @param int[] $permutation
     * @throws \Exception
     */
    public function __construct(array $reEncryptionFactors, array $permutation)
    {
        if (count($reEncryptionFactors) !== count($permutation)) {
            throw new \Exception('$reEncryptionFactors and $permutation have different size');
        }
        $this->reEncryptionFactors = $reEncryptionFactors;
        $this->permutation = $permutation;
    }

    /**
     * @param EGPublicKey $pk
     * @param int $count
     * @return MixNodeParameterSet
     * @throws \Exception
     */
    public static function create(EGPublicKey $pk, int $count): MixNodeParameterSet
    {

        $reEncryptionFactors = [];
        for ($i = 0; $i < $count; $i++) {
            $reEncryptionFactors[] = randomBIgt($pk->q);
        }

        // if not provided, generate permutation
        $permutation = range(0, $count - 1);
        shuffle($permutation);
        return new static($reEncryptionFactors, $permutation);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            "encryption" => array_map(function (BigInteger $randomness) {
                //return $randomness;
                return $randomness->toHex();
            }, $this->reEncryptionFactors),
            "permutation" => $this->permutation,
        ];
    }

    /**
     * @param MixNodeParameterSet $primaryMixPS
     * @return MixNodeParameterSet
     */
    public function combine(MixNodeParameterSet $primaryMixPS): MixNodeParameterSet
    {
        // combine randomness
        $newReEncryptionFactor = [];
        for ($i = 0; $i < count($this->reEncryptionFactors); $i++) {
            $newReEncryptionFactor[] = ($primaryMixPS->reEncryptionFactors[$i])->subtract($this->reEncryptionFactors[$i]);
        }

        // undo shadow mix shuffling (this)
        // recover inverse mapping of shadow mix permutation
        $permutationInv = array_flip($this->permutation);
        ksort($permutationInv); // restore key order
        // apply primary mix shuffling to the inverse mapping just computed
        $out = [];
        for ($i = 0; $i < count($this->permutation); $i++) {
            $out[] = $permutationInv[$primaryMixPS->permutation[$i]];
        }
        return new MixNodeParameterSet($newReEncryptionFactor, $out);
    }

}
