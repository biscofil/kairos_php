<?php


namespace Tests\Unit;

use App\Models\PeerServer;
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

        $signer = new Sha256();
        $privateKey = new Key(getCurrentServer()->jwt_secret_key->toArray()['v']);

        $claimName = Str::random(10);
        $claimValue = Str::random(10);

        $token = (new Builder())->withClaim($claimName, $claimValue)
            ->getToken($signer, $privateKey);

        $tokenStrSent = strval($token);

        // ############################

        $tokenStrReceived = $tokenStrSent;

        $token = (new Parser())->parse($tokenStrReceived);

        $publicKey = new Key(getCurrentServer()->jwt_public_key->toArray()['v']);

        static::assertTrue($token->verify($signer, $publicKey));

        $claims = $token->getClaims();
        static::assertArrayHasKey($claimName, $claims);
        static::assertEquals($claimValue, $claims[$claimName]);

    }

}
