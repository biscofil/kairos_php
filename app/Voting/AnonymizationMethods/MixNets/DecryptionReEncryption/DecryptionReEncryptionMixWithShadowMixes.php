<?php


namespace App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption;


use App\Models\Trustee;
use App\Voting\AnonymizationMethods\MixNets\Mix;
use App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet;
use App\Voting\AnonymizationMethods\MixNets\MixWithShadowMixes;
use App\Voting\CryptoSystems\CipherText;
use App\Voting\CryptoSystems\ElGamal\EGCiphertext;
use App\Voting\CryptoSystems\ElGamal\EGDLogProof;

/**
 * Class DecryptionReEncryptionMixWithShadowMixed
 * @package App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption
 */
class DecryptionReEncryptionMixWithShadowMixes extends MixWithShadowMixes
{

    use BelongsToDecryptionReEncryptionMixNode;

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadow
     * @return DecryptionReEncryptionParameterSet
     */
    public function getLeftEquivalenceParameterSet(Mix $shadow): MixNodeParameterSet
    {
        return $shadow->parameterSet;
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadow
     * @return DecryptionReEncryptionParameterSet
     */
    public function getRightEquivalenceParameterSet(Mix $shadow): MixNodeParameterSet
    {
        return $shadow->parameterSet->combine($this->primaryMix->parameterSet); // TODO Call to a member function combine() on null
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadow
     * @param \App\Models\Trustee $claimer
     * @return EGDLogProof[][]|null
     */
    public function getLeftProofs(Mix $shadow, Trustee $claimer): ?array
    {

        /** @var \App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption\DecryptionReEncryptionParameterSet $shadowMixPS */
        $shadowMixPS = $shadow->parameterSet;

        return array_map(function (EGCiphertext $cipherText, int $idx) use ($shadowMixPS, $claimer) {

            $reEncryptionRandomness = $shadowMixPS->reEncryptionFactors[$idx];
            $cipherText = $cipherText->reEncryptWithRandomness($reEncryptionRandomness);

            /** @noinspection PhpParamsInspection */
            return EGDLogProof::generate(
                $claimer->private_key,
                $cipherText,
                [EGDLogProof::class, 'DLogChallengeGenerator']);

        }, $this->originalCiphertexts, range(0, count($shadow->ciphertexts) - 1));
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadow
     * @return array|null
     */
    public function getRightProofs(Mix $shadow): ?array
    {
        return null;
    }

    // ########################################################################

    /**
     * TODO generalize
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadowMix
     * @param \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet $parameterSet
     * @param EGDLogProof[] $proof
     * @param \App\Models\Trustee $claimer
     * @return bool
     */
    public function checkLeftProof(Mix $shadowMix, MixNodeParameterSet $parameterSet, $proof, Trustee $claimer): bool
    {
        /** @var \App\Voting\CryptoSystems\ElGamal\EGPublicKey $claimerPublicKey */
        $claimerPublicKey = $claimer->public_key;

        // ################### backwards step 3/3 -> unshuffling ###################
        /** @var \App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption\DecryptionReEncryptionParameterSet $shadowMixPS */
        $shadowMixPS = $parameterSet; //$shadowMix->parameterSet;
        $newParameterSet = clone $shadowMixPS;
        $newParameterSet->permutation = $newParameterSet->getShufflingOrderReversed();

        /** @var EGCiphertext[] $unShuffledCiphertexts */
        $unShuffledCiphertexts = $newParameterSet->permuteArray($shadowMix->ciphertexts);

        // ################### backwards step 2/3 -> apply re-encryption to original ###################
        $ReEncryptedOriginalCiphers = array_map(function (CipherText $cipherText, int $idx) use ($shadowMixPS, $shadowMix): EGCiphertext {
            $reEncryptionRandomness = $shadowMixPS->reEncryptionFactors[$idx];
            return $cipherText->reEncryptWithRandomness($reEncryptionRandomness);
        }, $this->originalCiphertexts, range(0, count($shadowMix->ciphertexts) - 1));


        // ################### backwards step 1/3 -> reverse partial decryption (prove) ###################
        foreach ($ReEncryptedOriginalCiphers as $idx => $reEncryptedCipher) {
            $plainText = $unShuffledCiphertexts[$idx];
            $plainText = $plainText->extractPlainTextFromBeta();
            if (!$proof[$idx]->verify($claimerPublicKey, $reEncryptedCipher, $plainText, [EGDLogProof::class, 'DLogChallengeGenerator'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadowMix
     * @param \App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption\DecryptionReEncryptionParameterSet $parameterSet
     * @param $proof
     * @param \App\Models\Trustee $claimer
     * @return bool
     * @throws \Exception
     */
    public function checkRightProof(Mix $shadowMix, MixNodeParameterSet $parameterSet, $proof, Trustee $claimer): bool
    {
        $parameterSetCopy = clone $parameterSet;
        $parameterSetCopy->skipDecryption = true;
        $mix = DecryptionReEncryptionMixNode::forward($this->election, $shadowMix->ciphertexts, $parameterSetCopy);
        return $mix->equals($this->primaryMix);
    }
}
