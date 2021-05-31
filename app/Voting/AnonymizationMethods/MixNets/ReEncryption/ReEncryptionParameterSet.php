<?php


namespace App\Voting\AnonymizationMethods\MixNets\ReEncryption;


use App\Enums\CryptoSystemEnum;
use App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet;
use App\Voting\CryptoSystems\PublicKey;
use phpseclib3\Math\BigInteger;

/**
 * Class ReEncryptionParameterSet
 * @package App\Voting\AnonymizationMethods\MixNets\ReEncryption
 * @property BigInteger[] reEncryptionFactors
 */
class ReEncryptionParameterSet extends MixNodeParameterSet
{

    use BelongsToReEncryptionMixNode;

    public array $reEncryptionFactors;

    /**
     * ReEncryptionParameterSet constructor.
     * @param \App\Voting\CryptoSystems\PublicKey $pk
     * @param array $reEncryptionFactors
     * @param array $permutation
     * @throws \Exception
     */
    public function __construct(PublicKey $pk, array $reEncryptionFactors, array $permutation)
    {
        parent::__construct($pk, $permutation);
        if (count($reEncryptionFactors) !== count($permutation)) {
            throw new \Exception('$reEncryptionFactors and $permutation have different size');
        }
        $this->reEncryptionFactors = $reEncryptionFactors;
    }

    // ##########################################################################

    /**
     * @param PublicKey $pk // TODO remove
     * @param int $count
     * @return self
     * @throws \Exception
     */
    public static function create(PublicKey $pk, int $count): self
    {

        $reEncryptionFactors = [];
        for ($i = 0; $i < $count; $i++) {

//            $kpClass = $pk->getCryptosystem()::getKeyPairClass();
//            $keyPair = $kpClass::generate();

            $reEncryptionFactors[] = randomBIgt($pk->parameterSet->q); // TODO check
        }

        // if not provided, generate permutation
        $permutation = range(0, $count - 1);
        shuffle($permutation);
        return new static($pk, $reEncryptionFactors, $permutation);
    }

    // ##########################################################################

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            '_cs_' => CryptoSystemEnum::getIdentifier($this->pk),
            'pk' => $this->pk->toArray(),
            'encryption' => array_map(function (BigInteger $randomness) {
                //return $randomness;
                return $randomness->toHex();
            }, $this->reEncryptionFactors),
            'permutation' => $this->permutation,
        ];
    }

    /**
     * @param array $data
     * @return self
     * @throws \Exception
     */
    public static function fromArray(array $data): self
    {

        $csClass = CryptoSystemEnum::getByIdentifier($data['_cs_']);
        $pkClass = $csClass::getPublicKeyClass();

        $pk = $pkClass::fromArray($data['pk']);

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

    // ##########################################################################

    /**
     * @param self $primaryMixPS
     * @return self
     * @throws \Exception
     */
    public function combine(ReEncryptionParameterSet $primaryMixPS): self
    {
        // combine randomness
        $perm = range(0, count($this->reEncryptionFactors) - 1);
        $newReEncryptionFactor = [];
        foreach ($perm as $i) {
            $newReEncryptionFactor[] = $primaryMixPS->reEncryptionFactors[$i]
                ->subtract($this->reEncryptionFactors[$i])
                ->modPow(BI1(), $this->pk->parameterSet->q); // TODO generalize!!!
        }

        // permute $newReEncryptionFactor according to shadow mix permutation
        $newReEncryptionFactor = $this->permuteArray($newReEncryptionFactor);

        // undo shadow mix shuffling (this)
        // recover inverse mapping of shadow mix permutation
        $permutation = $primaryMixPS->permuteArray($this->getShufflingOrderReversed());

        return new static($this->pk, $newReEncryptionFactor, $permutation);
    }

}
