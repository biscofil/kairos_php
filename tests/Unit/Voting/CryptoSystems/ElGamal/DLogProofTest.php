<?php


namespace Tests\Unit\Voting\CryptoSystems\ElGamal;


use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
use App\Voting\CryptoSystems\ElGamal\EGSecretKey;
use Tests\TestCase;

class DLogProofTest extends TestCase
{

    /**
     * @test
     */
    public function proof_of_decryption()
    {

        $keyPair = EGKeyPair::generate();

        $proof = $keyPair->sk->generateDLogProof([EGSecretKey::class, 'DLogChallengeGenerator']);

        $r = $proof->verify($keyPair->pk, [EGSecretKey::class, 'DLogChallengeGenerator']);
        static::assertTrue($r);

    }

}
