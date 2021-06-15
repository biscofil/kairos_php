<?php

namespace Tests;

use App\Models\Answer;
use App\Models\CastVote;
use App\Models\Election;
use App\Models\Question;
use App\Models\User;
use App\Models\Voter;
use App\Voting\BallotEncodings\Small_JSONBallotEncoding;
use App\Voting\CryptoSystems\CipherText;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use App\Voting\CryptoSystems\ElGamal\EGPublicKey;
use App\Voting\CryptoSystems\ElGamal\EGSecretKey;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseTransactions;

    /**
     * @param int $expectedCode
     * @param TestResponse|JsonResponse $response
     */
    public static function assertResponseStatusCode(int $expectedCode, $response): void
    {
        if (env('TESTING_DUMP_RESPONSE', false)) {
            if ($response->getStatusCode() !== $expectedCode) {
                try {
                    dump($response->json());
                } catch (\Exception $e) {
                    dump($response->content());
                }
            }
        }
        static::assertEquals($expectedCode, $response->getStatusCode());
    }

    /**
     * @param \App\Voting\CryptoSystems\ElGamal\EGPublicKey $pk
     * @param \App\Voting\CryptoSystems\ElGamal\EGSecretKey $sk
     */
    public static function assertValidEGKeyPair(EGPublicKey $pk, EGSecretKey $sk)
    {
        $p = new EGPlaintext(randomBIgt($pk->parameterSet->p));
        $c = $pk->encrypt($p);
        $p2 = $sk->decrypt($c);
        static::assertTrue($p->equals($p2));
    }

    /**
     * @param \App\Models\Election $election
     * @param array $votePlain
     * @return \App\Voting\CryptoSystems\CipherText
     * @throws \Exception
     */
    public function addVote(Election $election, array $votePlain): CipherText
    {
        $user = User::factory()->create();

        $voter = new Voter();
        $voter->user_id = $user->id;
        $voter->election_id = $election->id;
        $voter->save();

        $plaintext = Small_JSONBallotEncoding::encode($votePlain, EGPlaintext::class);
        $cipher = $election->public_key->encrypt($plaintext);

        $castVote = new CastVote();
        $castVote->election_id = $election->id;
        $castVote->vote = $cipher;
        $castVote->ip = '';
        $castVote->hash = '';
        $castVote->voter_id = $voter->id;
        $castVote->save();

        return $cipher;

        $data = ['vote' => $cipher->toArray(true)];

        /**
         * @see \App\Http\Controllers\CastVoteController::store()
         */
        $token = $user->getNewJwtToken();
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->json('POST', "api/elections/$election->slug/cast", $data);

        self::assertResponseStatusCode(200, $response);

        return $cipher;
    }

    /**
     * @param \App\Models\Election $election
     * @param int $nQuestions
     * @param int $nAnswers
     * @param int $min
     */
    public static function createElectionQuestions(Election &$election, int $nQuestions = 3, int $nAnswers = 3, int $min = 0): void
    {
        for ($i = 0; $i < $nQuestions; $i++) {
            $question = Question::factory()->make();
            $question->local_id = $i + 1;
            $question->election_id = $election->id;
            $question->min = $min;
            $question->max = $nAnswers;
            $question->save();
            for ($k = 0; $k < $nAnswers; $k++) {
                $answer = Answer::factory()->make();
                $answer->local_id = $k + 1;
                $answer->question_id = $question->id;
                $answer->save();
            }
        }
    }
}
