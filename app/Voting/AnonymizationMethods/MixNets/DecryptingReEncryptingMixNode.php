<?php


namespace App\Voting\AnonymizationMethods\MixNets;


use App\Voting\CryptoSystems\PublicKey;

/**
 * Class DecryptingReEncryptingMixNode
 * @package App\Voting\AnonymizationMethods\MixNets
 */
class DecryptingReEncryptingMixNode extends MixNode
{

    /**
     * @param PublicKey $pk
     * @param array $ciphertexts
     * @param MixNodeParameterSet|null $parameterSet
     * @return Mix
     */
    public static function forward(PublicKey $pk, array $ciphertexts, MixNodeParameterSet $parameterSet = null): Mix
    {
        //TODO
    }

    /**
     * @param PublicKey $pk
     * @param array $ciphertexts
     * @param MixNodeParameterSet|null $parameterSet
     * @return Mix
     */
    public static function backward(PublicKey $pk, array $ciphertexts, MixNodeParameterSet $parameterSet = null): Mix
    {
        // TODO: Implement backward() method.
    }
}
