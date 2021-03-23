<?php


namespace Tests\Unit\Crypto;


use App\Crypto\MixNets\MixNodeParameterSet;
use phpseclib3\Math\BigInteger;
use Tests\TestCase;

class MixNodeParameterSetTest extends TestCase
{

    /**
     * @test
     * @throws \Exception
     */
    public function combine()
    {

        $shadow = new MixNodeParameterSet([
            new BigInteger(10),
            new BigInteger(11),
            new BigInteger(12),
            new BigInteger(13),
            new BigInteger(14)
        ], [2, 0, 4, 3, 1]);

        $primary = new MixNodeParameterSet([
            new BigInteger(30),
            new BigInteger(31),
            new BigInteger(32),
            new BigInteger(33),
            new BigInteger(34)
        ], [3, 2, 1, 0, 4]);

        $comb = $shadow->combine($primary);

        $this->assertEquals([3, 0, 4, 1, 2], $comb->permutation);
        foreach ($comb->reEncryptionFactors as $factor) {
            $this->assertTrue($factor->equals(new BigInteger(20)));
        }

    }

}
