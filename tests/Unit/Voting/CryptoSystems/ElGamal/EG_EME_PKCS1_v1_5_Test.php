<?php


namespace Tests\Unit\Voting\CryptoSystems\ElGamal;


use App\Voting\CryptoSystems\ElGamal\EG_EME_PKCS1_v1_5;
use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
use Illuminate\Support\Str;
use Tests\TestCase;

class EG_EME_PKCS1_v1_5_Test extends TestCase
{

    /**
     * @test
     */
    public function encryption_decryption()
    {

        $kp = EGKeyPair::generate();

        $str = Str::random(1000);

        $c = new EG_EME_PKCS1_v1_5();

        $parts = $c->encrypt($kp->pk, $str);

        $out = $c->decrypt($kp->sk, $parts);

        self::assertEquals($str, $out);
    }

}
