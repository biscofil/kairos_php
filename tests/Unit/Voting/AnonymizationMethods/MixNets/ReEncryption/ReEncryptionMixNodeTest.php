<?php

namespace Tests\Unit\Voting\AnonymizationMethods\MixNets\ReEncryption;

use App\Enums\CryptoSystemEnum;
use App\Models\Election;
use App\Models\PeerServer;
use App\Voting\AnonymizationMethods\MixNets\ReEncryption\ReEncryptingMixNode;
use App\Voting\BallotEncodings\JsonBallotEncoding;
use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
use App\Voting\CryptoSystems\ElGamal\EGParameterSet;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use Exception;
use Illuminate\Support\Str;
use phpseclib3\Math\BigInteger;
use Tests\TestCase;

/**
 * Class ReEncryptioMixNodeTest
 * @package Tests\Unit\Voting\MixNets
 */
class ReEncryptionMixNodeTest extends TestCase
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

        /** @var Election $election */
        $election = Election::factory()->create();
        $election->cryptosystem = CryptoSystemEnum::ElGamal(); // TODO remove
        $election->save();

        $election->createPeerServerTrustee(PeerServer::me());

        $kpClass = $election->cryptosystem->getClass()::getKeyPairClass();
        $ptClass = $election->cryptosystem->getClass()::getPlainTextClass();

        $ps = EGParameterSet::random(20); // TODO remove
        $keyPair = $kpClass::generate($ps);
        $election->public_key = $keyPair->pk;
        $election->private_key = $keyPair->sk;
        $election->save();

        // generate ballots
        $ciphertexts = [];
        for ($i = 0; $i < 5; $i++) { // TODO rand(5, 10)
            $plain = new $ptClass(BigInteger::random(15));
            $ciphertexts[] = $keyPair->pk->encrypt($plain);
        }

        $mixNode = new ReEncryptingMixNode();
        $shadowMixCount = rand(4, 5);
        $primaryShadowMixes = $mixNode->generate($election, $ciphertexts, $shadowMixCount);

        // generate bits
        $challengeBits = $primaryShadowMixes->getFiatShamirChallengeBits();
        static::assertEquals($shadowMixCount, strlen($challengeBits));

        $primaryShadowMixes->store('test_before', true);

        // set them
        $primaryShadowMixes->setChallengeBits($challengeBits);

        // generate proof
        $primaryShadowMixes->generateProofs();

        // check parameter sets have been removed
        foreach ($primaryShadowMixes->shadowMixes as $shadowMix){
            static::assertNull($shadowMix->parameterSet);
        }
        static::assertNull($primaryShadowMixes->primaryMix->parameterSet);

        $primaryShadowMixes->store('test_after', true);

        // check proof
        static::assertTrue($primaryShadowMixes->isProofValid());

    }

    /**
     * @ TODO test
     * @throws Exception
     */
    public function store_load()
    {

        /** @var Election $election */
        $election = Election::factory()->create();

        $kpClass = $election->cryptosystem->getClass()::getKeyPairClass();
        $ptClass = $election->cryptosystem->getClass()::getPlainTextClass();

        $keyPair = $kpClass::generate();
        $election->public_key = $keyPair->pk;
        $election->private_key = $keyPair->sk;
        $election->save();

        $ciphertexts = [];
        for ($i = 0; $i < rand(5, 10); $i++) {
            $ciphertexts[] = $keyPair->pk->encrypt(new $ptClass(BigInteger::random(20)));
        }

        $mixNode = new ReEncryptingMixNode();
        $shadowMixCount = rand(2, 3);
        $mixNode->generate($election, $ciphertexts, $shadowMixCount);
        $mixNode->generateFiatShamirChallengeBits();
        $mixNode->generateProofs();

        $mixNode->store('test');
        $mixNode->store('test_pk', true);

        $storedMix = ReEncryptingMixNode::load('test_pk');

        static::assertTrue($storedMix->primaryMix->equals($mixNode->primaryMix, $keyPair->sk));

    }

}

