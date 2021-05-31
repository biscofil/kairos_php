<?php

namespace Tests\Unit\Voting\CryptoSystems\ElGamal;

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

}
