<?php


namespace Tests\Feature\FullFlow;


use App\Enums\AnonymizationMethodEnum;
use App\Enums\CryptoSystemEnum;
use App\Models\Election;
use Tests\TestCase;

/**
 * Class ElGamalEncryptionMixnetElectionTest
 * @package Tests\Feature\FullFlow
 */
class ElGamalEncryptionMixnetElectionTest extends TestCase
{

    /**
     * @test
     */
    public function full()
    {
        $election = Election::factory()->create();
        $election->cryptosystem = CryptoSystemEnum::ElGamal();
        $election->anonymization_method = AnonymizationMethodEnum::EncMixNet();
        $election->min_peer_count_t = 1;
        $election->save();

        $kpClass = $election->cryptosystem->getClass()::getKeyPairClass();

        $trustee = $election->createPeerServerTrustee(getCurrentServer());
        $trusteeKeyPair = $kpClass::generate();
        $trustee->public_key = $trusteeKeyPair->pk;
        $trustee->save();

        $election->preFreeze();
        $election->actualFreeze();

        self::createElectionQuestions($election);

        // cast votes
        for ($i = 0; $i < 5; $i++) {
            $this->addVote($election, [[1], [2], [3]]);
        }

        $election->anonymization_method->getClass()::afterVotingPhaseEnds($election);

//        $election->private_key = $keyPair->sk;
//        $election->save();

        $trustee->private_key = $trusteeKeyPair->sk;
        $trustee->save();
        $election->anonymization_method->getClass()::onSecretKeyReceived($election, $trustee);

        self::assertNotNull($election->tallying_finished_at);

        /** @var \App\Models\Mix $lastMix */
        $lastMix = $election->mixes()->latest()->firstOrFail();
        $lastMix->verify();
        self::assertTrue($lastMix->is_valid);
    }

}
