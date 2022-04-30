<?php


namespace Tests\Unit\Voting\QuestionTypes;


use App\Enums\AnonymizationMethodEnum;
use App\Enums\CryptoSystemEnum;
use App\Models\Election;
use App\Models\PeerServer;
use App\Models\Question;
use App\Voting\BallotEncodings\Small_JSONBallotEncoding;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use App\Voting\QuestionTypes\MultipleChoice;
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
        $election->min_peer_count_t = 1;
        $election->save();
        self::createElectionQuestions($election);

        $peerServer = PeerServer::factory()->create();
        $trustee = $election->createPeerServerTrustee($peerServer);
        $trustee->generateKeyPair();
        $trustee->accepts_ballots = true;
        $trustee->save();

        self::assertTrue($election->preFreeze());
        $election->actualFreeze();

        $votePlain = [
            [1], // first answer of first question
            [2], // second answer of second question
            [3]  // third answer of third question
        ];
        $plaintext = Small_JSONBallotEncoding::encode($votePlain, EGPlaintext::class);
        $cipher = $election->public_key->encrypt($plaintext);

        $plainVote = Small_JSONBallotEncoding::decode($trustee->private_key->decrypt($cipher));

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
        $election->min_peer_count_t = 1;
        $election->save();

        $peerServer = PeerServer::factory()->create();
        $trustee = $election->createPeerServerTrustee($peerServer);
        $trustee->generateKeyPair();
        $trustee->accepts_ballots = true;
        $trustee->save();

        self::createElectionQuestions($election);

        self::assertTrue($election->preFreeze());
        $election->actualFreeze();

        $votePlain = [
            [],
            [],
            []
        ];
        $plaintext = Small_JSONBallotEncoding::encode($votePlain, EGPlaintext::class);
        $cipher = $election->public_key->encrypt($plaintext);

        $plainVote = Small_JSONBallotEncoding::decode($trustee->private_key->decrypt($cipher));
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
        $election->min_peer_count_t = 1;
        $election->save();

        $peerServer = PeerServer::factory()->create();
        $trustee = $election->createPeerServerTrustee($peerServer);
        $trustee->generateKeyPair();
        $trustee->accepts_ballots = true;
        $trustee->save();

        self::createElectionQuestions($election); //rand(1, 3), rand(1, 3)

        self::assertTrue($election->preFreeze());
        $election->actualFreeze();

        $votePlain = [
            [5], // fifth answer of first question (invalid)
            [2], // second answer of second question
            [3] // third answer of third question
        ];
        $plaintext = Small_JSONBallotEncoding::encode($votePlain, EGPlaintext::class);
        $cipher = $election->public_key->encrypt($plaintext);

        $plainVote = Small_JSONBallotEncoding::decode($trustee->private_key->decrypt($cipher));
        $tallyDatabase = $election->getTallyDatabase();
        self::assertFalse($tallyDatabase->insertBallot($plainVote));

        $tallyDatabase->delete();
    }

    /**
     * @test
     */
    public function enumeration_min_0()
    {
        $election = Election::factory()->create();
        $election->cryptosystem = CryptoSystemEnum::ElGamal();
        $election->anonymization_method = AnonymizationMethodEnum::EncMixNet();
        $nQuestions = 3;
        $maxAnswers = 3;
        $minAnswers = 0;
        self::createElectionQuestions($election, $nQuestions, $maxAnswers, $minAnswers);

        $enumerations = MultipleChoice::generateAllCombinations($election->questions()->first());

        $expected = [
            [], //
            [1], [2], [3], //
            [1, 2], [2, 1], [1, 3], [3, 1], [2, 3], [3, 2], //
            [1, 2, 3], [1, 3, 2], [2, 1, 3], [2, 3, 1], [3, 1, 2], [3, 2, 1]
        ];
        self::assertEquals($expected, $enumerations);

        $pos = array_search([], $enumerations);
        self::assertEquals(0, $pos); // empty array must exist, in first position

    }

    /**
     * @test
     */
    public function enumeration_min_1()
    {

        $election = Election::factory()->create();
        $election->cryptosystem = CryptoSystemEnum::ElGamal();
        $election->anonymization_method = AnonymizationMethodEnum::EncMixNet();
        $nQuestions = 3;
        $maxAnswers = 3;
        $minAnswers = 1;
        self::createElectionQuestions($election, $nQuestions, $maxAnswers, $minAnswers);

        // generate a set of answers, can be an empty array or a subset of [1,2,3]
        $idxs = [1 => 1, 2 => 2, 3 => 3];
        $randomQuestionAnswers = (array)array_rand($idxs, rand(1, 3));
        shuffle($randomQuestionAnswers);

        $enumerations = MultipleChoice::generateAllCombinations($election->questions()->first());
        self::assertFalse(array_search([], $enumerations)); // empty array must NOT exist

        $expected = [
            // NO empty set []
            [1], [2], [3], //
            [1, 2], [2, 1], [1, 3], [3, 1], [2, 3], [3, 2], //
            [1, 2, 3], [1, 3, 2], [2, 1, 3], [2, 3, 1], [3, 1, 2], [3, 2, 1]
        ];
        self::assertEquals($expected, $enumerations);

        self::assertFalse(array_search([], [1, 2, 3]));
        self::assertEquals(0, array_search([], [[], 1, 2, 3]));
        self::assertEquals([[], [1]], array_merge([[]], [[1]]));

        $pos = array_search($randomQuestionAnswers, $enumerations);
        self::assertTrue(false !== $pos);

        //        dump($pos);
        //        dump(decbin($pos));
        //
        //        $ps = EGParameterSet::getDefault();
        //        dump(array_map(function (int $pos) {
        //
        //        }, $enumerations, range(0, count($enumerations))));
    }
}
