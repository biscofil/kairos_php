<?php


namespace App\Voting\AnonymizationMethods\MixNets;


use App\Voting\CryptoSystems\PublicKey;
use phpseclib3\Math\BigInteger;

/**
 * Class MixNodeParameterSet
 * @package App\Voting\MixNets
 * @property PublicKey pk
 * @property BigInteger[] reEncryptionFactors
 * @property int[] permutation
 */
class MixNodeParameterSet
{

    public PublicKey $pk;
    public array $reEncryptionFactors;
    public array $permutation;

    /**
     * MixNodeParameterSet constructor.
     * @param PublicKey $pk
     * @param BigInteger[] $reEncryptionFactors
     * @param int[] $permutation
     * @throws \Exception
     */
    public function __construct(PublicKey $pk, array $reEncryptionFactors, array $permutation)
    {
        $this->pk = $pk;
        if (count($reEncryptionFactors) !== count($permutation)) {
            throw new \Exception('$reEncryptionFactors and $permutation have different size');
        }
        $this->reEncryptionFactors = $reEncryptionFactors;
        $this->permutation = $permutation;
    }

    /**
     * @param PublicKey $pk
     * @param int $count
     * @return MixNodeParameterSet
     * @throws \Exception
     */
    public static function create(PublicKey $pk, int $count): MixNodeParameterSet
    {

        $reEncryptionFactors = [];
        for ($i = 0; $i < $count; $i++) {
            $reEncryptionFactors[] = randomBIgt($pk->parameterSet->q);
        }

        // if not provided, generate permutation
        $permutation = range(0, $count - 1);
        shuffle($permutation);
        return new static($pk, $reEncryptionFactors, $permutation);
    }

    // ##########################################################################
    // ##########################################################################
    // ##########################################################################

    /**
     * @param PublicKey $pk
     * @param array $data
     * @return MixNodeParameterSet
     * @throws \Exception
     */
    public static function fromArray(PublicKey $pk, array $data): MixNodeParameterSet
    {

        $encryption = array_map(function (string $randomnessStr) {
            return BI($randomnessStr, 16);
        }, $data['encryption']);

        $permutation = $data['permutation'];

        return new static(
            $pk,
            $encryption,
            $permutation
        );

    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'encryption' => array_map(function (BigInteger $randomness) {
                //return $randomness;
                return $randomness->toHex();
            }, $this->reEncryptionFactors),
            'permutation' => $this->permutation,
        ];
    }

    // ##########################################################################
    // ##########################################################################
    // ##########################################################################

    /**
     * @param MixNodeParameterSet $primaryMixPS
     * @return MixNodeParameterSet
     * @throws \Exception
     */
    public function combine(MixNodeParameterSet $primaryMixPS): MixNodeParameterSet
    {
        // combine randomness
        $newReEncryptionFactor = [];
        for ($i = 0; $i < count($this->reEncryptionFactors); $i++) {
            $newReEncryptionFactor[] = $primaryMixPS->reEncryptionFactors[$i]
                ->subtract($this->reEncryptionFactors[$i])
                ->modPow(BI1(), $this->pk->parameterSet->p); // TODO check
        }

        // undo shadow mix shuffling (this)
        // recover inverse mapping of shadow mix permutation
        $out = $primaryMixPS->permuteArray($this->getShufflingOrderReversed());

        return new MixNodeParameterSet($this->pk, $newReEncryptionFactor, $out);
    }

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
