<?php


namespace Tests\Unit\Voting\CryptoSystems\ElGamal;

use App\Voting\BallotEncodings\Small_JSONBallotEncoding;
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

        $plaintexts = Small_JSONBallotEncoding::encode([123], EGPlaintext::class);
        $cipher = $keyPair->pk->encrypt($plaintexts);

        $randomness = BigInteger::random(50);

        $cipher2 = $cipher->reEncryptWithRandomness($randomness)
            ->reverseReEncryptionWithRandomness($randomness);

        static::assertTrue($cipher->equals($cipher2));

    }

    /**
     * @test
     */
    public function randomnessCombination()
    {

        $keyPair = EGKeyPair::generate();

        $msg = BigInteger::random(10);
        $plain = new EGPlaintext($msg);

        $mainMixRandomness = randomBIgt($keyPair->pk->parameterSet->q);
        $shadowMixRandomness = randomBIgt($keyPair->pk->parameterSet->q);

        $mainMixCipher = $keyPair->pk->encryptWithRandomness($plain, $mainMixRandomness);

        $shadowMixCipher = $keyPair->pk->encryptWithRandomness($plain, $shadowMixRandomness);

        $diff = $mainMixRandomness->subtract($shadowMixRandomness)->modPow(BI1(), $keyPair->pk->parameterSet->p);

        $reEncryptedCipher = $shadowMixCipher->reEncryptWithRandomness($diff);

        static::assertTrue($keyPair->sk->decrypt($reEncryptedCipher)->equals($plain));
        static::assertTrue($keyPair->sk->decrypt($mainMixCipher)->equals($plain));
    }

}
