<?php

namespace Tests\Feature\P2P\Messages;


use App\Models\PeerServer;
use App\P2P\Messages\AddMeToYourPeers;
use Tests\TestCase;

class AddMeToYourPeersRequestTest extends TestCase
{

    /**
     * @test
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function serialize_unserialize()
    {

        $to = PeerServer::factory()->create();

        $me = getCurrentServer();

        $pk = $me->jwt_public_key;

        $srcMsg = new AddMeToYourPeers\AddMeToYourPeersRequest($me, $to, $pk, 'token');
        $data = $srcMsg->serialize($to);

        $dstMsg = AddMeToYourPeers\AddMeToYourPeersRequest::unserialize($me, $data);

        static::assertTrue($srcMsg->senderJwtPk->toArray() === $dstMsg->senderJwtPk->toArray());
        static::assertTrue($srcMsg->token === $dstMsg->token);

    }

}
