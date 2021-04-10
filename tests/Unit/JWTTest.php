<?php


namespace Tests\Unit;

use Illuminate\Support\Str;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Tests\TestCase;

class JWTTest extends TestCase
{

    /**
     * @test
     */
    public function enc_dec()
    {

        $rsaKeyPair = getJwtRSAKeyPair();

        $signer = new Sha256();
        $privateKey = new Key($rsaKeyPair->sk->toArray()['v']);

        $claimName = Str::random(10);
        $claimValue = Str::random(10);

        $token = (new Builder())->withClaim($claimName, $claimValue)
            ->getToken($signer, $privateKey);

        $tokenStrSent = strval($token);

        // ############################

        $tokenStrReceived = $tokenStrSent;

        $token = (new Parser())->parse($tokenStrReceived);

        $publicKey = new Key($rsaKeyPair->pk->toArray()['v']);

        $this->assertTrue($token->verify($signer, $publicKey));

        $claims = $token->getClaims();
        $this->assertArrayHasKey($claimName, $claims);
        $this->assertEquals($claimValue, $claims[$claimName]);

    }

}
