<?php


namespace Tests\Unit\Voting\CryptoSystems\ElGamal;


use App\Voting\CryptoSystems\ElGamal\DLogProof;
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

        $proof = $keyPair->sk->generateDLogProof([DLogProof::class, 'DLogChallengeGenerator']);

        $r = $proof->verify($keyPair->pk, [DLogProof::class, 'DLogChallengeGenerator']);
        static::assertTrue($r);

        $proof = $proof->toArray();
        $proof = DLogProof::fromArray($proof);

        $r = $proof->verify($keyPair->pk, [DLogProof::class, 'DLogChallengeGenerator']);
        static::assertTrue($r);

    }

}
