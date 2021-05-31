<?php


namespace App\Voting\AnonymizationMethods\MixNets\Decryption;


use App\Enums\CryptoSystemEnum;
use App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet;
use App\Voting\CryptoSystems\PublicKey;

/**
 * Class DecryptionParameterSet
 * @package App\Voting\AnonymizationMethods\MixNets\Decryption
 */
class DecryptionParameterSet extends MixNodeParameterSet
{

    use BelongsToDecryptionMixNode;

    /**
     * @param \App\Voting\CryptoSystems\PublicKey $pk
     * @param int $count
     * @return \App\Voting\AnonymizationMethods\MixNets\Decryption\DecryptionParameterSet
     * @throws \Exception
     */
    public static function create(PublicKey $pk, int $count): self
    {
        // generate permutation
        $permutation = range(0, $count - 1);
        shuffle($permutation);
        return new static($pk, $permutation);
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
            'permutation' => $this->permutation,
        ];
    }

    /**
     * @param array $data
     * @return static
     * @throws \Exception
     */
    public static function fromArray(array $data): self
    {

        $csClass = CryptoSystemEnum::getByIdentifier($data['_cs_']);
        $pkClass = $csClass::getPublicKeyClass();

        $pk = $pkClass::fromArray($data['pk']);

        $permutation = $data['permutation'];
        return new static(
            $pk,
            $permutation
        );
    }

}
