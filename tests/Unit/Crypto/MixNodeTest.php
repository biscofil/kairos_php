<?php

namespace Tests\Unit\Crypto;

use App\Crypto\EGKeyPair;
use App\Crypto\EGPlaintext;
use App\Crypto\MixNets\Mix;
use App\Crypto\MixNets\MixNode;
use Illuminate\Support\Str;
use phpseclib3\Math\BigInteger;
use Tests\TestCase;

/**
 * Class MixNodeTest
 * @package Tests\Unit\Crypto
 */
class MixNodeTest extends TestCase
{

    /**
     * @test
     * @throws \Exception
     */
    public function works()
    {

        $electionKeyPair = EGKeyPair::generate();

        $original_ciphers = [];

        for ($i = 0; $i < 5; $i++) {
            $obj = [
                'initial_pos' => $i,
                'v' => Str::random(3)
            ];
            $plain = json_encode($obj);
            $msg = EGPlaintext::fromString($plain, $electionKeyPair->pk);
            $ciphers[] = $msg->encrypt();
        }

        // 2 nodes
        $ciphers = (new MixNode($original_ciphers))->toArray();
        $ciphers = (new MixNode($ciphers))->toArray();

        // assert same as the original
        $this->assertTrue(collect($ciphers)->map(function ($cipher) use ($electionKeyPair) {
            return json_decode($electionKeyPair->sk->decrypt($cipher)->toString(), true);
        })->pluck('initial_pos')->diffAssoc(collect($original_ciphers))->isEmpty());

    }

    /**
     * @test
     * @throws \Exception
     */
    public function proof()
    {

        $keyPair = EGKeyPair::generate();

        $ciphertexts = [];

        $plain = new EGPlaintext(BigInteger::random(20), $keyPair->pk);

        for ($i = 0; $i < rand(5, 10); $i++) {
            $ciphertexts[] = $plain->encrypt();
        }

        $mixNode = new MixNode($ciphertexts);
        $shadowMixCount = 2;
        $mixNode->generate($shadowMixCount);

        $bits = $mixNode->generateFiatShamirChallengeBits();
        $this->assertEquals($shadowMixCount, strlen($bits));

        $parameterSets = $mixNode->generateProofs($bits);

        foreach ($parameterSets as $idx => $parameterSet) {
            $challengeBit = $bits[$idx];
            if ($challengeBit == "0") {
                $mix = new Mix($ciphertexts, $parameterSet);
                $this->assertTrue($mixNode->shadowMixes[$idx]->equals($mix,$keyPair->sk));
            } else {
                $mix = new Mix($mixNode->shadowMixes[$idx]->ciphertexts, $parameterSet);
                $this->assertTrue($mixNode->primaryMix->equals($mix,$keyPair->sk));
            }
        }

    }

}


