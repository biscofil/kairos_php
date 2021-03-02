<?php

namespace Tests\Crypto;


use App\Crypto\EGPublicKey;
use phpseclib3\Math\BigInteger;
use Tests\TestCase;

class EGPublicKeyTest extends TestCase
{

    public function testCombine()
    {
        $a = EGPublicKey::fromArray(['g' => 1, 'p' => 8, 'q' => 1, 'y' => 3]);
        $b = EGPublicKey::fromArray(['g' => 1, 'p' => 8, 'q' => 1, 'y' => 3]);
        $c = $a->combine($b);
        $this->assertTrue($c->g->equals($a->g));
        $this->assertTrue($c->p->equals($a->p));
        $this->assertTrue($c->q->equals($a->q));
        $this->assertTrue($c->y->equals(new BigInteger(1))); // 3*3 mod 8 = 1
    }

}
