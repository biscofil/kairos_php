<?php

namespace Tests\Http\Controllers;

use App\Enums\AnonymizationMethodEnum;
use App\Enums\CryptoSystemEnum;
use App\Models\Election;
use App\Models\Question;
use App\Models\User;
use App\Voting\AnonymizationMethods\MixNets\MixNode;
use App\Voting\BallotEncodings\JsonBallotEncoding;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use Tests\TestCase;

class ElectionControllerTest extends TestCase
{

    /**
     * @test
     */
    public function store()
    {
        $user = User::factory()->create();
        $data = Election::factory()->make()->toArray();
        $response = $this->actingAs($user)->json('POST', 'api/elections', $data);
        $this->assertResponseStatusCode(201, $response);
    }

    /**
     * @test
     */
    public function update()
    {
        $user = User::factory()->create();

        // create
        $data = Election::factory()->make()->toArray();
        $response = $this->actingAs($user)
            ->json('POST', 'api/elections', $data);
        $this->assertResponseStatusCode(201, $response);

        // edit
        $election = Election::findOrFail($response->json('id'));

        $data = $election->toArray();

        $response = $this->actingAs($user)->json('PUT', 'api/elections/' . $election->slug, $data);
        $this->assertResponseStatusCode(200, $response);
    }

    /**
     * @test
     */
    public function questions()
    {

        $user = User::factory()->create();

        // create
        $election = Election::factory()->make();
        $election->cryptosystem = CryptoSystemEnum::ElGamal();
        $election->anonymization_method = AnonymizationMethodEnum::EncMixNet();

        $response = $this->actingAs($user)
            ->json('POST', 'api/elections', $election->toArray());
        $this->assertResponseStatusCode(201, $response);

        // edit
        $election = Election::findOrFail($response->json('id'));
        $data = [
            'questions' => [
                Question::factory()->make()->toArray(),
                Question::factory()->make()->toArray(),
                Question::factory()->make()->toArray(),
            ]
        ];
        $response = $this->actingAs($user)->json('PUT', 'api/elections/' . $election->slug . '/questions', $data);
        $this->assertResponseStatusCode(200, $response);

        $kpClass = $election->cryptosystem->getClass()::getKeyPairClass();
        $keyPair = $kpClass::generate();
        $election->public_key = $keyPair->pk;
        $election->private_key = $keyPair->sk;
        $election->save();

        $election->setupOutputTables();
        $conn = $election->getOutputConnection();


        // generate a JSON vote structure
        $votePlain = [
            [1], // first answer of first question
            [2], // second answer of second question
            [3] // third answer of third question
        ];
        $plaintext = (JsonBallotEncoding::encode($votePlain, EGPlaintext::class))[0];
        $cipher = $keyPair->pk->encrypt($plaintext);
        self::assertTrue(MixNode::insertBallot($election, $conn, $cipher));


        // generate a JSON vote structure
        $votePlain = [
            [],
            [],
            []
        ];
        $plaintext = (JsonBallotEncoding::encode($votePlain, EGPlaintext::class))[0];
        $cipher = $keyPair->pk->encrypt($plaintext);
        self::assertTrue(MixNode::insertBallot($election, $conn, $cipher));


        // generate a JSON vote structure
        $votePlain = [
            [5], // fifth answer of first question
            [2], // second answer of second question
            [3] // third answer of third question
        ];
        $plaintext = (JsonBallotEncoding::encode($votePlain, EGPlaintext::class))[0];
        $cipher = $keyPair->pk->encrypt($plaintext);
        self::assertFalse(MixNode::insertBallot($election, $conn, $cipher));

    }

}
