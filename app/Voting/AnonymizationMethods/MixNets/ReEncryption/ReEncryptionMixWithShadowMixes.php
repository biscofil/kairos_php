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
     * @param \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet $parameterSet
     * @return array|null
     */
    public function getLeftProofs(Mix $shadow, Trustee $claimer, MixNodeParameterSet $parameterSet): ?array
    {
        return null; // not necessary for re encryption mixnet
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\ReEncryption\ReEncryptionMix $shadow
     * @param \App\Models\Trustee $claimer
     * @param \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet $parameterSet
     * @return array|null
     */
    public function getRightProofs(Mix $shadow, Trustee $claimer, MixNodeParameterSet $parameterSet): ?array
    {
        return null; // not necessary for re encryption mixnet
    }

    // ########################################################################

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\ReEncryption\ReEncryptionMix $shadowMix
     * @param \App\Models\Trustee $claimer
     * @return bool
     * @throws \Exception
     */
    public function checkLeftProof(Mix $shadowMix, Trustee $claimer): bool
    {
        $mix = ReEncryptingMixNode::forward($this->election, $this->originalCiphertexts, $shadowMix->parameterSet, $claimer);
        return $mix->equals($shadowMix);
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\ReEncryption\ReEncryptionMix $shadowMix
     * @param \App\Models\Trustee $claimer
     * @return bool
     * @throws \Exception
     */
    public function checkRightProof(Mix $shadowMix, Trustee $claimer): bool
    {
        $mix = ReEncryptingMixNode::forward($this->election, $shadowMix->ciphertexts, $shadowMix->parameterSet, $claimer);
        return $mix->equals($this->primaryMix);
    }

}
