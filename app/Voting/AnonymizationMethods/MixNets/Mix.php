<?php


namespace App\Voting\MixNets;


use App\Voting\CryptoSystems\CipherText;
use App\Voting\CryptoSystems\PublicKey;
use App\Voting\CryptoSystems\SecretKey;

/**
 * Class Mix
 * @package App\Voting\MixNets
 * @property Ciphertext[] ciphertexts
 * @property MixNodeParameterSet $parameterSet
 */
class Mix
{

    public array $ciphertexts;
    public MixNodeParameterSet $parameterSet;

    /**
     * @param PublicKey $pk
     * @param Ciphertext[] $ciphertexts
     * @param MixNodeParameterSet|null $parameterSet
     * @throws \Exception
     */
    public function __construct(PublicKey $pk, array $ciphertexts, MixNodeParameterSet $parameterSet = null)
    {
        $this->ciphertexts = $ciphertexts;
        $this->parameterSet = $parameterSet;
    }

    /**
     * Hash is not enough to declare to mixes equal
     * @return string
     */
    public function getHash(): string
    {
        return array_reduce($this->ciphertexts, function (string $carry, CipherText $ciphertext) {
            return sha1($carry . $ciphertext->getFingerprint());
        }, "");
    }

    /**
     * @param Mix $b
     * @param SecretKey $sk
     * @return bool
     * @throws \Exception
     */
    public function equals(Mix $b, SecretKey $sk): bool
    {
        for ($i = 0; $i < count($this->ciphertexts); $i++) {

            if (true) {

                // TODO proof should not use the private key!!!!!
                // private key check
                if (!$sk->decrypt($this->ciphertexts[$i])->m->equals($sk->decrypt($b->ciphertexts[$i])->m)) {
                    return false;
                }

            } else {

                // No private key check
                if (!$this->ciphertexts[$i]->equals($b->ciphertexts[$i])) {
//                dump($this->ciphertexts[$i]->alpha->toHex());
//                dump($b->ciphertexts[$i]->alpha->toHex());
//                dump($this->ciphertexts[$i]->beta->toHex());
//                dump($b->ciphertexts[$i]->beta->toHex());
                    return false;
                }

            }
//

        }
        return true;
    }

    // ########################################################################
    // ########################################################################
    // ########################################################################

    /**
     * @param PublicKey $pk
     * @param array $data
     * @return Mix
     * @throws \Exception
     */
    public static function fromArray(PublicKey $pk, array $data): Mix
    {

        $ciphertexts = array_map(function (array $cipher) use ($pk) {
            return Ciphertext::fromArray($cipher, false, $pk);
        }, $data['ciphertexts']);

        $parameterSet = is_null($data['parameter_set'])
            ? null
            : MixNodeParameterSet::fromArray($pk, $data['parameter_set']);

        return new static($pk, $ciphertexts, $parameterSet);
    }

    /**
     * @param bool $storePrivateValues
     * @return array
     */
    public function toArray(bool $storePrivateValues = false): array
    {
        return [
            'ciphertexts' => array_map(function (Ciphertext $cipherText) {
                return $cipherText->toArray();
            }, $this->ciphertexts),
            'parameter_set' => $storePrivateValues ? $this->parameterSet->toArray() : null,
        ];
    }

}
