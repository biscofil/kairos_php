<?php

namespace Tests\Unit\Voting\AnonymizationMethods\MixNets\ReEncryption;

use App\Enums\AnonymizationMethodEnum;
use App\Enums\CryptoSystemEnum;
use App\Models\Election;
use App\Models\Mix;
use App\Voting\BallotEncodings\Small_JSONBallotEncoding;
use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use Exception;
use Illuminate\Support\Str;
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
            $msgs = Small_JSONBallotEncoding::encode($obj, EGPlaintext::class);
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
        $election->anonymization_method = AnonymizationMethodEnum::EncMixNet(); // TODO remove
        $election->save();

        $trustee = $election->createPeerServerTrustee(getCurrentServer());
        $trustee->generateKeyPair();

        $election->preFreeze();
        $election->actualFreeze();

        for ($i = 0; $i < 5; $i++) {
            self::addVote($election, [[rand(1, 3)]]);
        }

        $mixModel = new Mix();
        $mixModel->round = 1;
        $mixModel->previous_mix_id = null;
        $mixModel->uuid = Mix::getNewUUID()->string;
        $mixModel->trustee()->associate($trustee);
        $mixModel->hash = Str::random(100);
        $mixModel->shadow_mix_count = rand(4, 5);
        $mixModel->save();

        $primaryShadowMixes = $mixModel->generateMixAndShadowMixes();

        $challengeBits = $primaryShadowMixes->getFiatShamirChallengeBits(); // generate bits
        static::assertEquals($mixModel->shadow_mix_count, strlen($challengeBits));
        $mixModel->setChallengeBits($challengeBits); // set them

        // generate proof
        $primaryShadowMixes->generateProofs($trustee);

        // check parameter sets have been removed
        static::assertNull($primaryShadowMixes->getPrimaryMix()->parameterSet);

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
        $election->anonymization_method = AnonymizationMethodEnum::EncMixNet();
        $election->save();

        $trustee = $election->createPeerServerTrustee(getCurrentServer());
        $trustee->generateKeyPair();

        $election->preFreeze();
        $election->actualFreeze();

        for ($i = 0; $i < rand(5, 10); $i++) {
            $this->addVote($election, [[1], [2], [3]]);
        }

        $mixModel = new Mix();
        $mixModel->round = 1;
        $mixModel->previous_mix_id = null;
        $mixModel->uuid = Mix::getNewUUID()->string;
        $mixModel->trustee()->associate($trustee);
        $mixModel->hash = Str::random(100);
        $mixModel->shadow_mix_count = rand(2, 3);
        $mixModel->save();

        $primaryShadowMixes = $mixModel->generateMixAndShadowMixes();
        $mixModel->setChallengeBits($primaryShadowMixes->getFiatShamirChallengeBits());
        $primaryShadowMixes->generateProofs($trustee);

//        $file1 = 'mix_test.json';
//        $primaryShadowMixes->store($file1);

//        $primaryShadowMixes = $mixModel->getMixWithShadowMixes();

//        Storage::delete([$file1]);

        // check proof
        static::assertTrue($primaryShadowMixes->isProofValid());

//        $primaryShadowMixes->deleteFile($file1);

        $election->deleteFiles();

    }

}


