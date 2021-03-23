<?php


namespace App\Crypto\MixNets;


use App\Crypto\EGCiphertext;
use App\Crypto\EGPrivateKey;

/**
 * Class Mix
 * @package App\Crypto\MixNets
 * @property EGCiphertext[] ciphertexts
 * @property MixNodeParameterSet $parameterSet
 */
class Mix
{

    public array $ciphertexts;
    public MixNodeParameterSet $parameterSet;

    /**
     * @param EGCiphertext[] $ciphertexts
     * @param MixNodeParameterSet|null $parameterSet
     * @throws \Exception
     */
    public function __construct(array $ciphertexts, MixNodeParameterSet $parameterSet = null)
    {

        if (is_null($parameterSet)) {
            // if not provided, generate as many randomness factors as there are ciphertexts
            $parameterSet = MixNodeParameterSet::create($ciphertexts[0]->pk, count($ciphertexts));
        }

        // re-encrypt
        $reEncryptedCiphertexts = [];
        foreach ($ciphertexts as $idx => $ciphertext) {
            $r = $parameterSet->reEncryptionFactors[$idx];
            $reEncryptedCiphertexts[] = $ciphertext->reEncryptWithRandomness($r);
        }

        $this->ciphertexts = array_map(function (int $idx) use ($reEncryptedCiphertexts) {
            return $reEncryptedCiphertexts[$idx];
        }, $parameterSet->permutation);

        $this->parameterSet = $parameterSet;
    }

    /**
     * Hash is not enough to declare to mixes equal
     * @return string
     */
    public function getHash(): string
    {
        return array_reduce($this->ciphertexts, function (string $carry, EGCiphertext $ciphertext) {
            return sha1($carry . $ciphertext->beta->toHex());
        }, "");
    }

    /**
     * @return EGCiphertext[]
     */
    public function toArray(): array
    {
        return $this->ciphertexts;
    }

    /**
     * @param Mix $b
     * @param EGPrivateKey $sk
     * @return bool
     */
    public function equals(Mix $b, EGPrivateKey $sk): bool
    {
        for ($i = 0; $i < count($this->ciphertexts); $i++) {
            if (!$sk->decrypt($this->ciphertexts[$i])->m
                ->equals($sk->decrypt($b->ciphertexts[$i])->m)) {
                return false;
            }
        }
        return true;
    }

}
