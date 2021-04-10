<?php

namespace Tests\Http\Controllers;

use App\Models\Election;
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
        /** @var Election $election */

        $data = Election::factory()->make()->toArray();

        $response = $this->actingAs($user)
            ->json('POST', 'api/elections', $data);
        $this->assertResponseStatusCode(201, $response);

        $election = Election::findOrFail($response->json('id'));

        $data = $election->toArray();

        $response = $this->actingAs($user)->json('PUT', 'api/elections/' . $election->slug, $data);
        $this->assertResponseStatusCode(200, $response);
    }

}
