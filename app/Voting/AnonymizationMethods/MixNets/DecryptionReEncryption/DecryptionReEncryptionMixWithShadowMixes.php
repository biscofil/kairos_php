<?php


namespace App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption;


use App\Models\Trustee;
use App\Voting\AnonymizationMethods\MixNets\Mix;
use App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet;
use App\Voting\AnonymizationMethods\MixNets\MixWithShadowMixes;
use App\Voting\CryptoSystems\CipherText;
use App\Voting\CryptoSystems\ElGamal\EGCiphertext;
use App\Voting\CryptoSystems\ElGamal\EGDLogProof;
use phpseclib3\Math\BigInteger;

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
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadowMix
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $primaryMix
     * @return DecryptionReEncryptionParameterSet
     */
    public function getRightEquivalenceParameterSet(Mix $shadowMix, Mix $primaryMix): MixNodeParameterSet
    {
        /** @var DecryptionReEncryptionParameterSet $out */
        $out = $shadowMix->parameterSet->combine($primaryMix->parameterSet);
        $out->decryption = true;
        return $out;
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadow
     * @param \App\Models\Trustee $claimer
     * @param \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet $parameterSet
     * @return EGDLogProof[][]|null
     */
    public function getLeftProofs(Mix $shadow, Trustee $claimer, MixNodeParameterSet $parameterSet): ?array
    {
        return null;
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadow
     * @param \App\Models\Trustee $claimer
     * @param \App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption\DecryptionReEncryptionParameterSet $parameterSet
     * @return array|null
     */
    public function getRightProofs(Mix $shadow, Trustee $claimer, MixNodeParameterSet $parameterSet): ?array
    {
        /** @var \App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption\DecryptionReEncryptionParameterSet $shadowMixPS */
        return array_map(function (EGCiphertext $cipherText, BigInteger $reEncryptionFactor) use ($claimer) {

            $reEncryptionRandomness = $reEncryptionFactor;
            $cipherText = $cipherText->reEncryptWithRandomness($reEncryptionRandomness);

            /** @noinspection PhpParamsInspection */
            return EGDLogProof::generate($claimer->private_key, $cipherText);

        }, $shadow->ciphertexts, $parameterSet->reEncryptionFactors);
    }

    // ########################################################################

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $inputCipherTexts
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadowMix
     * @param \App\Models\Trustee $claimer
     * @return bool
     * @throws \Exception
     */
    public function checkLeftProof(Mix $inputCipherTexts, Mix $shadowMix, Trustee $claimer): bool
    {
        $mix = DecryptionReEncryptionMixNode::forward($inputCipherTexts, $shadowMix->parameterSet, $claimer);
        return $mix->equals($shadowMix);
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadowMix
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $primaryMix
     * @param \App\Models\Trustee $claimer
     * @return bool
     * @throws \Exception
     */
    public function checkRightProof(Mix $shadowMix, Mix $primaryMix, Trustee $claimer): bool
    {
        /** @var \App\Voting\CryptoSystems\ElGamal\EGPublicKey $claimerPublicKey */
        $claimerPublicKey = $claimer->public_key;

        // ################### backwards step 3/3 -> unshuffling ###################
        /** @var \App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption\DecryptionReEncryptionParameterSet $shadowMixPS */
        $shadowMixPS = $shadowMix->parameterSet;
        $newParameterSet = clone $shadowMixPS;
        $newParameterSet->permutation = $newParameterSet->getShufflingOrderReversed();

        /** @var EGCiphertext[] $unShuffledCiphertexts */
        $unShuffledCiphertexts = $newParameterSet->permuteArray($primaryMix->ciphertexts);

        // ################### backwards step 2/3 -> apply re-encryption to original ###################
        $ReEncryptedOriginalCiphers = array_map(function (CipherText $cipherText, BigInteger $reEncryptionFactor): EGCiphertext {
            return $cipherText->reEncryptWithRandomness($reEncryptionFactor);
        }, $shadowMix->ciphertexts, $shadowMixPS->reEncryptionFactors);

        // ################### backwards step 1/3 -> reverse partial decryption (prove) ###################
        foreach ($ReEncryptedOriginalCiphers as $idx => $reEncryptedCipher) {
            $plainText = $unShuffledCiphertexts[$idx]->extractPlainTextFromBeta(true);
            if (!$shadowMix->proofs[$idx]->isValid($claimerPublicKey, $reEncryptedCipher, $plainText)) {
                return false;
            }
        }
        return true;
    }
}
