<?php


namespace Tests\Unit;


use phpseclib3\Math\BigInteger;
use Tests\TestCase;

class MathTest extends TestCase
{

    /**
     * @test
     */
    public function inverse_of_product()
    {

        $a = BigInteger::random(20);
        $b = BigInteger::random(20);
        $c = BigInteger::random(20);

        $p = BigInteger::randomPrime(20);

        dump("a = {$a->toString()}, b = {$b->toString()}, c = {$c->toString()}");
        dump("p = {$p->toString()}");

        // ( a * b )^-1 mod p
        // ( b * a )^-1 mod p
        $first = $a->multiply($b)->multiply($c)
            ->modInverse($p);

        dump("( {$a->toString()} * {$b->toString()} * {$c->toString()} )^-1 mod {$p->toString()} " .
            "= {$first->toString()}");

        // a^-1 * b^-1 mod p
        $second = $a->modInverse($p)
            ->multiply($b->modInverse($p))
            ->multiply($c->modInverse($p))
            ->modPow(BI1(), $p);

        $this->assertTrue($first->equals($second));


    }


}
