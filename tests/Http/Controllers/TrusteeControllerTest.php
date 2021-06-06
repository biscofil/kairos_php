<?php

namespace Tests\Http\Controllers;

use App\Models\Election;
use App\Models\User;
use Tests\TestCase;

class TrusteeControllerTest extends TestCase
{

    /**
     * @test
     */
    public function trustee_home()
    {

        $trustee_user = User::factory()->create();

        $election = Election::factory()->withAdmin($trustee_user)->create();

        $election->createUserTrustee($trustee_user);

        $response = $this->actingAs($trustee_user)
            ->json('GET', 'api/elections/' . $election->slug . '/trustee/home');
        $this->assertResponseStatusCode(200, $response);

    }

}
