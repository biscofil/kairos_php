<?php


namespace Tests\Feature;


use App\Models\PeerServer;
use App\Providers\AppServiceProvider;
use Tests\TestCase;

class ActAsPeerServerTest extends TestCase
{

    /**
     * @ TODO test
     */
    public function default_peer()
    {
        /**
         * @see \App\Http\Controllers\Controller::settings_auth()
         */
        $response = $this->getJson('api/settings_auth');
        $this->assertResponseStatusCode(200, $response);

        $serverPeer = $response->json('peer');

        static::assertEquals(PeerServer::meID, $serverPeer['id']);
    }

    /**
     * @ TODO test
     */
    public function another_peer()
    {

        $newPeer = PeerServer::factory()->create();

        /**
         * @see \App\Http\Controllers\Controller::settings_auth()
         */
        $response = $this
            ->withHeaders([AppServiceProvider::ActAsPeerServerKey => $newPeer->id])
            ->json(
                'GET',
                'api/settings_auth?aaa=1',
//                [],
                [AppServiceProvider::ActAsPeerServerKey => "$newPeer->id"]);

        $this->assertResponseStatusCode(200, $response);

        $serverPeer = $response->json('peer');
        static::assertEquals($newPeer->id, $serverPeer['id']);
    }

}
