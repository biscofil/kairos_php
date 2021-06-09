<?php


namespace Tests\Feature\FullFlow;


use App\Enums\AnonymizationMethodEnum;
use App\Enums\CryptoSystemEnum;
use App\Models\Election;
use App\Models\User;
use App\Models\Voter;
use App\Voting\BallotEncodings\ASCII_JSONBallotEncoding;
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

        $trustee = $election->createPeerServerTrustee(getCurrentServer());

        $kpClass = $election->cryptosystem->getClass()::getKeyPairClass();
//        $ptClass = $election->cryptosystem->getClass()::getPlainTextClass();

        $keyPair = $kpClass::generate();
        $election->public_key = $keyPair->pk;
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
            $plaintext = (ASCII_JSONBallotEncoding::encode($votePlain, EGPlaintext::class))[0]; // TODO check [0]
            $cipher = $keyPair->pk->encrypt($plaintext);
            $decryption = ASCII_JSONBallotEncoding::decode($keyPair->sk->decrypt($cipher));
            self::assertTrue($votePlain === $decryption);

            $data = ['vote' => $cipher->toArray(true)];

            /**
             * @see \App\Http\Controllers\CastVoteController::store()
             */
            $token = $user->getNewJwtToken();
            $response = $this->withHeaders(['Authorization' => "Bearer $token"])
                ->json('POST', "api/elections/$election->slug/cast", $data);

            $this->assertResponseStatusCode(200, $response);
        }

        $election->anonymization_method->getClass()::afterVotingPhaseEnds($election);

        $election->private_key = $keyPair->sk;
        $election->save();
        $election->anonymization_method->getClass()::onSecretKeyReceived($election, $trustee);

    }

}
