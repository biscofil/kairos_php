<?php


namespace Tests\Feature\FullFlow;


use App\Enums\AnonymizationMethodEnum;
use App\Enums\CryptoSystemEnum;
use App\Models\Election;
use App\Models\User;
use App\Models\Voter;
use App\Voting\BallotEncodings\Small_JSONBallotEncoding;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use Tests\TestCase;

/**
 * Class ElGamalDecryptionReEncryptionMixnetElectionTest
 * @package Tests\Feature\FullFlow
 */
class ElGamalDecryptionReEncryptionMixnetElectionTest extends TestCase
{

//    public function formula(){
//        $kp = EGKeyPair::generate();
//        $plainText = new EGPlaintext(randomBIgt(BI(999999)));
//        $cipherText = $kp->pk->encrypt($plainText);
//    }

    /**
     * @test
     */
    public function full()
    {
        $election = Election::factory()->create();
        $election->cryptosystem = CryptoSystemEnum::ElGamal();
        $election->anonymization_method = AnonymizationMethodEnum::DecReEncMixNet();
        $election->save();

        self::createElectionQuestions($election);

//        $kpClass = $election->cryptosystem->getClass()::getKeyPairClass();
//        $ptClass = $election->cryptosystem->getClass()::getPlainTextClass();

        $trustee = $election->createPeerServerTrustee(getCurrentServer());
        $election->min_peer_count_t = 1;
        $election->save();

        $election->preFreeze();
        $election->actualFreeze();
        $election->save();

        // cast votes
        for ($i = 0; $i < 5; $i++) {

            $user = User::factory()->create();

            $voter = new Voter();
            $voter->user_id = $user->id;
            $voter->election_id = $election->id;
            $voter->save();

            // generate a JSON vote structure
            $votePlain = [[rand(1, 3)], [rand(1, 3)], [rand(1, 3)]];

            $plaintext = Small_JSONBallotEncoding::encode($votePlain, EGPlaintext::class);
            $cipher = $election->public_key->encrypt($plaintext);

            $data = ['vote' => $cipher->toArray(true)];

            /**
             * @see \App\Http\Controllers\CastVoteController::store()
             */
            $token = $user->getNewJwtToken();
            $response = $this->withHeaders(['Authorization' => "Bearer $token"])
                ->json('POST', "api/elections/$election->slug/cast", $data);

            self::assertResponseStatusCode(200, $response);
        }

        $election->anonymization_method->getClass()::afterVotingPhaseEnds($election);

        $election->anonymization_method->getClass()::afterSuccessfulMixProcess($election);

        self::assertNotNull($election->tallying_finished_at);

        /** @var \App\Models\Mix $lastMix */
        $lastMix = $election->mixes()->latest()->firstOrFail();
        $lastMix->verify();
        self::assertTrue($lastMix->is_valid);

    }

}
