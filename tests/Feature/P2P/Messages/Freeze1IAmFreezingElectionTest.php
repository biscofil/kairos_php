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

        /** @var Election $election */
        $election = Election::factory()->create();

        $trusteePeerServer = new PeerServer();
        $trustee = $election->createPeerServerTrustee($trusteePeerServer);

        $publicKey = null;
        $broadcast = null;
        $share = null;

        $msg = new Freeze1IAmFreezingElectionRequest(
            PeerServer::me(),
            $trusteePeerServer,
            $election,
            $election->trustees()->get()->all(),
            $publicKey,
            $broadcast,
            $share);

        $ser = $msg->serialize($trusteePeerServer);
        $unser = Freeze1IAmFreezingElectionRequest::unserialize(PeerServer::me(), $ser);

        static::assertEquals($msg->election->uuid, $unser->election->uuid);

    }

}
