<?php


namespace Tests\Feature\FullFlow;


use App\Enums\AnonymizationMethodEnum;
use App\Enums\CryptoSystemEnum;
use App\Models\Answer;
use App\Models\Election;
use App\Models\Question;
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
        $election->save();

        $trustee = $election->createPeerServerTrustee(getCurrentServer());

        $kpClass = $election->cryptosystem->getClass()::getKeyPairClass();
        $ptClass = $election->cryptosystem->getClass()::getPlainTextClass();

        $keyPair = $kpClass::generate();
        $election->public_key = $keyPair->pk;
        $election->private_key = $keyPair->sk;
        $election->save();

        $election->frozen_at = Carbon::now(); // todo use freeze()
        $election->save();

        $q = Question::factory()->make();
        $q->election_id = $election->id;
        $q->save();

        $a = Answer::factory()->make();
        $a->local_id = 1;
        $a->question_id = $q->id;
        $a->save();

        // cast votes
        for ($i = 0; $i < 5; $i++) {

            $user = User::factory()->create();

            $voter = new Voter();
            $voter->user_id = $user->id;
            $voter->election_id = $election->id;
            $voter->save();

            $plaintext = new ExpEGPlaintext(BI(1));
            $cipher = $keyPair->pk->encrypt($plaintext);

            $v = $cipher->toArray(true);
            $v['answer_id'] = $a->id;
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

            $this->assertResponseStatusCode(200, $response);
        }

        // TODO secret key combination

        $election->anonymization_method->getClass()::afterVotingPhaseEnds($election);

        // TODO brute force
    }

}
