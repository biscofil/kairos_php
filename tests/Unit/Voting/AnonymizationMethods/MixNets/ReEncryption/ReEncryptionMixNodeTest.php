<?php

namespace Tests\Unit\Voting\AnonymizationMethods\MixNets\ReEncryption;

use App\Enums\CryptoSystemEnum;
use App\Models\Election;
use App\Voting\AnonymizationMethods\MixNets\ReEncryption\ReEncryptingMixNode;
use App\Voting\AnonymizationMethods\MixNets\ReEncryption\ReEncryptionMixWithShadowMixes;
use App\Voting\BallotEncodings\JsonBallotEncoding;
use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
use App\Voting\CryptoSystems\ElGamal\EGParameterSet;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use Exception;
use Illuminate\Support\Facades\Storage;
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
     * @ test
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

        $election = Election::factory()->create();
        $election->cryptosystem = CryptoSystemEnum::ElGamal(); // TODO remove
        $election->save();

        $election->createPeerServerTrustee(getCurrentServer());

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

        $shadowMixCount = rand(4, 5);
        $primaryShadowMixes = ReEncryptingMixNode::generate($election, $ciphertexts, $shadowMixCount);

        // generate bits
        $challengeBits = $primaryShadowMixes->getFiatShamirChallengeBits();
        static::assertEquals($shadowMixCount, strlen($challengeBits));

        // set them
        $primaryShadowMixes->setChallengeBits($challengeBits);

        // generate proof
        $primaryShadowMixes->generateProofs();

        // check parameter sets have been removed
        foreach ($primaryShadowMixes->shadowMixes as $shadowMix) {
            static::assertNull($shadowMix->parameterSet);
        }
        static::assertNull($primaryShadowMixes->primaryMix->parameterSet);

        // check proof
        static::assertTrue($primaryShadowMixes->isProofValid());

    }

    /**
     * @test
     * @throws Exception
     */
    public function store_load_elgamal()
    {

        $election = Election::factory()->create();
        $election->cryptosystem = CryptoSystemEnum::ElGamal();
        $election->save();

        $election->createPeerServerTrustee(getCurrentServer());

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

        $shadowMixCount = rand(2, 3);
        $primaryShadowMixes = ReEncryptingMixNode::generate($election, $ciphertexts, $shadowMixCount);
        $primaryShadowMixes->challengeBits = $primaryShadowMixes->getFiatShamirChallengeBits();
        $primaryShadowMixes->generateProofs();

        $file1 = 'test_without_pk';
        $primaryShadowMixes->store($file1);

        $file2 = 'test_with_pk';
        $primaryShadowMixes->store($file2, true);

        $primaryShadowMixes = ReEncryptionMixWithShadowMixes::load('test_without_pk');

        Storage::delete(['test_without_pk.json', 'test_with_pk.json']);

        // check proof
        static::assertTrue($primaryShadowMixes->isProofValid());

        $primaryShadowMixes->deleteFile($file1);
        $primaryShadowMixes->deleteFile($file2);

    }

}


