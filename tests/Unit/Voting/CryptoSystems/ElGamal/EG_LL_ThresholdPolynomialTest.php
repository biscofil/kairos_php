<?php


namespace Tests\Unit\Voting\CryptoSystems\ElGamal;


use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use phpseclib3\Math\BigInteger;
use Tests\TestCase;

/**
 * Class EG_LL_ThresholdPolynomialTest
 * @package Tests\Unit\Voting\CryptoSystems\ElGamal
 */
class EG_LL_ThresholdPolynomialTest extends TestCase
{

    /**
     * @test
     */
    public function combine_sk()
    {

        $kp1 = EGKeyPair::generate();
        $kp2 = EGKeyPair::generate();
        $kp3 = EGKeyPair::generate();

        $pk = $kp1->pk->combine($kp2->pk)->combine($kp3->pk);

        $plain = new EGPlaintext(BigInteger::random(20));
        $cipher = $pk->encrypt($plain);

        $sk = $kp1->sk->combine($kp2->sk)->combine($kp3->sk);
        $plain2 = $sk->decrypt($cipher);

        static::assertTrue($plain->equals($plain2));

    }

    /**
     * @test
     * @throws \Exception
     */
    public function sequential_partial_decryption()
    {

        $kp1 = EGKeyPair::generate();
        $kp2 = EGKeyPair::generate();
        $kp3 = EGKeyPair::generate();

        $pk = $kp1->pk->combine($kp2->pk)->combine($kp3->pk);
        //$sk = $kp1->sk->combine($kp2->sk)->combine($kp3->sk);

        $plain = new EGPlaintext(BigInteger::random(20));
        $cipher = $pk->encrypt($plain);

        // first > second > third
        $cipher123 = $kp1->sk->partiallyDecrypt($cipher); // first server
        $cipher123 = $kp2->sk->partiallyDecrypt($cipher123); // second server
        $cipher123 = $kp3->sk->partiallyDecrypt($cipher123); // third server
        static::assertTrue($cipher123->beta->equals($plain->m));

        // third > second > first
        $cipher321 = $kp3->sk->partiallyDecrypt($cipher); // first server
        $cipher321 = $kp2->sk->partiallyDecrypt($cipher321); // first server
        $cipher321 = $kp1->sk->partiallyDecrypt($cipher321); // third server
        static::assertTrue($cipher321->beta->equals($plain->m));

    }

    /**
     * @test
     * @throws \Exception
     */
    public function sequential_partial_decryption_re_encryption()
    {

        $kp1 = EGKeyPair::generate();
        $kp2 = EGKeyPair::generate();
        $kp3 = EGKeyPair::generate();

        $pk = $kp1->pk->combine($kp2->pk)->combine($kp3->pk);

        $plain = new EGPlaintext(BigInteger::random(20));
        $cipher = $pk->encrypt($plain);

        // first server
        $cipher1 = $kp1->sk->partiallyDecrypt($cipher);
        static::assertTrue($cipher1->alpha->equals($cipher->alpha)); // not changed
        static::assertFalse($cipher1->beta->equals($cipher->beta)); // changed
        $cipher1->pk = $kp2->pk->combine($kp3->pk); // key of the next peers
        $cipher1 = $cipher1->reEncrypt();
        static::assertFalse($cipher1->alpha->equals($cipher->alpha)); // changed
        static::assertFalse($cipher1->beta->equals($cipher->beta)); // changed

        // second server
        $cipher2 = $kp2->sk->partiallyDecrypt($cipher1); // second server
        static::assertTrue($cipher2->alpha->equals($cipher1->alpha)); // not changed
        static::assertFalse($cipher2->beta->equals($cipher1->beta)); // changed
        $cipher2->pk = $kp3->pk; // key of the next peers
        $cipher2 = $cipher2->reEncrypt();
        static::assertFalse($cipher2->alpha->equals($cipher1->alpha)); // changed
        static::assertFalse($cipher2->beta->equals($cipher1->beta)); // changed

        // last server
        $cipher3 = $kp3->sk->partiallyDecrypt($cipher2); // third server

        static::assertTrue($cipher3->beta->equals($plain->m));

    }
}
