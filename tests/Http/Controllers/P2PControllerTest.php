<?php


namespace Tests\Http\Controllers;


use Tests\TestCase;

class P2PControllerTest extends TestCase
{

    /**
     * @test
     */
    public function list_peers()
    {
        $response = $this->get('/api/p2p');
        self::assertResponseStatusCode(200, $response);
    }

}
