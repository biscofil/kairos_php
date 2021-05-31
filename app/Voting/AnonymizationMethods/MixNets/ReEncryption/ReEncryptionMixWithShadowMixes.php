<?php


namespace App\Voting\AnonymizationMethods\MixNets\ReEncryption;


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
     * @return null
     */
    public function getLeftProof(Mix $shadow)
    {
        return null; // not necessary for re encryption mixnet
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\ReEncryption\ReEncryptionMix $shadow
     * @return null
     */
    public function getRightProof(Mix $shadow)
    {
        return null; // not necessary for re encryption mixnet
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\ReEncryption\ReEncryptionMix $shadowMix
     * @param \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet $parameterSet
     * @param $proof
     * @return bool
     * @throws \Exception
     */
    public function checkLeftProof(Mix $shadowMix, MixNodeParameterSet $parameterSet, $proof): bool
    {
        $mix = ReEncryptingMixNode::forward($this->election, $this->originalCiphertexts, $parameterSet);
        return $mix->equals($shadowMix);
    }

    /**
     * @param \App\Voting\AnonymizationMethods\MixNets\ReEncryption\ReEncryptionMix $shadowMix
     * @param \App\Voting\AnonymizationMethods\MixNets\MixNodeParameterSet $parameterSet
     * @param $proof
     * @return bool
     * @throws \Exception
     */
    public function checkRightProof(Mix $shadowMix, MixNodeParameterSet $parameterSet, $proof): bool
    {
        $mix = ReEncryptingMixNode::forward($this->election, $shadowMix->ciphertexts, $parameterSet);
        return $mix->equals($this->primaryMix);
    }

}
