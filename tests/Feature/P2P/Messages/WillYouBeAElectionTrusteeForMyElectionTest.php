<?php

namespace Tests\Feature\P2P\Messages;


use App\Models\Election;
use App\Models\PeerServer;
use App\P2P\Messages\WillYouBeAElectionTrusteeForMyElection\WillYouBeAElectionTrusteeForMyElectionRequest;
use Tests\TestCase;

class WillYouBeAElectionTrusteeForMyElectionTest extends TestCase
{

    /**
     * @test
     * @throws \Exception
     */
    public function serialize_unserialize()
    {

        $to = new PeerServer();

        $me = getCurrentServer();

        $election = Election::factory()->create();

        $srcMsg = new WillYouBeAElectionTrusteeForMyElectionRequest($me, [$to], $election);

        $data = $srcMsg->serialize($to);

        $dstMsg = WillYouBeAElectionTrusteeForMyElectionRequest::unserialize($me, $data);

        static::assertEquals($srcMsg->election->slug, $dstMsg->election->slug);

    }

}
