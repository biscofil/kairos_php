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
use App\Voting\QuestionTypes\MultipleChoice;
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

    }

}
