<?php


namespace App\Voting\AnonymizationMethods\MixNets\ReEncryption;


use App\Models\Election;
use App\Voting\AnonymizationMethods\MixNets\Mix;
use App\Voting\AnonymizationMethods\MixNets\MixNode;

/**
 * Class ReEncryptingMixNode
 * @package App\Voting\AnonymizationMethods\MixNets
 */
class ReEncryptingMixNode extends MixNode
{

    /**
     * @param Election $election
     * @param array $ciphertexts
     * @param \App\Voting\AnonymizationMethods\MixNets\ReEncryption\ReEncryptionParameterSet|null $parameterSet
     * @return Mix
     * @throws \Exception
     */
    public static function forward(Election $election, array $ciphertexts, $parameterSet = null): Mix
    {

        if (is_null($parameterSet)) {
            // if not provided, generate as many randomness factors as there are ciphertexts
            $parameterSet = ReEncryptionParameterSet::create($election->public_key, count($ciphertexts));
        }

        // re-encrypt
        $reEncryptedCiphertexts = [];
        foreach ($ciphertexts as $idx => $ciphertext) {
            $r = $parameterSet->reEncryptionFactors[$idx];
            /** @var \App\Voting\CryptoSystems\ElGamal\EGCiphertext $ciphertext */
            $reEncryptedCiphertexts[] = $ciphertext->reEncryptWithRandomness($r); // TODO generalize
        }

        // shuffle
        $reEncryptedCiphertexts = $parameterSet->permuteArray($reEncryptedCiphertexts);

        return new ReEncryptionMix(
            $election,
            $reEncryptedCiphertexts,
            $parameterSet
        );

    }

    /**
     * @return string|ReEncryptionMixWithShadowMixes
     */
    public static function getMixWithShadowMixesClass(): string
    {
        return ReEncryptionMixWithShadowMixes::class;
    }

}
