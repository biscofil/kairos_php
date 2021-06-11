<?php


namespace App\Voting\AnonymizationMethods\MixNets\ReEncryption;


use App\Models\Trustee;
use App\Voting\AnonymizationMethods\MixNets\Mix;
use App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet;
use App\Voting\AnonymizationMethods\MixNets\MixWithShadowMixes;

/**
 * Class ReEncryptionMixWithShadowMixes
 * @package App\Voting\AnonymizationMethods\MixNets\ReEncryption
 */
class ReEncryptionMixWithShadowMixes extends MixWithShadowMixes
{

    use BelongsToReEncryptionMixNode;

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\ReEncryption\ReEncryptionMix $shadow
     * @return \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet
     */
    public function getLeftEquivalenceParameterSet(Mix $shadow): MixNodeParameterSet
    {
        return $shadow->parameterSet;
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\ReEncryption\ReEncryptionMix $shadow
     * @return \App\Voting\AnonymizationMethods\MixNets\ReEncryption\ReEncryptionParameterSet
     */
    public function getRightEquivalenceParameterSet(Mix $shadow): ReEncryptionParameterSet
    {
        return $shadow->parameterSet->combine($this->primaryMix->parameterSet);
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\ReEncryption\ReEncryptionMix $shadow
     * @param \App\Models\Trustee $claimer
     * @return array|null
     */
    public function getLeftProofs(Mix $shadow, Trustee $claimer): ?array
    {
        return null; // not necessary for re encryption mixnet
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\ReEncryption\ReEncryptionMix $shadow
     * @return array|null
     */
    public function getRightProofs(Mix $shadow): ?array
    {
        return null; // not necessary for re encryption mixnet
    }

    // ########################################################################

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\ReEncryption\ReEncryptionMix $shadowMix
     * @param \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet $parameterSet
     * @param $proof
     * @param \App\Models\Trustee $claimer
     * @return bool
     * @throws \Exception
     */
    public function checkLeftProof(Mix $shadowMix, MixNodeParameterSet $parameterSet, $proof, Trustee $claimer): bool
    {
        $mix = ReEncryptingMixNode::forward($this->election, $this->originalCiphertexts, $parameterSet);
        return $mix->equals($shadowMix);
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\ReEncryption\ReEncryptionMix $shadowMix
     * @param \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet $parameterSet
     * @param $proof
     * @param \App\Models\Trustee $claimer
     * @return bool
     * @throws \Exception
     * Same as @see \App\Voting\AnonymizationMethods\MixNets\DecryptionReEncryption\DecryptionReEncryptionMixWithShadowMixes::checkRightProof()
     */
    public function checkRightProof(Mix $shadowMix, MixNodeParameterSet $parameterSet, $proof, Trustee $claimer): bool
    {
        $mix = ReEncryptingMixNode::forward($this->election, $shadowMix->ciphertexts, $parameterSet);
        return $mix->equals($this->primaryMix);
    }

}
