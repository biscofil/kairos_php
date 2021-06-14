<?php


namespace App\Voting\AnonymizationMethods\MixNets\Decryption;


use App\Models\Trustee;
use App\Voting\AnonymizationMethods\MixNets\Mix;
use App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet;
use App\Voting\AnonymizationMethods\MixNets\MixWithShadowMixes;

/**
 * Class DecryptionMixWithShadowMixes
 * @package App\Voting\AnonymizationMethods\MixNets\Decryption
 */
class DecryptionMixWithShadowMixes extends MixWithShadowMixes
{

    use BelongsToDecryptionMixNode;

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadowMix
     * @param \App\Models\Trustee $claimer
     * @return bool
     */
    public function checkLeftProof(Mix $shadowMix, Trustee $claimer): bool
    {
        return false;
        // TODO: Implement checkLeftProof() method.
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadowMix
     * @param \App\Models\Trustee $claimer
     * @return bool
     */
    public function checkRightProof(Mix $shadowMix, Trustee $claimer): bool
    {
        return false;
        // TODO: Implement checkRightProof() method.
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadow
     * @return \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet
     */
    public function getLeftEquivalenceParameterSet(Mix $shadow): MixNodeParameterSet
    {
        return $shadow->parameterSet;
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadow
     * @return \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet
     */
    public function getRightEquivalenceParameterSet(Mix $shadow): MixNodeParameterSet
    {
        return $shadow->parameterSet->combine($this->primaryMix->parameterSet);
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadow
     * @param \App\Models\Trustee $claimer
     * @param \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet $parameterSet
     */
    public function getLeftProofs(Mix $shadow, Trustee $claimer, MixNodeParameterSet $parameterSet): ?array
    {
//        foreach ($shadow->ciphertexts as $cipherText){
//
//        }
        return null; // TODO DLOG proof
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadow
     * @param \App\Models\Trustee $claimer
     * @param \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet $parameterSet
     */
    public function getRightProofs(Mix $shadow, Trustee $claimer, MixNodeParameterSet $parameterSet): ?array
    {
        return null; // TODO DLOG proof
    }

}
