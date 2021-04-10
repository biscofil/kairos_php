<?php


namespace Tests\Unit\Voting\CryptoSystems\ElGamal;

use App\Voting\BallotEncodings\JsonBallotEncoding;
use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use phpseclib3\Math\BigInteger;
use Tests\TestCase;

/**
 * Class EGCiphertextTest
 * @package Tests\Unit\Crypto
 */
class EGCiphertextTest extends TestCase
{

    /**
     * @test
     */
    public function removeReEncryption()
    {

        $keyPair = EGKeyPair::generate();

        $plaintexts = JsonBallotEncoding::encode([123], EGPlaintext::class);
        $cipher = $keyPair->pk->encrypt($plaintexts[0]);

        $randomness = BigInteger::random(50);

        $cipher2 = $cipher->reEncryptWithRandomness($randomness)
            ->decryptWithRandomness($randomness);

        $this->assertTrue($cipher->equals($cipher2));
        $this->assertTrue($cipher->equals($cipher2));

    }

    /**
     * @test
     */
    public function randomnessCombination()
    {

        $keyPair = EGKeyPair::generate();

        $msg = BigInteger::random(10);
        $plain = new EGPlaintext($msg);

        $mainMixRandomness = randomBIgt($keyPair->pk->q);
        $shadowMixRandomness = randomBIgt($keyPair->pk->q);

        $mainMixCipher = $keyPair->pk->encryptWithRandomness($plain, $mainMixRandomness);

        $shadowMixCipher = $keyPair->pk->encryptWithRandomness($plain, $shadowMixRandomness);

        $diff = $mainMixRandomness->subtract($shadowMixRandomness)->modPow(BI1(), $keyPair->pk->p);

        $reEncryptedCipher = $shadowMixCipher->reEncryptWithRandomness($diff);

        $this->assertTrue($keyPair->sk->decrypt($reEncryptedCipher)->equals($plain));
        $this->assertTrue($keyPair->sk->decrypt($mainMixCipher)->equals($plain));
    }

}
