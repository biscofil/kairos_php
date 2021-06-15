<?php


namespace Tests\Feature\P2P\Messages\Freeze3CommitFailRequest;


use App\Enums\CryptoSystemEnum;
use App\Models\Election;
use App\Models\PeerServer;
use App\P2P\Messages\Freeze\Freeze3CommitFail\Freeze3CommitFailRequest;
use Tests\TestCase;

class Freeze3CommitFailRequestTest extends TestCase
{

    /**
     * @test
     * @throws \Exception
     */
    public function serialize_unserialize_request_commit()
    {

        $election = Election::factory()->create();
        $election->cryptosystem = CryptoSystemEnum::ElGamal(); // TODO remove
        $election->save();

        self::createElectionQuestions($election, 1, 1);

        $trusteePeerServer = PeerServer::factory()->create();
        $trustee1 = $election->createPeerServerTrustee($trusteePeerServer);
        $trustee1->generateKeyPair();
        $trustee1->save();

        $trusteePeerServer2 = PeerServer::factory()->create();
        $trustee2 = $election->createPeerServerTrustee($trusteePeerServer2);
        $trustee2->generateKeyPair();
        $trustee2->save();

        $election = $election->fresh('trustees');

        $msg = new Freeze3CommitFailRequest(
            getCurrentServer(),
            $trusteePeerServer,
            $election,
            true,
            $election->trustees);

        $ser = $msg->serialize($trusteePeerServer);

        $tempPK1 = $trustee1->public_key;
        $trustee1->public_key = null;
        $trustee1->save();

        $tempPK2 = $trustee2->public_key;
        $trustee2->public_key = null;
        $trustee2->save();

        $unser = Freeze3CommitFailRequest::unserialize(getCurrentServer(), $ser);

        $unser->onRequestReceived();

        $election = $election->fresh();
        self::assertNotNull($election->frozen_at);

        $trustee1 = $trustee1->fresh();
        self::assertTrue($trustee1->public_key->equals($tempPK1));

        $trustee2 = $trustee2->fresh();
        self::assertTrue($trustee2->public_key->equals($tempPK2));

    }

    /**
     * @test
     * @throws \Exception
     */
    public function serialize_unserialize_request_fail()
    {

        $election = Election::factory()->create();

        self::createElectionQuestions($election, 1, 1);

        $trusteePeerServer = PeerServer::factory()->create();
        $trustee1 = $election->createPeerServerTrustee($trusteePeerServer);
        $trustee1->generateKeyPair();
        $trustee1->save();

        $trusteePeerServer2 = PeerServer::factory()->create();
        $trustee2 = $election->createPeerServerTrustee($trusteePeerServer2);
        $trustee2->generateKeyPair();
        $trustee2->save();

        $election = $election->fresh('trustees');

        $msg = new Freeze3CommitFailRequest(
            getCurrentServer(),
            $trusteePeerServer,
            $election,
            false,
            $election->trustees);

        $ser = $msg->serialize($trusteePeerServer);

        $unser = Freeze3CommitFailRequest::unserialize(getCurrentServer(), $ser);

        $unser->onRequestReceived();

        $election = $election->fresh();
        self::assertNull($election->frozen_at);

    }

}
