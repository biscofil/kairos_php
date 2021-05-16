<?php

namespace Tests\Http\Controllers;

use App\Models\CastVote;
use App\Models\Election;
use App\Models\PeerServer;
use App\Models\User;
use App\Models\Voter;
use App\Voting\BallotEncodings\JsonBallotEncoding;
use App\Voting\CryptoSystems\RSA\RSAKeyPair;
use App\Voting\CryptoSystems\RSA\RSAPlaintext;
use Illuminate\Support\Str;
use Tests\TestCase;

class CastVoteControllerTest extends TestCase
{

    /**
     * @test
     * @throws \Exception
     */
    public function store()
    {

        /** @var User $user */
        $user = User::factory()->create();

        /** @var Election $election */
        $election = Election::factory()->withAdmin($user)->frozen()->create();
        $election->cryptosystem = 'rsa';
        $election->createPeerServerTrustee(PeerServer::me());
        $election->cryptosystem->getCryptoSystemClass()::onElectionFreeze($election); // generateCombinedPublicKey
        $election->save();

        $voter = new Voter();
        $voter->user_id = $user->id;
        $voter->election_id = $election->id;
        $voter->save();

        $this->assertEquals(0, $voter->votes()->count());

        // generate key
        $keyPair = RSAKeyPair::generate();

        // generate a JSON vote structure
        $votePlain = [
            Str::random(10) => Str::random(10),
            Str::random(10) => [
                Str::random(10),
                Str::random(10),
            ]
        ];

        // encrypt it
        /** @var RSAPlaintext $plaintext */
        $plaintext = (JsonBallotEncoding::encode($votePlain, RSAPlaintext::class))[0];
        $cipher = $keyPair->pk->encrypt($plaintext);

        $data = ['vote' => $cipher->toArray(true)];

        /**
         * @see \App\Http\Controllers\CastVoteController::store()
         */
        $token = $user->getNewJwtToken();
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->json('POST', 'api/elections/' . $election->slug . '/cast', $data);

        $this->assertResponseStatusCode(201, $response);

        $this->assertEquals(1, $voter->votes()->count());

        /** @var CastVote $voteCast */
        $voteCast = $voter->votes()->first();

        $out = $keyPair->sk->decrypt($voteCast->vote);
        $this->assertEquals($votePlain, JsonBallotEncoding::decode($out));

    }
}
