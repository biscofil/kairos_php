<?php

namespace Tests\Http\Controllers;

use App\Models\CastVote;
use App\Models\Election;
use App\Models\User;
use App\Models\Voter;
use App\Voting\BallotEncodings\Small_JSONBallotEncoding;
use App\Voting\CryptoSystems\RSA\RSAKeyPair;
use App\Voting\CryptoSystems\RSA\RSAPlaintext;
use Tests\TestCase;

class CastVoteControllerTest extends TestCase
{

    /**
     * @test
     * @throws \Exception
     */
    public function store()
    {

        $user = User::factory()->create();

        $election = Election::factory()->withAdmin($user)->frozen()->create();
        $election->cryptosystem = 'rsa';
        $election->createPeerServerTrustee(getCurrentServer());

        // generate key
        $election->cryptosystem->getClass()::onElectionFreeze($election); // generateCombinedPublicKey
        $keyPair = RSAKeyPair::generate();

        $election->public_key = $keyPair->pk;
        $election->save();

        $voter = new Voter();
        $voter->user_id = $user->id;
        $voter->election_id = $election->id;
        $voter->save();

        self::createElectionQuestions($election);

        static::assertEquals(0, $voter->votes()->count());

        // generate a JSON vote structure
        $votePlain = [[1], [3], []];

        // encrypt it
        /** @var RSAPlaintext $plaintext */
        $plaintext = Small_JSONBallotEncoding::encode($votePlain, RSAPlaintext::class);
        $cipher = $keyPair->pk->encrypt($plaintext);

        $data = ['vote' => $cipher->toArray(true)];

        /**
         * @see \App\Http\Controllers\CastVoteController::store()
         */
        $token = $user->getNewJwtToken();
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->json('POST', "api/elections/$election->slug/cast", $data);

        self::assertResponseStatusCode(200, $response);

        static::assertEquals(1, $election->votes()->count());

        /** @var CastVote $voteCast */
        $voteCast = $election->votes()->first();

        $out = $keyPair->sk->decrypt($voteCast->vote);
        static::assertEquals($votePlain, Small_JSONBallotEncoding::decode($out));

    }
}
