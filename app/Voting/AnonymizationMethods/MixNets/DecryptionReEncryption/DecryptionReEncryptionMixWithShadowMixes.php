<?php


namespace App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption;


use App\Models\PeerServer;
use App\Voting\AnonymizationMethods\MixNets\Mix;
use App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet;
use App\Voting\AnonymizationMethods\MixNets\MixWithShadowMixes;
use App\Voting\CryptoSystems\CipherText;
use App\Voting\CryptoSystems\ElGamal\EGDLogProof;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;

/**
 * Class DecryptionReEncryptionMixWithShadowMixed
 * @package App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption
 */
class DecryptionReEncryptionMixWithShadowMixes extends MixWithShadowMixes
{

    use BelongsToDecryptionReEncryptionMixNode;

    /**
     * TODO generalize
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadowMix
     * @param \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet $parameterSet
     * @param $proof
     * @return bool
     */
    public function checkLeftProof(Mix $shadowMix, MixNodeParameterSet $parameterSet, $proof): bool
    {

        /** @var \App\Voting\CryptoSystems\ElGamal\EGKeyPair $trusteeKeyPair */
        $trusteeKeyPair = $this->election->getTrusteeFromPeerServer(getCurrentServer()); // TODO

        // ###################  backwards step 3/3 -> deshuffling ###################
        /** @var \App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption\DecryptionReEncryptionParameterSet $shadowMixPS */
        $shadowMixPS = $shadowMix->parameterSet;
        $newParameterSet = clone $shadowMixPS;
        $newParameterSet->permutation = $newParameterSet->getShufflingOrderReversed();
        $unShuffledCiphertexts = $newParameterSet->permuteArray($shadowMix->ciphertexts);

        // ################### backwards step 2/3 -> reverse re-encryption ###################
        /** @var \App\Voting\CryptoSystems\ElGamal\EGCiphertext[] $unReEncryptedCiphers */
        $unReEncryptedCiphers = array_map(function (CipherText $cipherText, int $idx) use ($shadowMixPS, $shadowMix) {
            $reEncryptionRandomness = $shadowMixPS->reEncryptionFactors[$idx];
            return $cipherText->reverseReEncryptionWithRandomness($reEncryptionRandomness);
        }, $unShuffledCiphertexts, range(1, count($shadowMix->ciphertexts)));

        // ###################  backwards step 1/3 -> reverse partial decryption (prove) ###################
        foreach ($unReEncryptedCiphers as $idx => $unReEncryptedCipher) {
            /** @var \App\Voting\CryptoSystems\ElGamal\EGCiphertext $cipherText */
            $cipherText = $this->originalCiphertexts[$idx]; // TODO check
            // TODO, generalize
            $proof = EGDLogProof::generate($trusteeKeyPair->sk, $cipherText, [EGDLogProof::class, 'DLogChallengeGenerator']); // TODO only for proof creation
            $unReEncryptedCipher->beta = $trusteeKeyPair->pk->parameterSet->extractMessageFromSubgroup($unReEncryptedCipher->beta);
            $proofPlain = new EGPlaintext($unReEncryptedCipher->beta);
            if (!$proof->verify($trusteeKeyPair->pk, $cipherText, $proofPlain, [EGDLogProof::class, 'DLogChallengeGenerator'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * TODO combine parameters sets as in the ReEncryption mixnet proof
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadowMix
     * @param \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet $parameterSet
     * @param $proof
     * @return bool
     */
    public function checkRightProof(Mix $shadowMix, MixNodeParameterSet $parameterSet, $proof): bool
    {
        foreach ($shadowMix->ciphertexts as $cipherText) {
            //TODO
        }
        return true;
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadow
     * @return DecryptionReEncryptionParameterSet
     */
    public function getLeftEquivalenceParameterSet(Mix $shadow): DecryptionReEncryptionParameterSet
    {
        // TODO: Implement getLeftEquivalenceParameterSet() method.
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadow
     * @return DecryptionReEncryptionParameterSet
     */
    public function getRightEquivalenceParameterSet(Mix $shadow): DecryptionReEncryptionParameterSet
    {
        return $shadow->parameterSet->combine($this->primaryMix->parameterSet);
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadow
     */
    public function getLeftProof(Mix $shadow)
    {
        // TODO: Implement getLeftProof() method.
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadow
     */
    public function getRightProof(Mix $shadow)
    {
        // TODO: Implement getRightProof() method.
    }
}
