<?php


namespace Tests\Feature\P2P\Messages;


use App\Models\Election;
use App\Models\PeerServer;
use App\P2P\Messages\Freeze\Freeze1IAmFreezingElection\Freeze1IAmFreezingElectionRequest;
use Tests\TestCase;

class Freeze1IAmFreezingElectionTest extends TestCase
{

    /**
     * @test
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function serialize_unserialize_request()
    {

        $election = Election::factory()->create();

        self::createElectionQuestions($election, 1, 1);

        $trusteePeerServer = PeerServer::factory()->create();
        $election->createPeerServerTrustee($trusteePeerServer);

        $trusteePeerServer2 = PeerServer::factory()->create();
        $election->createPeerServerTrustee($trusteePeerServer2);

        $election = $election->fresh('trustees');

        $publicKey = null;
        $broadcast = null;
        $share = null;

        $msg = new Freeze1IAmFreezingElectionRequest(
            getCurrentServer(),
            $trusteePeerServer,
            $election,
            $election->questions,
            $election->trustees,
            $publicKey,
            $broadcast,
            $share);

        $ser = $msg->serialize($trusteePeerServer);

        $unser = Freeze1IAmFreezingElectionRequest::unserialize(getCurrentServer(), $ser);

        static::assertEquals($msg->election->uuid, $unser->election->uuid);

//        $unser->onRequestReceived();

    }

}
