<?php

namespace Tests\Feature\Models;

use App\Enums\CryptoSystemEnum;
use App\Models\Election;
use App\Models\Trustee;
use App\Models\User;
use App\Voting\BallotEncodings\JsonBallotEncoding;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use App\Voting\CryptoSystems\RSA\RSAPlaintext;
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
            $pair = $election->cryptosystem->getCryptoSystemClass()::generateKeypair();
            $trusteeUser = $election->createUserTrustee(User::factory()->create());
            $trusteeUser->public_key = $pair->pk;
            //$trusteeUser->private_key = $pair->sk; // uploaded after election
            $trusteeUser->save();
            $privateKeys[strval($trusteeUser->id)] = $pair->sk; // uploaded after election
        }

        // For RSA, this does nothing
        $election->cryptosystem->getCryptoSystemClass()::onElectionFreeze($election);

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
        $election->cryptosystem->getCryptoSystemClass()::afterAnonymizationProcessEnds($election);

        //$out = $election->private_key->decrypt($cipher);
        $this->assertEquals($plainVote, JsonBallotEncoding::decode($out));

        // corrupt one private key
        $trustee = Trustee::findOrFail(intval(array_keys($privateKeys)[0])); // take the first trustee
        $trustee->private_key = $privateKeys[array_keys($privateKeys)[1]]; // assign a wrong private key
        $trustee->save();

        // compute private key and decrypt
        //(ElGamal::getInstance())->generateCombinedPrivateKey($election);  // TODO fix!!!
        $election->cryptosystem->getCryptoSystemClass()::afterAnonymizationProcessEnds($election);

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
        $response = $this->actingAs($user)
            ->json('POST', 'api/elections', $data->toArray());
        $this->assertResponseStatusCode(201, $response);

        $election = Election::findOrFail($response->json('id'));

        $privateKeys = [];
        for ($i = 1; $i < 5; $i++) {
            $user = User::factory()->create();
            $pair = $election->cryptosystem->getCryptoSystemClass()::generateKeypair();
            $trusteeUser = $election->createUserTrustee($user);
            $trusteeUser->public_key = $pair->pk;
            //$trusteeUser->private_key = $pair->sk; // uploaded after election
            $trusteeUser->save();
            $privateKeys[strval($trusteeUser->id)] = $pair->sk; // uploaded after election
        }

        $election->min_peer_count_t = count($privateKeys);
        $election->save();

        // generateCombinedPublicKey;
        $this->assertNull($election->public_key);
        $election->cryptosystem->getCryptoSystemClass()::onElectionFreeze($election);
        $this->assertNotNull($election->public_key);

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
        $election->cryptosystem->getCryptoSystemClass()::afterAnonymizationProcessEnds($election);

        $out = $election->private_key->decrypt($cipher);
        $this->assertEquals($plainVote, JsonBallotEncoding::decode($out));

        // corrupt one private key
        $trustee = Trustee::findOrFail(intval(array_keys($privateKeys)[0])); // take the first trustee
        $trustee->private_key = $privateKeys[array_keys($privateKeys)[1]]; // assign a wrong private key
        $trustee->save();

        // compute private key and decrypt
        // generateCombinedPrivateKey($election);
        $election->cryptosystem->getCryptoSystemClass()::afterAnonymizationProcessEnds($election);

        $out = $election->private_key->decrypt($cipher);
        $this->assertNotEquals($plainVote, JsonBallotEncoding::decode($out));

    }

}
