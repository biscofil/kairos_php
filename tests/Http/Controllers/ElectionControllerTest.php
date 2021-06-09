<?php

namespace Tests\Http\Controllers;

use App\Enums\AnonymizationMethodEnum;
use App\Enums\CryptoSystemEnum;
use App\Models\Answer;
use App\Models\Election;
use App\Models\Question;
use App\Models\User;
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
    public function copy()
    {
        $election = Election::factory()->create();

        $nQuestions = rand(0, 3);
        self::createElectionQuestions($election, $nQuestions);
        self::assertEquals($nQuestions, $election->questions()->count());

        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->json('POST', 'api/elections/' . $election->slug . '/copy');
        $this->assertResponseStatusCode(201, $response);

        $newElection = Election::findOrFail($response->json('id'));

        self::assertEquals($nQuestions, $newElection->questions()->count());

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
            'questions' => []
        ];
        for ($i = 0; $i < 3; $i++) {
            $q = Question::factory()->make()->toArray();
            $q['answers'] = [];
            for ($j = 0; $j < 3; $j++) {
                $q['answers'][] = Answer::factory()->make()->toArray();
            }
            $data['questions'][] = $q;
        }
        $response = $this->actingAs($user)->json('PUT', 'api/elections/' . $election->slug . '/questions', $data);
        $this->assertResponseStatusCode(200, $response);

    }

}
