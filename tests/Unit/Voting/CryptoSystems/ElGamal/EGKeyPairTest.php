<?php

namespace Tests\Unit\Voting\CryptoSystems\ElGamal;

use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
use App\Voting\CryptoSystems\ElGamal\EGParameterSet;
use Illuminate\Support\Facades\Storage;
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

        $ps = EGParameterSet::getDefault();
        $pair = EGKeyPair::generate($ps);
        self::assertValidEGKeyPair($pair->pk, $pair->sk);

//        $obj = [
//            Str::random(30) => Str::random(30),
//            Str::random(30) => [
//                Str::random(30),
//                Str::random(30),
//            ]
//        ];
//
//        $plaintexts = JsonBallotEncoding::encode($obj, EGPlaintext::class);
//        $cipher = $pair->pk->encrypt($plaintexts[0]);
//
//        $out = $pair->sk->decrypt($cipher);
//        $this->assertEquals($obj, JsonBallotEncoding::decode($out));

    }

    /**
     * @test
     */
    public function storeToFile_and_fromFile()
    {

        $kp1 = EGKeyPair::generate();

        $path = 'keypair.json';

        $kp1->storeToFile($path);

        static::assertTrue(Storage::exists($path));

        $loadedKP = EGKeyPair::fromFile($path);

        static::assertTrue($kp1->sk->x->equals($loadedKP->sk->x));
        static::assertTrue($kp1->pk->y->equals($loadedKP->pk->y));

        Storage::delete($path);

    }

}
