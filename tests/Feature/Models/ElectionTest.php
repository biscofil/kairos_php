<?php

namespace Tests\Feature\Models;

use App\Enums\AnonymizationMethodEnum;
use App\Enums\CryptoSystemEnum;
use App\Models\Election;
use App\Models\Trustee;
use App\Models\User;
use App\Voting\BallotEncodings\Small_JSONBallotEncoding;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use App\Voting\CryptoSystems\RSA\RSAPlaintext;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;
use Throwable;

/**
 * Class ElectionTest
 * @package Tests\Feature\Models
 */
class ElectionTest extends TestCase
{

    /**
     * @ TODO test
     */
    public function RSA_Election()
    {

        $user = User::factory()->create();

        // create election
        $election = Election::factory()->make();
        $election->cryptosystem = CryptoSystemEnum::RSA();
        $response = $this->actingAs($user)->json('POST', 'api/elections', $election->toArray());
        self::assertResponseStatusCode(201, $response);

        $election = Election::findOrFail($response->json('id'));

        $privateKeys = [];
        for ($i = 1; $i < 5; $i++) {
            $pair = $election->cryptosystem->getClass()::getKeyPairClass()::generate();
            $trusteeUser = $election->createUserTrustee(User::factory()->create());
            $trusteeUser->public_key = $pair->pk;
            //$trusteeUser->private_key = $pair->sk; // uploaded after election
            $trusteeUser->save();
            $privateKeys[strval($trusteeUser->id)] = $pair->sk; // uploaded after election
        }

        // For RSA, this does nothing
        $election->cryptosystem->getClass()::onElectionFreeze($election);

        $plainVote = ['v' => Str::random(3)];

        $plaintext = Small_JSONBallotEncoding::encode($plainVote, RSAPlaintext::class);

//        $cipher = $election->public_key->encrypt($plaintext); // TODO use trustee public key sequentially
        // after voting phase ends

        // trustee upload private keys
        foreach ($privateKeys as $trusteeID => $privateKey) {
            $trustee = Trustee::findOrFail(intval($trusteeID));
            //$trustee->private_key = $privateKey; // uploaded after election
            //$trustee->save();

            $plaintext = $trustee->public_key->encrypt($plaintext);
            $plaintext = new RSAPlaintext($plaintext->cipherText);
        }

        $out = $plaintext;

        // compute private key and decrypt
        //(ElGamal::getInstance())->generateCombinedPrivateKey($election);  // TODO fix!!!
        $election->cryptosystem->getClass()::afterAnonymizationProcessEnds($election);

        //$out = $election->private_key->decrypt($cipher);
        static::assertEquals($plainVote, Small_JSONBallotEncoding::decode($out));

        // corrupt one private key
        $trustee = Trustee::findOrFail(intval(array_keys($privateKeys)[0])); // take the first trustee
        $trustee->private_key = $privateKeys[array_keys($privateKeys)[1]]; // assign a wrong private key
        $trustee->save();

        // compute private key and decrypt
        //(ElGamal::getInstance())->generateCombinedPrivateKey($election);  // TODO fix!!!
        $election->cryptosystem->getClass()::afterAnonymizationProcessEnds($election);

        //TODO $out = $election->private_key->decrypt($cipher);
        //TODO $this->assertNotEquals($plainVote, JsonBallotEncoding::decode($out));

    }

    /**
     * @test
     */
    public function ElGamal_Election()
    {

        $user = User::factory()->create();

        // create election
        $election = Election::factory()->make();
        $election->cryptosystem = CryptoSystemEnum::ElGamal();
        $response = $this->actingAs($user)
            ->json('POST', 'api/elections', $election->toArray());
        self::assertResponseStatusCode(201, $response);

        $election = Election::findOrFail($response->json('id'));
        self::createElectionQuestions($election);

        $privateKeys = [];
        for ($i = 1; $i < 5; $i++) {
            $user = User::factory()->create();
            $pair = $election->cryptosystem->getClass()::getKeyPairClass()::generate();
            $trusteeUser = $election->createUserTrustee($user);
            $trusteeUser->public_key = $pair->pk;
            //$trusteeUser->private_key = $pair->sk; // uploaded after election
            $trusteeUser->save();
            $privateKeys[strval($trusteeUser->id)] = $pair->sk; // uploaded after election
        }

        $election->min_peer_count_t = count($privateKeys);
        $election->save();

        // generateCombinedPublicKey;
        static::assertNull($election->public_key);
        $election->cryptosystem->getClass()::onElectionFreeze($election);
        static::assertNotNull($election->public_key);

        $plainVote = [[2], [3], [1]];
        $plaintext = Small_JSONBallotEncoding::encode($plainVote, EGPlaintext::class);
        $cipher = $election->public_key->encrypt($plaintext);
        // after voting phase ends

        // trustee upload private keys
        foreach ($privateKeys as $trusteeID => $privateKey) {
            $trustee = Trustee::findOrFail(intval($trusteeID));
            $trustee->private_key = $privateKey; // uploaded after election
            $trustee->save();
        }

        // compute private key and decrypt
        //(ElGamal::getInstance())->generateCombinedPrivateKey($election);  // TODO fix!!!
        $election->cryptosystem->getClass()::afterAnonymizationProcessEnds($election);

        $out = $election->private_key->decrypt($cipher);
        static::assertEquals($plainVote, Small_JSONBallotEncoding::decode($out));

        // corrupt one private key
        $trustee = Trustee::findOrFail(intval(array_keys($privateKeys)[0])); // take the first trustee
        $trustee->private_key = $privateKeys[array_keys($privateKeys)[1]]; // assign a wrong private key
        $trustee->save();

        // compute private key and decrypt
        // generateCombinedPrivateKey($election);
        $election->cryptosystem->getClass()::afterAnonymizationProcessEnds($election);

        $out = $election->private_key->decrypt($cipher);
        try {
            // if succeeds (unlikely)
            static::assertNotEquals($plainVote, Small_JSONBallotEncoding::decode($out));
        } catch (Throwable $e) {
            self::assertTrue(true);
        }
    }

    /**
     * @test
     */
    public function ElGamal_close_voting_phase()
    {
        $user = User::factory()->create();

        // create election
        $election = Election::factory()->make();
        $election->cryptosystem = CryptoSystemEnum::ElGamal();
        $election->anonymization_method = AnonymizationMethodEnum::EncMixNet();
        $response = $this->actingAs($user)
            ->json('POST', 'api/elections', $election->toArray());
        self::assertResponseStatusCode(201, $response);

        $election = Election::findOrFail($response->json('id'));
        $election->voting_starts_at = Carbon::now();
        $election->voting_started_at = Carbon::now();
        $election->voting_ends_at = Carbon::now();

        $election->createPeerServerTrustee(getCurrentServer());

        $election->preFreeze();
        $election->actualFreeze();

        self::createElectionQuestions($election);

        for ($i = 0; $i < rand(3, 5); $i++) {

            // generate a JSON vote structure
            $votePlain = [
                [3], [], []
            ];

            /** @var RSAPlaintext $plaintext */  // TODO check!!
            $plaintext = Small_JSONBallotEncoding::encode($votePlain, EGPlaintext::class);
            $cipher = $election->public_key->encrypt($plaintext); // encrypt it
            $data = ['vote' => $cipher->toArray(true)];
            /**
             * @see \App\Http\Controllers\CastVoteController::store()
             */
            $token = $user->getNewJwtToken();
            $response = $this->withHeaders(['Authorization' => "Bearer $token"])
                ->json('POST', "api/elections/{$election->slug}/cast", $data);
            self::assertResponseStatusCode(200, $response);

        }

        $election->closeVotingPhase();
        static::assertNotNull($election->voting_ended_at);

    }

}
