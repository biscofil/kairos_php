<?php


namespace Tests\Feature\FullFlow;


use App\Enums\AnonymizationMethodEnum;
use App\Enums\CryptoSystemEnum;
use App\Models\Election;
use App\Models\PeerServer;
use App\Models\User;
use App\Models\Voter;
use App\Voting\BallotEncodings\JsonBallotEncoding;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use App\Voting\CryptoSystems\RSA\RSAPlaintext;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Class ElGamalMixnetElectionTest
 * @package Tests\Feature\FullFlow
 */
class ElGamalMixnetElectionTest extends TestCase
{

    /**
     * @test
     */
    public function full()
    {
        $election = Election::factory()->create();
        $election->cryptosystem = CryptoSystemEnum::ElGamal();
        $election->anonymization_method = AnonymizationMethodEnum::EncMixNet();
        $election->save();

        $trustee = $election->createPeerServerTrustee(getCurrentServer());

        $kpClass = $election->cryptosystem->getClass()::getKeyPairClass();
        $ptClass = $election->cryptosystem->getClass()::getPlainTextClass();

        $keyPair = $kpClass::generate();
        $election->public_key = $keyPair->pk;
        $election->private_key = $keyPair->sk;
        $election->save();

        $election->frozen_at = Carbon::now(); // todo use freeze()
        $election->save();

        // cast votes
        for ($i = 0; $i < 5; $i++) {

            $user = User::factory()->create();

            $voter = new Voter();
            $voter->user_id = $user->id;
            $voter->election_id = $election->id;
            $voter->save();

            // generate a JSON vote structure
            $votePlain = [
                Str::random(10) => Str::random(10),
                Str::random(10) => [
                    Str::random(10),
                    Str::random(10),
                ]
            ];

            $plaintext = (ASCII_JSONBallotEncoding::encode($votePlain, EGPlaintext::class))[0];
            $cipher = $keyPair->pk->encrypt($plaintext);

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


    }

}
