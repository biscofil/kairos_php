<?php


namespace App\Voting\AnonymizationMethods\MixNets\Decryption;


use App\Voting\AnonymizationMethods\MixNets\Mix;
use App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet;
use App\Voting\AnonymizationMethods\MixNets\MixWithShadowMixes;

/**
 * Class DecryptionMixWithShadowMixes
 * @package App\Voting\AnonymizationMethods\MixNets\Decryption
 */
class DecryptionMixWithShadowMixes extends MixWithShadowMixes
{

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadowMix
     * @param \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet $parameterSet
     * @param $proof
     * @return bool
     */
    public function checkLeftProof(Mix $shadowMix, MixNodeParameterSet $parameterSet, $proof): bool
    {
        // TODO: Implement checkLeftProof() method.
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadowMix
     * @param \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet $parameterSet
     * @param $proof
     * @return bool
     */
    public function checkRightProof(Mix $shadowMix, MixNodeParameterSet $parameterSet, $proof): bool
    {
        // TODO: Implement checkRightProof() method.
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadow
     * @return \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet
     */
    public function getLeftEquivalenceParameterSet(Mix $shadow): MixNodeParameterSet
    {
        // TODO: Implement getLeftEquivalenceParameterSet() method.
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\Mix $shadow
     * @return \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet
     */
    public function getRightEquivalenceParameterSet(Mix $shadow): MixNodeParameterSet
    {
        // TODO: Implement getRightEquivalenceParameterSet() method.
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
