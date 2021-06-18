<?php


namespace Tests\Feature\FullFlow;


use App\Enums\AnonymizationMethodEnum;
use App\Enums\CryptoSystemEnum;
use App\Models\Election;
use App\Models\Mix;
use App\Models\PeerServer;
use App\Models\User;
use App\Models\Voter;
use App\Voting\BallotEncodings\Small_JSONBallotEncoding;
use App\Voting\CryptoSystems\ElGamal\EGCiphertext;
use App\Voting\CryptoSystems\ElGamal\EGDLogProof;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Class ElGamalDecryptionReEncryptionMixnetElectionTest
 * @package Tests\Feature\FullFlow
 */
class ElGamalDecryptionReEncryptionMixnetElectionTest extends TestCase
{

    /**
     * @test
     */
    public function general_idea()
    {

        $election = Election::factory()->create();
        $election->cryptosystem = CryptoSystemEnum::ElGamal();
        $election->anonymization_method = AnonymizationMethodEnum::DecReEncMixNet();
        $election->save();

        $nQuestions = 3;
        self::createElectionQuestions($election, $nQuestions);

        $peer1 = PeerServer::factory()->create();
        $trustee1 = $election->createPeerServerTrustee($peer1);
        $trustee1->generateKeyPair();
        $trustee1->save();

        $peer2 = PeerServer::factory()->create();
        $trustee2 = $election->createPeerServerTrustee($peer2);
        $trustee2->generateKeyPair();
        $trustee2->save();

        $peer3 = PeerServer::factory()->create();
        $trustee3 = $election->createPeerServerTrustee($peer3);
        $trustee3->generateKeyPair();
        $trustee3->save();

        $election->min_peer_count_t = 3;
        $election->save();

//        $election->preFreeze();
        $election->actualFreeze();

        // public key of election is the combination
        /** @noinspection PhpParamsInspection */
        self::assertTrue($trustee1->public_key->combine($trustee2->public_key)->combine($trustee3->public_key)
            ->equals($election->public_key));

        // cast vote
        $user = User::factory()->create();
        $voter = new Voter();
        $voter->user_id = $user->id;
        $voter->election_id = $election->id;
        $voter->save();

        // generate a JSON vote structure
        $idxs = [1 => 1, 2 => 2, 3 => 3];
        $votePlain = array_map(function () use ($idxs) {
            return rand(0, 3) === 0 ? [] : (array)array_rand($idxs, rand(1, 3));
        }, range(1, $nQuestions));

        $plaintext = Small_JSONBallotEncoding::encode($votePlain, EGPlaintext::class);
        $cipher = $election->public_key->encrypt($plaintext);

        // #################################### check decryption >>>>>

        /** @var EGCiphertext $cipher */
        $cipherReEncrypted1 = $cipher->reEncrypt();
        /** @var EGCiphertext $cipherDecrypted1 */
        $cipherDecrypted1 = $trustee1->private_key->partiallyDecrypt($cipherReEncrypted1);

        $cipherReEncrypted2 = $cipherDecrypted1->reEncrypt();
        /** @var EGCiphertext $pt */
        $cipherDecrypted2 = $trustee2->private_key->partiallyDecrypt($cipherReEncrypted2);

        $cipherReEncrypted3 = $cipherDecrypted2->reEncrypt();
        /** @var EGCiphertext $pt */
        $cipherDecrypted3 = $trustee3->private_key->partiallyDecrypt($cipherReEncrypted3);

        $extractedPlainText = $cipherDecrypted3->extractPlainTextFromBeta(true);
        self::assertTrue($plaintext->equals($extractedPlainText));
        self::assertEquals($votePlain, Small_JSONBallotEncoding::decode($extractedPlainText));

        // #################################### check decryption <<<<<

        /** @noinspection PhpParamsInspection */
        $proof1 = EGDLogProof::generate($trustee1->private_key, $cipherReEncrypted1);
        /** @noinspection PhpParamsInspection */
        self::assertTrue($proof1->isValid(
            $trustee1->public_key,
            $cipherReEncrypted1,
            $cipherDecrypted1->extractPlainTextFromBeta(true)
        ));

        /** @noinspection PhpParamsInspection */
        $proof2 = EGDLogProof::generate($trustee2->private_key, $cipherReEncrypted2);
        /** @noinspection PhpParamsInspection */
        self::assertTrue($proof2->isValid(
            $trustee2->public_key,
            $cipherReEncrypted2,
            $cipherDecrypted2->extractPlainTextFromBeta(true)
        ));

        /** @noinspection PhpParamsInspection */
        $proof3 = EGDLogProof::generate($trustee3->private_key, $cipherReEncrypted3);
        /** @noinspection PhpParamsInspection */
        self::assertTrue($proof3->isValid(
            $trustee3->public_key,
            $cipherReEncrypted3,
            $cipherDecrypted3->extractPlainTextFromBeta(true)
        ));

    }

    /**
     * @test
     * @throws \Exception
     */
    public function multiple_servers()
    {

        $election = Election::factory()->create();
        $election->cryptosystem = CryptoSystemEnum::ElGamal();
        $election->anonymization_method = AnonymizationMethodEnum::DecReEncMixNet();
        $election->save();

        $nQuestions = 3;
        self::createElectionQuestions($election, $nQuestions);

        $peer1 = PeerServer::factory()->create();
        $trustee1 = $election->createPeerServerTrustee($peer1);
        $trustee1->generateKeyPair();
        $trustee1->save();

        $peer2 = PeerServer::factory()->create();
        $trustee2 = $election->createPeerServerTrustee($peer2);
        $trustee2->generateKeyPair();
        $trustee2->save();

        $peer3 = PeerServer::factory()->create();
        $trustee3 = $election->createPeerServerTrustee($peer3);
        $trustee3->generateKeyPair();
        $trustee3->save();

        $election->min_peer_count_t = 3;
        $election->voting_started_at = Carbon::now();
        $election->save();

//        $election->preFreeze();
        $election->actualFreeze();

        // public key of election is the combination
        /** @noinspection PhpParamsInspection */
        self::assertTrue($trustee1->public_key->combine($trustee2->public_key)->combine($trustee3->public_key)
            ->equals($election->public_key));

        // cast votes
        $ballots = [];
        for ($i = 0; $i < 5; $i++) {
            $user = User::factory()->create();
            $voter = new Voter();
            $voter->user_id = $user->id;
            $voter->election_id = $election->id;
            $voter->save();

            // generate a JSON vote structure
            $idxs = [1 => 1, 2 => 2, 3 => 3];
            $votePlain = array_map(function () use ($idxs) {
                return rand(0, 3) === 0 ? [] : (array)array_rand($idxs, rand(1, 3));
            }, range(1, $nQuestions));
            $ballots[] = $votePlain;

            $plaintext = Small_JSONBallotEncoding::encode($votePlain, EGPlaintext::class);
            $cipher = $election->public_key->encrypt($plaintext);

            $data = ['vote' => $cipher->toArray(true)];

            /**
             * @see \App\Http\Controllers\CastVoteController::store()
             */
            $token = $user->getNewJwtToken();
            $response = $this->withHeaders(['Authorization' => "Bearer $token"])
                ->json('POST', "api/elections/$election->slug/cast", $data);

            self::assertResponseStatusCode(200, $response);
        }

        // ################# MIX 1
        $mix1 = Mix::generate($election, null, $trustee1);
        /** @noinspection PhpParamsInspection */
        self::assertTrue($mix1->getMixWithShadowMixes()->getPrimaryMix()->ciphertexts[0]->pk->equals(
            $trustee1->public_key->combine($trustee2->public_key)->combine($trustee3->public_key)
        ));
        $mix1->verify();
        self::assertEquals($trustee1->id, $mix1->trustee_id);
        self::assertTrue($mix1->is_valid);

        // ################# MIX 2
        $mix2 = Mix::generate($election, $mix1, $trustee2);
        /** @noinspection PhpParamsInspection */
        self::assertTrue($mix2->getMixWithShadowMixes()->getPrimaryMix()->ciphertexts[0]->pk->equals(
            $trustee2->public_key->combine($trustee3->public_key)
        ));
        $mix2->verify();
        self::assertEquals($trustee2->id, $mix2->trustee_id);
        self::assertTrue($mix2->is_valid);

        // ################# MIX 3
        $mix3 = Mix::generate($election, $mix2, $trustee3);
        self::assertTrue($mix3->getMixWithShadowMixes()->getPrimaryMix()->ciphertexts[0]->pk->equals(
            $trustee3->public_key
        ));
        $mix3->verify();
        self::assertEquals($trustee3->id, $mix3->trustee_id);
        self::assertTrue($mix3->is_valid);

        // ################# check ciphertexts match input
        $votes = array_map(function (EGCiphertext $ct) {
            return Small_JSONBallotEncoding::decode($ct->extractPlainTextFromBeta(true));
        }, $mix3->getMixWithShadowMixes()->getPrimaryMix()->ciphertexts);

        usort($ballots, function ($a, $b) {
            return sha1(json_encode($a)) > sha1(json_encode($b));
        });
        usort($votes, function ($a, $b) {
            return sha1(json_encode($a)) > sha1(json_encode($b));
        });
        self::assertEquals($ballots, $votes);

    }

    /**
     * @test
     * @throws \Exception
     */
    public function single_server()
    {
        $election = Election::factory()->create();
        $election->cryptosystem = CryptoSystemEnum::ElGamal();
        $election->anonymization_method = AnonymizationMethodEnum::DecReEncMixNet();
        $election->save();

        $nQuestions = 3;
        self::createElectionQuestions($election, $nQuestions);

        $election->createPeerServerTrustee(getCurrentServer());

        $election->min_peer_count_t = 1;
        $election->voting_started_at = Carbon::now();
        $election->save();

        $election->preFreeze();
        $election->actualFreeze();

        // cast votes
        $ballots = [];
        for ($i = 0; $i < 5; $i++) {
            $user = User::factory()->create();
            $voter = new Voter();
            $voter->user_id = $user->id;
            $voter->election_id = $election->id;
            $voter->save();

            // generate a JSON vote structure
            $idxs = [1 => 1, 2 => 2, 3 => 3];
            $votePlain = array_map(function () use ($idxs) {
                return rand(0, 3) === 0 ? [] : (array)array_rand($idxs, rand(1, 3));
            }, range(1, $nQuestions));
            $ballots[] = $votePlain;

            $plaintext = Small_JSONBallotEncoding::encode($votePlain, EGPlaintext::class);
            $cipher = $election->public_key->encrypt($plaintext);

            $data = ['vote' => $cipher->toArray(true)];

            /**
             * @see \App\Http\Controllers\CastVoteController::store()
             */
            $token = $user->getNewJwtToken();
            $response = $this->withHeaders(['Authorization' => "Bearer $token"])
                ->json('POST', "api/elections/$election->slug/cast", $data);

            self::assertResponseStatusCode(200, $response);
        }

        self::purgeJobs();
        $election->anonymization_method->getClass()::afterVotingPhaseEnds($election);
        self::assertNotEquals(0, self::getPendingJobCount());

        self::runFirstPendingJob();

        /** @var \App\Models\Mix $lastMix */
        $lastMix = $election->mixes()->latest()->firstOrFail();
        $lastMix->verify();
        self::assertTrue($lastMix->is_valid);

        $votes = array_map(function (EGCiphertext $ct) {
            return Small_JSONBallotEncoding::decode($ct->extractPlainTextFromBeta(true));
        }, $lastMix->getMixWithShadowMixes()->getPrimaryMix()->ciphertexts);

        usort($ballots, function ($a, $b) {
            return sha1(json_encode($a)) > sha1(json_encode($b));
        });
        usort($votes, function ($a, $b) {
            return sha1(json_encode($a)) > sha1(json_encode($b));
        });
        self::assertEquals($ballots, $votes);


        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $election->anonymization_method->getClass()::afterSuccessfulMixProcess($election);

        self::assertNotNull($election->tallying_finished_at);
    }

}
