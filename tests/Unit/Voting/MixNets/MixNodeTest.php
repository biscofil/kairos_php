<?php

namespace Tests\Unit\Voting\MixNets;

use App\Voting\AnonymizationMethods\MixNets\ReEncryptingMixNode;
use App\Voting\BallotEncodings\JsonBallotEncoding;
use App\Voting\CryptoSystems\ElGamal\EGCiphertext;
use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use Exception;
use Illuminate\Support\Str;
use phpseclib3\Math\BigInteger;
use Tests\TestCase;

/**
 * Class MixNodeTest
 * @package Tests\Unit\Voting\MixNets
 */
class MixNodeTest extends TestCase
{

    /**
     * @ TODO test
     * @throws Exception
     */
    public function works()
    {

        $electionKeyPair = EGKeyPair::generate();

        for ($i = 0; $i < 5; $i++) {
            $obj = [
                'initial_pos' => $i,
                'v' => Str::random(3)
            ];
            $msgs = JsonBallotEncoding::encode($obj, EGPlaintext::class);
            $msg = $msgs[0];
            $ciphers[] = $electionKeyPair->pk->encrypt($msg);
        }

//        // 2 nodes
//        $ciphers = (new MixNode($original_ciphers))->originalCiphertexts;
//        $ciphers = (new MixNode($ciphers))->originalCiphertexts;
//        // assert same as the original
//        $this->assertTrue(collect($ciphers)->map(function ($cipher) use ($electionKeyPair) {
//            return json_decode($electionKeyPair->sk->decrypt($cipher)->toString(), true);
//        })->pluck('initial_pos')->diffAssoc(collect($original_ciphers))->isEmpty());

    }

    /**
     * @test
     * @throws Exception
     */
    public function proof()
    {

        $keyPair = EGKeyPair::generate();

        $plain = new EGPlaintext(BigInteger::random(20));

        $ciphertexts = [];
        for ($i = 0; $i < rand(5, 10); $i++) {
            $ciphertexts[] = $keyPair->pk->encrypt($plain);
        }

        $mixNode = new ReEncryptingMixNode();
        $shadowMixCount = rand(2, 3);
        $mixNode->generate($keyPair->pk, $ciphertexts, $shadowMixCount);

        $mixNode->generateFiatShamirChallengeBits();
        $this->assertEquals($shadowMixCount, strlen($mixNode->challengeBits));

        $parameterSets = $mixNode->generateProofs();

        foreach ($parameterSets as $idx => $parameterSet) {
            $challengeBit = $mixNode->challengeBits[$idx];

            if ($challengeBit == "0") {
                $mix = ReEncryptingMixNode::forward($keyPair->pk, $ciphertexts, $parameterSet);
                $this->assertTrue($mixNode->shadowMixes[$idx]->equals($mix, $keyPair->sk));
            } else {
                $mix = ReEncryptingMixNode::forward($keyPair->pk, $mixNode->shadowMixes[$idx]->ciphertexts, $parameterSet);
                $this->assertTrue($mixNode->primaryMix->equals($mix, $keyPair->sk));
            }

//            if ($challengeBit == "0") {
//                $mix = new Mix($mixNode->shadowMixes[$idx]->ciphertexts, $parameterSet, true);
//                $this->assertTrue(
//                    self::areCiphertextListsEquals($ciphertexts, $mix->ciphertexts)
//                );
//            } else {
//                $mix = new Mix($mixNode->primaryMix->ciphertexts, $parameterSet, true);
//                $this->assertTrue(
//                    self::areCiphertextListsEquals($mixNode->shadowMixes[$idx]->ciphertexts, $mix->ciphertexts)
//                );
//            }
        }

    }

    /**
     * @param EGCiphertext[] $a
     * @param EGCiphertext[] $b
     * @return bool
     * @throws Exception
     */
    public static function areCiphertextListsEquals(array $a, array $b): bool
    {
        for ($i = 0; $i < count($a); $i++) {
            if (!$a[$i]->equals($b[$i])) {
                dump($a[$i]->alpha->toHex());
                dump($b[$i]->alpha->toHex());
                dump($a[$i]->beta->toHex());
                dump($b[$i]->beta->toHex());
                return false;
            }
        }
        return true;
    }

    /**
     * @ TODO test
     * @throws Exception
     */
    public function store_load()
    {

        $keyPair = EGKeyPair::generate();

        $ciphertexts = [];
        for ($i = 0; $i < rand(5, 10); $i++) {
            $ciphertexts[] = $keyPair->pk->encrypt(new EgPlaintext(BigInteger::random(20)));
        }

        $mixNode = new ReEncryptingMixNode();
        $shadowMixCount = rand(2, 3);
        $mixNode->generate($keyPair->pk, $ciphertexts, $shadowMixCount);
        $mixNode->generateFiatShamirChallengeBits();
        $mixNode->generateProofs();

        $mixNode->store('test');
        $mixNode->store('test_pk', true);

        $storedMix = ReEncryptingMixNode::load('test_pk');

        $this->assertTrue($storedMix->primaryMix->equals($mixNode->primaryMix, $keyPair->sk));

    }

}


