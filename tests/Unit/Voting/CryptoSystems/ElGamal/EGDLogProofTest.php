<?php


namespace Tests\Unit\Voting\CryptoSystems\ElGamal;


use App\Voting\CryptoSystems\ElGamal\EGDLogProof;
use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use phpseclib3\Math\BigInteger;
use Tests\TestCase;

class EGDLogProofTest extends TestCase
{

    /**
     * @test
     */
    public function proof_of_decryption()
    {

        $keyPair = EGKeyPair::generate();

        $msg = BigInteger::random(10);
        $plain = new EGPlaintext($msg);

        $randomness = randomBIgt($keyPair->pk->parameterSet->q);

        $ciphertext = $keyPair->pk->encryptWithRandomness($plain, $randomness);

        $proof = EGDLogProof::generate($keyPair->sk, $ciphertext, [EGDLogProof::class, 'DLogChallengeGenerator']);

        $r = $proof->verify($keyPair->pk, $ciphertext, $plain, [EGDLogProof::class, 'DLogChallengeGenerator']);
        static::assertTrue($r);

        // re encryption should have a different proof
        $randomness = BigInteger::random(50);
        $ciphertext = $ciphertext->reEncryptWithRandomness($randomness);
        $r = $proof->verify($keyPair->pk, $ciphertext, $plain, [EGDLogProof::class, 'DLogChallengeGenerator']);
        static::assertFalse($r);

        // undoing re encryption should make proof work
        $ciphertext = $ciphertext->reverseReEncryptionWithRandomness($randomness);
        $r = $proof->verify($keyPair->pk, $ciphertext, $plain, [EGDLogProof::class, 'DLogChallengeGenerator']);
        static::assertTrue($r);
    }

}
