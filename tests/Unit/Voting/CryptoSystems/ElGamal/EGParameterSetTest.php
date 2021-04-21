<?php


namespace Tests\Unit\Voting\CryptoSystems\ElGamal;


use App\Voting\CryptoSystems\ElGamal\EGParameterSet;
use Tests\TestCase;

/**
 * Class EGParameterSetTest
 * @package Tests\Unit\Voting\CryptoSystems\ElGamal
 */
class EGParameterSetTest extends TestCase
{

    /**
     * @test
     */
    public function default_ps()
    {
        $ps = EGParameterSet::default();
        $this->assertTrue($ps->isValid());
    }

    /**
     * @test
     */
    public function random_ps()
    {
        $ps = EGParameterSet::random(5);
        $this->assertTrue($ps->isValid());
        dump($ps->toString());
    }


}
