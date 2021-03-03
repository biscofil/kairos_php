<?php

namespace Tests\Unit\Crypto;

use App\Crypto\EGKeyPair;
use App\Crypto\EGPlaintext;
use Illuminate\Support\Str;
use Tests\TestCase;

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

        $plain = json_encode($obj);

        $msg = EGPlaintext::fromString($plain, $pair->pk);

        $cipher = $msg->encrypt();

//        dump($cipher->alpha->toHex());
//        dump(base64_encode($cipher->alpha->toBytes()));

        $out = $pair->sk->decrypt($cipher)->toString();

        $this->assertEquals($plain, $out);

//        $out = json_decode($out);

//        dump($out);

    }

}
