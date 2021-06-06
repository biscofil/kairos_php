<?php


namespace Tests\Unit\Voting\QuestionTypes;


use App\Enums\AnonymizationMethodEnum;
use App\Enums\CryptoSystemEnum;
use App\Models\Election;
use App\Models\Question;
use App\Voting\AnonymizationMethods\MixNets\MixNode;
use App\Voting\BallotEncodings\JsonBallotEncoding;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use Tests\TestCase;

class MultipleChoicesTest extends TestCase
{

    /**
     * @test
     */
    public function works()
    {

        $election = Election::factory()->create();
        $election->cryptosystem = CryptoSystemEnum::ElGamal();
        $election->anonymization_method = AnonymizationMethodEnum::EncMixNet();
        $kpClass = $election->cryptosystem->getClass()::getKeyPairClass();
        $keyPair = $kpClass::generate();
        $election->public_key = $keyPair->pk;
        $election->private_key = $keyPair->sk;
        $election->save();

        $nQuestions = 3;//rand(1, 3);

        for ($i = 0; $i < $nQuestions; $i++) {
            $question = Question::factory()->make();
            $question->election_id = $election->id;
            $question->save();
        }

        $election->setupOutputTables();
        $conn = $election->getOutputConnection();

        $votePlain = [
            [1], // first answer of first question
            [2], // second answer of second question
            [3]  // third answer of third question
        ];
        $plaintext = (JsonBallotEncoding::encode($votePlain, EGPlaintext::class))[0];
        $cipher = $keyPair->pk->encrypt($plaintext);

//        $conn->table($election->getOutputTableName())->truncate();
        self::assertTrue(MixNode::insertBallot($election, $conn, $cipher));

        self::assertEquals(1, $conn->table($election->getOutputTableName())->count());

        $election->tally();

        $election = $election->fresh();

        self::assertNotNull($election->tallying_started_at);
        self::assertNotNull($election->tallying_finished_at);

        /** @var Question $question */
        foreach ($election->questions()->get() as $question) {
            static::assertNotEquals(false, $question->tally_result);
        }

        self::assertTrue(file_exists($election->getOutputDatabaseFilename()));
        unlink($election->getOutputDatabaseFilename());
        self::assertFalse(file_exists($election->getOutputDatabaseFilename()));

    }


    /**
     * @test
     */
    public function empty_ballot_should_work()
    {
        $election = Election::factory()->create();
        $election->cryptosystem = CryptoSystemEnum::ElGamal();
        $election->anonymization_method = AnonymizationMethodEnum::EncMixNet();
        $kpClass = $election->cryptosystem->getClass()::getKeyPairClass();
        $keyPair = $kpClass::generate();
        $election->public_key = $keyPair->pk;
        $election->private_key = $keyPair->sk;
        $election->save();

        $nQuestions = 3; //rand(1, 3);
        for ($i = 0; $i < $nQuestions; $i++) {
            $question = Question::factory()->make();
            $question->election_id = $election->id;
            $question->save();
        }

        $election->setupOutputTables();
        $conn = $election->getOutputConnection();

        $votePlain = [
            [],
            [],
            []
        ];
        $plaintext = (JsonBallotEncoding::encode($votePlain, EGPlaintext::class))[0];
        $cipher = $keyPair->pk->encrypt($plaintext);
//        $conn->table($election->getOutputTableName())->truncate();
        self::assertTrue(MixNode::insertBallot($election, $conn, $cipher));

        unlink($election->getOutputDatabaseFilename());
    }

    /**
     * @test
     */
    public function wrong_answer_id_should_fail()
    {
        $election = Election::factory()->create();
        $election->cryptosystem = CryptoSystemEnum::ElGamal();
        $election->anonymization_method = AnonymizationMethodEnum::EncMixNet();
        $kpClass = $election->cryptosystem->getClass()::getKeyPairClass();
        $keyPair = $kpClass::generate();
        $election->public_key = $keyPair->pk;
        $election->private_key = $keyPair->sk;
        $election->save();

        $nQuestions = 3;//rand(1, 3);
        for ($i = 0; $i < $nQuestions; $i++) {
            $question = Question::factory()->make();
            $question->election_id = $election->id;
            $question->save();
        }

        $election->setupOutputTables();
        $conn = $election->getOutputConnection();

        $votePlain = [
            [5], // fifth answer of first question
            [2], // second answer of second question
            [3] // third answer of third question
        ];
        $plaintext = (JsonBallotEncoding::encode($votePlain, EGPlaintext::class))[0];
        $cipher = $keyPair->pk->encrypt($plaintext);
//        $conn->table($election->getOutputTableName())->truncate();
        self::assertFalse(MixNode::insertBallot($election, $conn, $cipher));

        unlink($election->getOutputDatabaseFilename());

    }

}
