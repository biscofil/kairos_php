<?php

namespace Tests\Http\Controllers;

use App\Crypto\EGCiphertext;
use App\Crypto\EGKeyPair;
use App\Crypto\EGPlaintext;
use App\Models\CastVote;
use App\Models\Election;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Support\Str;
use Tests\TestCase;

class CastVoteControllerTest extends TestCase
{

    /**
     * @test
     */
    public function store()
    {

        /** @var User $user */
        $user = User::factory()->create();

        /** @var Election $election */
        $election = Election::factory()->withAdmin($user)->withUUID()->frozen()->create();

        $voter = new Voter();
        $voter->user_id = $user->id;
        $voter->election_id = $election->id;
        $voter->save();

        $this->assertEquals(0, $voter->votes()->count());

        // generate key
        $keyPair = EGKeyPair::generate();

        // generate a JSON vote structure
        $votePlain = [
            Str::random(10) => Str::random(10),
            Str::random(10) => [
                Str::random(10),
                Str::random(10),
            ]
        ];
        $plain = json_encode($votePlain);

        // encrypt it
        $msg = EGPlaintext::fromString($plain, $keyPair->pk);
        $cipher = $msg->encrypt();

        $voteEncrypted = json_encode($cipher->toArray()); // alpha is discarded
        $data = ["vote" => $voteEncrypted];

        $response = $this->actingAs($user)
            ->json('POST', 'api/elections/' . $election->slug . '/cast', $data);
        $this->assertResponseStatusCode(200, $response);

        $this->assertEquals(1, $voter->votes()->count());

        /** @var CastVote $voteCast */
        $voteCast = $voter->votes()->first();

        $voteEncryptedDB = EGCiphertext::fromArray(json_decode($voteCast->vote, true));
        $out = $keyPair->sk->decrypt($voteEncryptedDB)->toString();

        $this->assertEquals($plain, $out);

    }
}
