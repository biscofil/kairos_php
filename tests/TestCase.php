<?php

namespace Tests;

use App\Voting\CryptoSystems\ElGamal\EGPlaintext;
use App\Voting\CryptoSystems\ElGamal\EGSecretKey;
use App\Voting\CryptoSystems\ElGamal\EGPublicKey;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseTransactions;

    /**
     * @param int $expectedCode
     * @param TestResponse|JsonResponse $response
     */
    public function assertResponseStatusCode(int $expectedCode, $response): void
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
        static::assertEquals($expectedCode, $response->getStatusCode());
    }

    /**
     * @param \App\Voting\CryptoSystems\ElGamal\EGPublicKey $pk
     * @param \App\Voting\CryptoSystems\ElGamal\EGSecretKey $sk
     */
    public function assertValidEGKeyPair(EGPublicKey $pk, EGSecretKey $sk)
    {
        $p = new EGPlaintext(randomBIgt($pk->parameterSet->q));
        $c = $pk->encrypt($p);
        $p2 = $sk->decrypt($c);
        static::assertTrue($p->equals($p2));
    }

}
