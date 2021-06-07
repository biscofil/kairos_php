<?php

namespace Tests\Unit\Voting\CryptoSystems\ElGamal;

use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
use App\Voting\CryptoSystems\ElGamal\EGParameterSet;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use App\Voting\CryptoSystems\ElGamal\EGPublicKey;
use Exception;
use Tests\TestCase;

/**
 * Class EGPublicKeyTest
 * @package Tests\Unit\Voting\CryptoSystems\ElGamal
 */
class EGPublicKeyTest extends TestCase
{

    /**
     * @test
     * @throws Exception
     */
    public function combine()
    {
        $a = EGPublicKey::fromArray([
            'ps' => ['g' => 1, 'p' => 8, 'q' => 1],
            'y' => 3
        ], false, 10);

        $b = EGPublicKey::fromArray([
            'ps' => ['g' => 1, 'p' => 8, 'q' => 1],
            'y' => 3
        ], false, 10);

        $c = $a->combine($b);
        static::assertTrue($c->parameterSet->equals($a->parameterSet));
        static::assertTrue($c->parameterSet->equals($a->parameterSet));
        static::assertTrue($c->parameterSet->equals($a->parameterSet));
        static::assertTrue($c->y->equals(BI(1))); // 3*3 mod 8 = 1
    }

    /**
     * @test
     */
    public function plaintext_max_bit_len_is_p_len()
    {

        $ps = EGParameterSet::getDefault();
        $kp = EGKeyPair::generate($ps);

        $l = $ps->p->getLength();

        for ($i = $l - 2; $i < $l + 2; $i++) {

            $n = str_repeat('1', $i);

            try {
                $ptIn = new EGPlaintext(BI($n, 2));
                $ct = $kp->pk->encrypt($ptIn);
                $ptOut = $kp->sk->decrypt($ct);
                self::assertEquals($i < $l, $ptIn->equals($ptOut));
            } catch (\Exception $e) {
                self::assertTrue($i >= $l);
            }

        }
    }
}
