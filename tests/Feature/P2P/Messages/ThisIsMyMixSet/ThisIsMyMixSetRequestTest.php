<?php


namespace Tests\Feature\P2P\Messages\ThisIsMyMixSet;


use App\Enums\AnonymizationMethodEnum;
use App\Enums\CryptoSystemEnum;
use App\Models\Election;
use App\Models\Mix;
use App\Models\PeerServer;
use App\P2P\Messages\ThisIsMyMixSet\ThisIsMyMixSetRequest;
use App\P2P\Messages\ThisIsMyMixSet\ThisIsMyMixSetResponse;
use App\Voting\AnonymizationMethods\MixNets\ReEncryption\ReEncryptingMixNode;
use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use Illuminate\Support\Str;
use Tests\TestCase;

class ThisIsMyMixSetRequestTest extends TestCase
{

    /**
     * @test
     */
    public function serialize_unserialize()
    {

        $to = new PeerServer();

        $me = PeerServer::me();

        $election = Election::factory()->create();
        $election->cryptosystem = CryptoSystemEnum::ElGamal();
        $election->anonymization_method = AnonymizationMethodEnum::EncMixNet();
        $election->cryptosystem->getClass()::onElectionFreeze($election); // generateCombinedPublicKey
        $keyPair = EGKeyPair::generate();
        $election->public_key = $keyPair->pk;
        $election->save();

        $trustee = $election->createPeerServerTrustee($me);

        $primaryShadowMixes = ReEncryptingMixNode::generate($election, [
            $election->public_key->encrypt(new EGPlaintext(BI(3)))
        ], 2);
        $primaryShadowMixes->challengeBits = $primaryShadowMixes->getFiatShamirChallengeBits();
        $primaryShadowMixes->generateProofs();

        $mixModel = new Mix();
        $mixModel->trustee_id = $election->getTrusteeFromPeerServer($me, true)->id;
        $mixModel->hash = Str::random(10);
        $mixModel->round = 1;
        $mixModel->save();

        $primaryShadowMixes->store($mixModel->getFilename());

        $srcMsg = new ThisIsMyMixSetRequest($me, $to, $mixModel);

        $serialized = $srcMsg->serialize($to);

        // change to prevent database unique constraint
        $oldHash = $mixModel->hash;
        $mixModel->hash = Str::random(10);
        $mixModel->save();

        $back = ThisIsMyMixSetRequest::unserialize($me, $serialized);

        $response = $back->onRequestReceived();

        $resp = ThisIsMyMixSetResponse::unserialize($me, $response->serialize(), $srcMsg);
        $resp->onResponseReceived($me, null);

        self::assertEquals($oldHash, $back->mixModel->hash);

    }

}