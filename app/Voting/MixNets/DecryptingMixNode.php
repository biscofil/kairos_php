<?php


namespace App\Voting\MixNets;


use App\Voting\CryptoSystems\PublicKey;

/**
 * Class DecryptingMixNode
 * @package App\Voting\MixNets
 */
class DecryptingMixNode extends MixNode
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
