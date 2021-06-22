<?php


namespace Tests\Unit\Voting\CryptoSystems\ExpElGamal;


use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use App\Voting\CryptoSystems\ExpElGamal\ExpEGKeyPair;
use App\Voting\CryptoSystems\ExpElGamal\ExpEGPlaintext;
use Tests\TestCase;

/**
 * Class ExpEGHomomorphicEncryptionTest
 * @package Tests\Unit\Voting\CryptoSystems\ElGamal
 */
class ExpEGEncryptionTest extends TestCase
{

    /**
     * @test
     */
    public function regular_encryption_should_fail()
    {

        $keyPair = EGKeyPair::generate();

        $a = rand(0, 999999);
        $b = rand(0, 999999);

//        dump($a);
//        dump($b);

        /** @var EGPlaintext[] $plaintexts */
//        $plaintexts = JsonBallotEncoding::encode($a, EGPlaintext::class);
        $plaintextA = new EGPlaintext(BI($a));
        $cipherA = $keyPair->pk->encrypt($plaintextA);

        $plaintextB = new EGPlaintext(BI($b));
        $cipherB = $keyPair->pk->encrypt($plaintextB);

        $cipherC = clone $cipherA;
        $cipherC->alpha = mod($cipherC->alpha->multiply($cipherB->alpha), $keyPair->pk->parameterSet->p);
        $cipherC->beta = mod($cipherC->beta->multiply($cipherB->beta), $keyPair->pk->parameterSet->p);

        $k = $keyPair->sk->decrypt($cipherC);
//        dump($k->m->toString());

        static::assertFalse($k->m->equals(BI($a + $b)));

    }

    /**
     * @test
     */
    public function exp_should_work()
    {

        // TODO homomorphic encryption does not like subgroup mapping!!!!

        $keyPair = ExpEGKeyPair::generate();

        $a = rand(0, 999999);
        $b = rand(0, 999999);
        $_c = $a + $b;

//        dump($a);
//        dump($b);

        $_plaintext = new ExpEGPlaintext(BI($_c));
//        $_plaintext->m = $keyPair->pk->parameterSet->g->powMod($_plaintext->m, $keyPair->pk->parameterSet->p); // exp

        /** @var ExpEGPlaintext[] $plaintexts */
//        $plaintexts = JsonBallotEncoding::encode($a, ExpEGPlaintext::class);
        $plaintextA = new ExpEGPlaintext(BI($a));
//        $plaintextA->m = $keyPair->pk->parameterSet->g->powMod($plaintextA->m, $keyPair->pk->parameterSet->p); // exp

        $cipherA = $keyPair->pk->encrypt($plaintextA);

        $plaintextB = new ExpEGPlaintext(BI($b));
//        $plaintextB->m = $keyPair->pk->parameterSet->g->powMod($plaintextB->m, $keyPair->pk->parameterSet->p); // exp
        $cipherB = $keyPair->pk->encrypt($plaintextB);

        $cipherC = $cipherA->homomorphicSum($cipherB);

        $k = $keyPair->sk->decrypt($cipherC);

        $g_pow_m = $keyPair->pk->parameterSet->g->powMod($_plaintext->m, $keyPair->pk->parameterSet->p);

//        dump($k->m->toString());
//        dump($g_pow_m->toString()); // exp

        static::assertTrue($k->m->equals($g_pow_m));

//        $this->assertTrue($k->m->equals($_plaintext->m));
//        $this->assertTrue($k->m->equals(BI($a + $b))); // requires DLOG

    }

}
