<?php


namespace Tests\Feature\FullFlow;


use App\Enums\AnonymizationMethodEnum;
use App\Enums\CryptoSystemEnum;
use App\Models\Election;
use App\Models\PeerServer;
use App\Models\User;
use App\Models\Voter;
use App\Voting\CryptoSystems\ExpElGamal\ExpEGPlaintext;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Class ExpElGamalHomomorphicElectionTest
 * @package Tests\Feature\FullFlow
 */
class ExpElGamalHomomorphicElectionTest extends TestCase
{

    /**
     * @test
     */
    public function full()
    {
        $election = Election::factory()->create();
        $election->cryptosystem = CryptoSystemEnum::ExponentialElGamal();
        $election->anonymization_method = AnonymizationMethodEnum::Homomorphic();
        $election->min_peer_count_t = 1;
        $election->save();

        $peerServer = PeerServer::factory()->create();
        $trustee = $election->createPeerServerTrustee($peerServer);
        $trustee->generateKeyPair();
        $trustee->accepts_ballots = true;
        $trustee->save();

        self::createElectionQuestions($election, 1, 1);

        self::assertTrue($election->preFreeze());
        $election->actualFreeze();

        // start voting phase
        $election->voting_started_at = Carbon::now();
        $election->save();

        // cast votes
        for ($i = 0; $i < 5; $i++) {

            $user = User::factory()->create();

            $voter = new Voter();
            $voter->user_id = $user->id;
            $voter->election_id = $election->id;
            $voter->save();

            $plaintext = new ExpEGPlaintext(BI(1));
            $cipher = $election->public_key->encrypt($plaintext);

            $v = $cipher->toArray(true);
            $v['answer_id'] = $election->questions()->first()->answers()->first()->id; // first
            $data = [
                'votes' => [
                    $v
                ]
            ]; // TODO answer votes

            /**
             * @see \App\Http\Controllers\CastVoteController::store()
             */
            $token = $user->getNewJwtToken();
            $response = $this->withHeaders(['Authorization' => "Bearer $token"])
                ->json('POST', "api/elections/$election->slug/cast", $data);

            self::assertResponseStatusCode(200, $response);
        }

        // TODO secret key combination

        $election->private_key = $trustee->private_key; // TODO check
        $election->anonymization_method->getClass()::afterVotingPhaseEnds($election);

        // TODO brute force

        self::assertNotNull($election->tallying_finished_at);
    }

}
