<?php


namespace Tests\Feature;


use App\Http\Middleware\ActAsPeer;
use App\Models\PeerServer;
use Tests\TestCase;

/**
 * Class ActAsPeerServerTest
 * @package Tests\Feature
 */
class ActAsPeerServerTest extends TestCase
{

    /**
     * @test
     */
    public function default_peer()
    {
        /**
         * @see \App\Http\Controllers\Controller::settings_auth()
         */
        $response = $this->getJson('api/settings_auth');
        self::assertResponseStatusCode(200, $response);

        $serverPeer = $response->json('peer');

        static::assertEquals(PeerServer::meID, $serverPeer['id']);
    }

    /**
     * @test
     */
    public function another_peer()
    {

        $newPeer = PeerServer::factory()->create();

        /**
         * @see \App\Http\Controllers\Controller::settings_auth()
         */
        $response = $this
            ->withHeaders([ActAsPeer::ActAsPeerServerKey => $newPeer->id])
            ->json(
                'GET',
                'api/settings_auth',
                [ActAsPeer::ActAsPeerServerKey => "$newPeer->id"]);

        self::assertResponseStatusCode(200, $response);

        $serverPeer = $response->json('peer');
        static::assertEquals($newPeer->id, $serverPeer['id']);
    }

}
