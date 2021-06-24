<?php


namespace Tests\Feature\FullFlow;


use App\Enums\AnonymizationMethodEnum;
use App\Enums\CryptoSystemEnum;
use App\Models\Election;
use App\Models\PeerServer;
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

        $peerServer = PeerServer::factory()->create();
        $trustee = $election->createPeerServerTrustee($peerServer);
        $trustee->generateKeyPair();
        $trustee->accepts_ballots = true;
        $trustee->save();

        self::createElectionQuestions($election);

        self::assertTrue($election->preFreeze());
        $election->actualFreeze();

        // cast votes
        for ($i = 0; $i < 5; $i++) {
            $this->addVote($election, [[1], [2], [3]]);
        }

        self::purgeJobs();
        $election->anonymization_method->getClass()::afterVotingPhaseEnds($election, $trustee);
        self::assertNotEquals(0, self::getPendingJobCount());

        self::runFirstPendingJob();

        $election->anonymization_method->getClass()::onSecretKeyReceived($election, $trustee);

        self::assertNotNull($election->tallying_finished_at);

        self::assertNotEquals(0, $election->mixes()->count());
        /** @var \App\Models\Mix $lastMix */
        $lastMix = $election->mixes()->latest()->firstOrFail();
        $lastMix->verify();
        self::assertTrue($lastMix->is_valid);
    }

}
