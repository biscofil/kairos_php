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

        /** @var User $trustee_user */
        $trustee_user = User::factory()->create();

        /** @var Election $election */
        $election = Election::factory()->withAdmin($trustee_user)->withUUID()->create();

        $election->createTrustee($trustee_user);

        $response = $this->actingAs($trustee_user)
            ->json('GET', 'api/elections/' . $election->slug . '/trustee/home');
        $this->assertResponseStatusCode(200, $response);

    }

    /**
     * TODO @ test
     */
    public function upload_public_key()
    {

    }
}
