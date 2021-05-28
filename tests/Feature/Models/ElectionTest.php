<?php

namespace Tests\Feature\Models;

use App\Enums\CryptoSystemEnum;
use App\Models\Election;
use App\Models\Trustee;
use App\Models\User;
use App\Voting\BallotEncodings\JsonBallotEncoding;
use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use App\Voting\CryptoSystems\RSA\RSAPlaintext;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

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
        /** @var Election $data */
        $data = Election::factory()->make();
        $data->cryptosystem = CryptoSystemEnum::RSA();
        $response = $this->actingAs($user)
            ->json('POST', 'api/elections', $data->toArray());
        $this->assertResponseStatusCode(201, $response);

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

        $plaintext = (JsonBallotEncoding::encode($plainVote, RSAPlaintext::class))[0];

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
        static::assertEquals($plainVote, JsonBallotEncoding::decode($out));

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
        /** @var Election $data */
        $data = Election::factory()->make();
        $data->cryptosystem = CryptoSystemEnum::ElGamal();
//        dd($data->toArray());
        $response = $this->actingAs($user)
            ->json('POST', 'api/elections', $data->toArray());
        $this->assertResponseStatusCode(201, $response);

        $election = Election::findOrFail($response->json('id'));

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

        $plainVote = ['v' => Str::random(3)];
        $plaintext = (JsonBallotEncoding::encode($plainVote, EGPlaintext::class))[0];
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
        static::assertEquals($plainVote, JsonBallotEncoding::decode($out));

        // corrupt one private key
        $trustee = Trustee::findOrFail(intval(array_keys($privateKeys)[0])); // take the first trustee
        $trustee->private_key = $privateKeys[array_keys($privateKeys)[1]]; // assign a wrong private key
        $trustee->save();

        // compute private key and decrypt
        // generateCombinedPrivateKey($election);
        $election->cryptosystem->getClass()::afterAnonymizationProcessEnds($election);

        $out = $election->private_key->decrypt($cipher);
        static::assertNotEquals($plainVote, JsonBallotEncoding::decode($out));

    }

    /**
     * @test
     */
    public function ElGamal_close_voting_phase()
    {
        $user = User::factory()->create();

        // create election
        /** @var Election $data */
        $data = Election::factory()->make();
        $data->cryptosystem = CryptoSystemEnum::ElGamal();
//        dd($data->toArray());
        $response = $this->actingAs($user)
            ->json('POST', 'api/elections', $data->toArray());
        $this->assertResponseStatusCode(201, $response);

        $keypair = EGKeyPair::generate(); // TODO check!!

        $election = Election::findOrFail($response->json('id'));
        $election->frozen_at = Carbon::now();
        $election->voting_starts_at = Carbon::now();
        $election->voting_started_at = Carbon::now();
        $election->voting_ends_at = Carbon::now();
        $election->public_key = $keypair->pk;
        $election->save();


        for ($i = 0; $i < rand(3, 5); $i++) {

            // generate a JSON vote structure
            $votePlain = [
                Str::random(10) => Str::random(10),
                Str::random(10) => [
                    Str::random(10),
                    Str::random(10),
                ]
            ];

            /** @var RSAPlaintext $plaintext */  // TODO check!!
            $plaintext = (JsonBallotEncoding::encode($votePlain, EGPlaintext::class))[0];
            $cipher = $keypair->pk->encrypt($plaintext); // encrypt it
            $data = ['vote' => $cipher->toArray(true)];
            /**
             * @see \App\Http\Controllers\CastVoteController::store()
             */
            $token = $user->getNewJwtToken();
            $response = $this->withHeaders(['Authorization' => "Bearer $token"])
                ->json('POST', "api/elections/$election->slug/cast", $data);
            $this->assertResponseStatusCode(201, $response);

        }

        $election->closeVotingPhase();
        static::assertNotNull($election->voting_ended_at);

    }

}
