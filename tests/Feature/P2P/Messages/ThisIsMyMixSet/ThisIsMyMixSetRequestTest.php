<?php


namespace Tests\Feature\P2P\Messages\ThisIsMyMixSet;


use App\Enums\AnonymizationMethodEnum;
use App\Enums\CryptoSystemEnum;
use App\Models\Election;
use App\Models\Mix;
use App\Models\PeerServer;
use App\P2P\Messages\ThisIsMyMixSet\ThisIsMyMixSetRequest;
use App\P2P\Messages\ThisIsMyMixSet\ThisIsMyMixSetResponse;
use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class ThisIsMyMixSetRequestTest extends TestCase
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
        $election->cryptosystem = CryptoSystemEnum::ElGamal();
        $election->anonymization_method = AnonymizationMethodEnum::EncMixNet();
        $election->cryptosystem->getClass()::onElectionFreeze($election); // generateCombinedPublicKey
        $keyPair = EGKeyPair::generate();
        $election->public_key = $keyPair->pk;
        $election->voting_started_at = Carbon::now();
        $election->save();

        $trustee = $election->createPeerServerTrustee($me);

        $election->actualFreeze();

        $vote1 = $this->addVote($election, [[1, 3]]);

        $mixModel = new Mix();
        $mixModel->round = 1;
        $mixModel->previous_mix_id = null;
        $mixModel->uuid = Mix::getNewUUID()->string;
        $mixModel->trustee()->associate($trustee);
        $mixModel->hash = Str::random(10);
        $mixModel->shadow_mix_count = 2;
        $mixModel->save();

        $primaryShadowMixes = $mixModel->generateMixAndShadowMixes();
        $primaryShadowMixes->setChallengeBits($primaryShadowMixes->getFiatShamirChallengeBits());
        $primaryShadowMixes->generateProofs($trustee);

//        $primaryShadowMixes->store($mixModel->getFilename());

        $srcMsg = new ThisIsMyMixSetRequest($me, $to, $mixModel);

        $serialized = $srcMsg->serialize($to);

        // change to prevent database unique constraint
        $oldHash = $mixModel->hash;
        $mixModel->uuid = Mix::getNewUUID()->string;
        $mixModel->hash = Str::random(10);
        $mixModel->save();

        $back = ThisIsMyMixSetRequest::unserialize($me, $serialized);

        $response = $back->onRequestReceived();

        $resp = ThisIsMyMixSetResponse::unserialize($me, $response->serialize(), $srcMsg);
        $resp->onResponseReceived($me, null);

        self::assertEquals($oldHash, $back->mixModel->hash);

//        $primaryShadowMixes->deleteFile($mixModel->getFilename());

        $election->deleteFiles();

    }

}
