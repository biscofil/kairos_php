<?php

namespace Tests\Unit\Voting\MixNets;

use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
use App\Voting\MixNets\MixNodeParameterSet;
use Exception;
use phpseclib3\Math\BigInteger;
use Tests\TestCase;

/**
 * Class MixNodeParameterSetTest
 * @package Tests\Unit\Voting\MixNets
 */
class MixNodeParameterSetTest extends TestCase
{

    /**
     * @test
     * @throws Exception
     */
    public function combine()
    {

        $keyPair = EGKeyPair::generate();

        $shadowMixPS = new MixNodeParameterSet($keyPair->pk, [
            BI(10),
            BI(11),
            BI(12),
            BI(13),
            BI(14)
        ], [2, 0, 4, 3, 1]);

        $primaryMixPS = new MixNodeParameterSet($keyPair->pk, [
            BI(30),
            BI(31),
            BI(32),
            BI(33),
            BI(34)
        ], [3, 2, 1, 0, 4]);

        $comb = $shadowMixPS->combine($primaryMixPS);

        $this->assertEquals([3, 0, 4, 1, 2], $comb->permutation);
        foreach ($comb->reEncryptionFactors as $factor) {
            $this->assertTrue($factor->equals(BI(20)));
        }

    }

}
