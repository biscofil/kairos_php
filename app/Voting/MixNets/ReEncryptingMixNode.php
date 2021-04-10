<?php


namespace App\Voting\MixNets;


use App\Voting\CryptoSystems\PublicKey;

/**
 * Class ReEncryptingMixNode
 * @package App\Voting\MixNets
 */
class ReEncryptingMixNode extends MixNode
{

    /**
     * @param PublicKey $pk
     * @param array $ciphertexts
     * @param MixNodeParameterSet|null $parameterSet
     * @return Mix
     * @throws \Exception
     */
    public static function forward(PublicKey $pk, array $ciphertexts, MixNodeParameterSet $parameterSet = null): Mix
    {

        if (is_null($parameterSet)) {
            // if not provided, generate as many randomness factors as there are ciphertexts
            $parameterSet = MixNodeParameterSet::create($pk, count($ciphertexts));
        }

        // re-encrypt (or de-re-encrypt)
        $reEncryptedCiphertexts = [];
        foreach ($ciphertexts as $idx => $ciphertext) {
            $r = $parameterSet->reEncryptionFactors[$idx];
            $reEncryptedCiphertexts[] = $ciphertext->reEncryptWithRandomness($r);
        }

        // if not, shuffle after encryption
        $reEncryptedCiphertexts = $parameterSet->permuteArray($reEncryptedCiphertexts);

        return new Mix( // OK
            $pk,
            $reEncryptedCiphertexts,
            $parameterSet
        );

    }

    /**
     * @param PublicKey $pk
     * @param array $ciphertexts
     * @param MixNodeParameterSet|null $parameterSet
     * @return Mix
     * @throws \Exception
     */
    public static function backward(PublicKey $pk, array $ciphertexts, MixNodeParameterSet $parameterSet = null): Mix
    {
        if (is_null($parameterSet)) {
            // if not provided, generate as many randomness factors as there are ciphertexts
            $parameterSet = MixNodeParameterSet::create($pk, count($ciphertexts));
        }

        // if backward, de-shuffle first, de-crypt later
        $parameterSet->permutation = $parameterSet->getShufflingOrderReversed();
        $ciphertexts = $parameterSet->permuteArray($ciphertexts);

        // re-encrypt (or de-re-encrypt)
        $reEncryptedCiphertexts = [];
        foreach ($ciphertexts as $idx => $ciphertext) {
            $r = $parameterSet->reEncryptionFactors[$idx];
            $reEncryptedCiphertexts[] = $ciphertext->decryptWithRandomness($r);
        }

        return new Mix($pk, $reEncryptedCiphertexts, $parameterSet);

    }

}
