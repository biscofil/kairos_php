<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseTransactions;

    /**
     * @param int $expectedCode
     * @param TestResponse $response
     */
    public function assertResponseStatusCode(int $expectedCode, TestResponse $response): void
    {
        if (env('TESTING_DUMP_RESPONSE', false)) {
            if ($response->getStatusCode() !== $expectedCode) {
                try {
                    dump($response->json());
                } catch (\Exception $e) {
                    dump($response->content());
                }
            }
        }
        $this->assertEquals($expectedCode, $response->getStatusCode());
    }

    /**
     * @param \App\Voting\CryptoSystems\ElGamal\EGPublicKey $pk
     * @param \App\Voting\CryptoSystems\ElGamal\EGPrivateKey $sk
     */
    public function assertValidEGKeyPair(EGPublicKey $pk, EGPrivateKey $sk)
    {
        $p = new EGPlaintext(BigInteger::randomRange(BI1(), $pk->parameterSet->q));
        $c = $pk->encrypt($p);
        $p2 = $sk->decrypt($c);
        $this->assertTrue($p->equals($p2));
    }


}
