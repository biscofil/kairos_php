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

        $randomness = $keyPair->pk->parameterSet->getReEncryptionFactor();

        $ciphertext = $keyPair->pk->encryptWithRandomness($plain, $randomness);

        $proof = EGDLogProof::generate($keyPair->sk, $ciphertext);

        $r = $proof->isValid($keyPair->pk, $ciphertext, $plain);
        static::assertTrue($r);

        // re encryption should have a different proof
        $randomness = BigInteger::random(50);
        $ciphertext = $ciphertext->reEncryptWithRandomness($randomness);
        $r = $proof->isValid($keyPair->pk, $ciphertext, $plain);
        static::assertFalse($r);

        // undoing re encryption should make proof work
        $ciphertext = $ciphertext->reverseReEncryptionWithRandomness($randomness);
        $r = $proof->isValid($keyPair->pk, $ciphertext, $plain);
        static::assertTrue($r);
    }

}
