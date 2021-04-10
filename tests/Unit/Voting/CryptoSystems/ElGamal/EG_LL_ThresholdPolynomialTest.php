<?php


namespace Tests\Unit\Voting\CryptoSystems\ElGamal;


use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use App\Voting\CryptoSystems\ElGamal\EGThresholdPolynomial;
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
    public function combine_sk(){

        $kp1 = EGKeyPair::generate();
        $kp2 = EGKeyPair::generate();
        $kp3 = EGKeyPair::generate();

        $pk = $kp1->pk->combine($kp2->pk)->combine($kp3->pk);

        $plain = new EGPlaintext(BigInteger::random(20));
        $cipher = $pk->encrypt($plain);

        $sk = $kp1->sk->combine($kp2->sk)->combine($kp3->sk);
        $plain2 = $sk->decrypt($cipher);

        $this->assertTrue($plain->equals($plain2));

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

        $cipher123 = $kp1->sk->partiallyDecrypt($cipher); // first server
        $cipher123 = $kp2->sk->partiallyDecrypt($cipher123); // second server
        $cipher123 = $kp3->sk->partiallyDecrypt($cipher123); // third server
        $this->assertTrue($cipher123->beta->equals($plain->m));

        $cipher321 = $kp3->sk->partiallyDecrypt($cipher); // first server
        $cipher321 = $kp2->sk->partiallyDecrypt($cipher321); // first server
        $cipher321 = $kp1->sk->partiallyDecrypt($cipher321); // third server
        $this->assertTrue($cipher321->beta->equals($plain->m));

    }
}
