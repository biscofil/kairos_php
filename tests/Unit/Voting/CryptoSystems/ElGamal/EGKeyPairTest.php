<?php

namespace Tests\Unit\Voting\CryptoSystems\ElGamal;

use App\Voting\BallotEncodings\JsonBallotEncoding;
use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use phpseclib3\Math\BigInteger;
use Tests\TestCase;

/**
 * Class EGKeyPairTest
 * @package Tests\Unit\Voting\CryptoSystems\ElGamal
 */
class EGKeyPairTest extends TestCase
{

    /**
     * @test
     */
    public function generate()
    {

        $pair = EGKeyPair::generate();

        $obj = [
            Str::random(30) => Str::random(30),
            Str::random(30) => [
                Str::random(30),
                Str::random(30),
            ]
        ];

        $plaintexts = JsonBallotEncoding::encode($obj);
        $cipher = $pair->pk->encrypt($plaintexts[0]);

        $out = $pair->sk->decrypt($cipher);
        $this->assertEquals($obj, JsonBallotEncoding::decode($out));

    }

    /**
     * @test
     */
    public function storeToFile_and_fromFile()
    {

        $kp1 = EGKeyPair::generate();

        $path = 'keypair.json';

        $kp1->storeToFile($path);
        $this->assertTrue(Storage::exists($path));

        $loadedKP = EGKeyPair::fromFile($path);

        $this->assertTrue($kp1->sk->x->equals($loadedKP->sk->x));

    }

}
