<?php

namespace Tests\Crypto;

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

        $plain = Str::random(100);

        $msg = EGPlaintext::fromString($plain, $pair->pk);

        $cipher = $pair->pk->encrypt($msg->m);

        $out = $pair->sk->decrypt($cipher)->toString();

        $this->assertEquals($plain, $out);

    }

}
