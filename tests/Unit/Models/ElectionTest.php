<?php

namespace Tests\Unit\Models;

use App\Crypto\EGKeyPair;
use App\Crypto\EGPlaintext;
use App\Models\Election;
use App\Models\Trustee;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class ElectionTest extends TestCase
{

    /**
     * @test
     */
    public function combinedPublicAndPrivateKeys()
    {

        $user = User::factory()->create();

        // create election
        $data = Election::factory()->make()->toArray();
        $response = $this->actingAs($user)->json('POST', 'api/elections', $data);
        $this->assertResponseStatusCode(201, $response);
        $election = Election::findOrFail($response->json('id'));

        $privateKeys = [];
        for ($i = 1; $i < 5; $i++) {
            $pair = EGKeyPair::generate();
            $trusteeUser = $election->createTrustee(User::factory()->create());
            $trusteeUser->public_key = $pair->pk;
            //$trusteeUser->private_key = $pair->sk; // uploaded after election
            $trusteeUser->save();
            $privateKeys[strval($trusteeUser->id)] = $pair->sk; // uploaded after election
        }

        $election->public_key = $election->generateCombinedPublicKey();
        $plainVote = ['v' => Str::random(3)];
        $plainVoteJson = json_encode($plainVote);
        $msg = EGPlaintext::fromString($plainVoteJson, $election->public_key);
        $cipher = $msg->encrypt();

        // after voting phase ends

        // trustee upload private keys
        foreach ($privateKeys as $trusteeID => $privateKey) {
            $trustee = Trustee::findOrFail(intval($trusteeID));
            $trustee->private_key = $privateKey; // uploaded after election
            $trustee->save();
        }

        // compute private key and decrypt
        $election->private_key = $election->generateCombinedPrivateKey();
        $out = $election->private_key->decrypt($cipher)->toString();
        $this->assertEquals($plainVoteJson, $out);

        // corrupt one private key
        $trustee = Trustee::findOrFail(intval(array_keys($privateKeys)[0])); // take the first trustee
        $trustee->private_key = $privateKeys[array_keys($privateKeys)[1]]; // assign a wrong private key
        $trustee->save();

        // compute private key and decrypt
        $election->private_key = $election->generateCombinedPrivateKey();
        $out = $election->private_key->decrypt($cipher)->toString();
        $this->assertNotEquals($plainVoteJson, $out);

    }

}
