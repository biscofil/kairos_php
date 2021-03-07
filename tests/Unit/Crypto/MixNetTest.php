<?php

namespace Tests\Unit\Crypto;

use App\Crypto\EGKeyPair;
use App\Crypto\EGPlaintext;
use App\Crypto\MixNet;
use Illuminate\Support\Str;
use Tests\TestCase;

class MixNetTest extends TestCase
{

    /**
     * @test
     */
    public function works()
    {

        $electionKeyPair = EGKeyPair::generate();

        $original_ciphers = [];

        for ($i = 0; $i < 5; $i++) {
            $obj = [
                'initial_pos' => $i,
                'v' => Str::random(3)
            ];
            $plain = json_encode($obj);
            $msg = EGPlaintext::fromString($plain, $electionKeyPair->pk);
            $ciphers[] = $msg->encrypt();
        }

        // 2 nodes
        $ciphers = (new MixNet($original_ciphers))->toArray();
        $ciphers = (new MixNet($ciphers))->toArray();

        // assert same as the original
        $this->assertTrue(collect($ciphers)->map(function ($cipher) use ($electionKeyPair) {
            return json_decode($electionKeyPair->sk->decrypt($cipher)->toString(), true);
        })->pluck('initial_pos')->diffAssoc(collect($original_ciphers))->isEmpty());

    }

}

