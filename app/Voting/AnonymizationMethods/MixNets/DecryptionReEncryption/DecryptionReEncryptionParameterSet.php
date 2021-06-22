<?php


namespace App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption;

use App\Enums\CryptoSystemEnum;
use App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet;
use App\Voting\AnonymizationMethods\MixNets\ReEncryption\BelongsToReEncryptionMixNode;
use App\Voting\CryptoSystems\PublicKey;
use phpseclib3\Math\BigInteger;

/**
 * Class DecryptionReEncryptionParameterSet
 * @package App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption
 * @property BigInteger[] reEncryptionFactors
 * @property bool $decryption
 */
class DecryptionReEncryptionParameterSet extends MixNodeParameterSet
{

    use BelongsToReEncryptionMixNode;

    public array $reEncryptionFactors;
    public bool $decryption = true;

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
        // if not provided, generate permutation
        $permutation = range(0, $count - 1);
        shuffle($permutation);

        $reEncryptionFactors = array_map(function () use ($pk): BigInteger {
            return $pk->parameterSet->getReEncryptionFactor();
        }, $permutation);

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
            'decryption' => $this->decryption
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

        $encryption = array_map(function (string $randomnessStr): BigInteger {
            return BI($randomnessStr, 16);
        }, $data['encryption']);

        $permutation = $data['permutation'];

        $out = new static(
            $pk,
            $encryption,
            $permutation
        );

        $out->decryption = boolval($data['decryption']);

        return $out;

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
        $newReEncryptionFactor = array_map(
            function (BigInteger $primaryMixReEncryptionFactor, BigInteger $shadowMixReEncryptionFactor): BigInteger {
                return mod(
                    $primaryMixReEncryptionFactor->subtract($shadowMixReEncryptionFactor),
                    $this->pk->parameterSet->q
                ); // TODO generalize!!!
            },
            $primaryMixPS->reEncryptionFactors,
            $this->reEncryptionFactors);

        // permute $newReEncryptionFactor according to shadow mix permutation
        $newReEncryptionFactor = $this->permuteArray($newReEncryptionFactor);

        // undo shadow mix shuffling (this)
        // recover inverse mapping of shadow mix permutation
        $permutation = $primaryMixPS->permuteArray($this->getShufflingOrderReversed());

        return new static($this->pk, $newReEncryptionFactor, $permutation);

    }

}
