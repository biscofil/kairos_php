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

//        $ptClass = $election->cryptosystem->getClass()::getPlainTextClass();

        $keyPair = $kpClass::generate();
        $election->preFreeze();
        $election->actualFreeze();

        self::createElectionQuestions($election);

        // cast votes
        for ($i = 0; $i < 5; $i++) {

            $user = User::factory()->create();

            $voter = new Voter();
            $voter->user_id = $user->id;
            $voter->election_id = $election->id;
            $voter->save();

            $votePlain = [[1], [3], [2]];
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

//        $election->private_key = $keyPair->sk;
//        $election->save();

        $trustee->private_key = $trusteeKeyPair->sk;
        $trustee->save();
        $election->anonymization_method->getClass()::onSecretKeyReceived($election, $trustee);

        self::assertNotNull($election->tallying_finished_at);
    }

}
