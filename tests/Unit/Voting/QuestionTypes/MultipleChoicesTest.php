<?php


namespace Tests\Unit\Voting\QuestionTypes;


use App\Enums\AnonymizationMethodEnum;
use App\Enums\CryptoSystemEnum;
use App\Models\Election;
use App\Models\Question;
use App\Voting\BallotEncodings\Small_JSONBallotEncoding;
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
        self::createElectionQuestions($election);
        $election->preFreeze();
        $election->actualFreeze();

        $votePlain = [
            [1], // first answer of first question
            [2], // second answer of second question
            [3]  // third answer of third question
        ];
        $plaintext = Small_JSONBallotEncoding::encode($votePlain, EGPlaintext::class);
        $cipher = $keyPair->pk->encrypt($plaintext);

        $plainVote = Small_JSONBallotEncoding::decode($election->private_key->decrypt($cipher));

        $tallyDatabase = $election->getTallyDatabase();
        self::assertTrue($tallyDatabase->insertBallot($plainVote));

        self::assertEquals(1, $tallyDatabase->getRecordCount());

        $election->tally();
        $election = $election->fresh();
        self::assertNotNull($election->tallying_started_at);
        self::assertNotNull($election->tallying_finished_at);

        /** @var Question $question */
        foreach ($election->questions()->get() as $question) {
            static::assertNotEquals(false, $question->tally_result);
        }

        self::assertTrue($tallyDatabase->file_exists());
        $tallyDatabase->delete();
        self::assertFalse($tallyDatabase->file_exists());

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

        self::createElectionQuestions($election);

        $election->preFreeze();
        $election->actualFreeze();

        $votePlain = [
            [],
            [],
            []
        ];
        $plaintext = Small_JSONBallotEncoding::encode($votePlain, EGPlaintext::class);
        $cipher = $keyPair->pk->encrypt($plaintext);

        $plainVote = Small_JSONBallotEncoding::decode($election->private_key->decrypt($cipher));
        $tallyDatabase = $election->getTallyDatabase();
        self::assertTrue($tallyDatabase->insertBallot($plainVote));
        $tallyDatabase->tally();

        $tallyDatabase->delete();
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

        self::createElectionQuestions($election); //rand(1, 3), rand(1, 3)

        $election->preFreeze();
        $election->actualFreeze();

        $votePlain = [
            [5], // fifth answer of first question (invalid)
            [2], // second answer of second question
            [3] // third answer of third question
        ];
        $plaintext = Small_JSONBallotEncoding::encode($votePlain, EGPlaintext::class);
        $cipher = $keyPair->pk->encrypt($plaintext);

        $plainVote = Small_JSONBallotEncoding::decode($election->private_key->decrypt($cipher));
        $tallyDatabase = $election->getTallyDatabase();
        self::assertFalse($tallyDatabase->insertBallot($plainVote));

        $tallyDatabase->delete();

    }

}
