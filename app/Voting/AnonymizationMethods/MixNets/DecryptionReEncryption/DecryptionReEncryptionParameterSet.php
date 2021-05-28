<?php


namespace App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption;

use App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption\DecryptionReEncryptionMixNode;
use App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet;
use App\Voting\AnonymizationMethods\MixNets\ReEncryption\BelongsToReEncryptionMixNode;
use App\Voting\CryptoSystems\PublicKey;
use phpseclib3\Math\BigInteger;

/**
 * Class DecryptionReEncryptionParameterSet
 * @package App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption
 * @property BigInteger[] reEncryptionFactors
 */
class DecryptionReEncryptionParameterSet extends MixNodeParameterSet
{

    use BelongsToReEncryptionMixNode;

    public array $reEncryptionFactors;

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
            'encryption' => array_map(function (BigInteger $randomness) {
                //return $randomness;
                return $randomness->toHex();
            }, $this->reEncryptionFactors),
            'permutation' => $this->permutation,
        ];
    }

    /**
     * @param PublicKey $pk // TODO remove
     * @param array $data
     * @return self
     * @throws \Exception
     */
    public static function fromArray(PublicKey $pk, array $data): self
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

    // ##########################################################################

    /**
     * @param self $primaryMixPS
     * @return self
     * @throws \Exception
     */
    public function combine(DecryptionReEncryptionParameterSet $primaryMixPS): self
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

        return new static($this->pk, $newReEncryptionFactor, $out);
    }

}
