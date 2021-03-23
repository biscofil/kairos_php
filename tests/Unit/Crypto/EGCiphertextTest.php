<?php


namespace Tests\Unit\Crypto;


use App\Crypto\EGKeyPair;
use App\Crypto\EGPlaintext;
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

        $cipher = EGPlaintext::fromString("abc", $keyPair->pk)->encrypt();

        $randomness = BigInteger::random(50);

        $cipher2 = $cipher->reEncryptWithRandomness($randomness)
            ->decryptWithRandomness($randomness);

        $this->assertTrue($cipher->alpha->equals($cipher2->alpha));
        $this->assertTrue($cipher->beta->equals($cipher2->beta));

    }

    /**
     * @test
     */
    public function randomnessCombination()
    {

        $keyPair = EGKeyPair::generate();

        $msg = BigInteger::random(10);
        $plain = new EGPlaintext($msg, $keyPair->pk);

        $mainMixRandomness = randomBIgt($keyPair->pk->q);
        $shadowMixRandomness = randomBIgt($keyPair->pk->q);

        $mainMixCipher = $plain->encryptWithRandomness($mainMixRandomness);

        $shadowMixCipher = $plain->encryptWithRandomness($shadowMixRandomness);

        $diff = $mainMixRandomness->subtract($shadowMixRandomness)->modPow(BI1(), $keyPair->pk->p);

        $reEncryptedCipher = $shadowMixCipher->reEncryptWithRandomness($diff);

        $this->assertTrue($keyPair->sk->decrypt($reEncryptedCipher)->m->equals($msg));
        $this->assertTrue($keyPair->sk->decrypt($mainMixCipher)->m->equals($msg));
    }

}
