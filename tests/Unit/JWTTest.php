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
        $privateKey = new Key(PeerServer::me()->jwt_secret_key->toArray()['v']);

        $claimName = Str::random(10);
        $claimValue = Str::random(10);

        $token = (new Builder())->withClaim($claimName, $claimValue)
            ->getToken($signer, $privateKey);

        $tokenStrSent = strval($token);

        // ############################

        $tokenStrReceived = $tokenStrSent;

        $token = (new Parser())->parse($tokenStrReceived);

        $publicKey = new Key(PeerServer::me()->jwt_public_key->toArray()['v']);

        $this->assertTrue($token->verify($signer, $publicKey));

        $claims = $token->getClaims();
        $this->assertArrayHasKey($claimName, $claims);
        $this->assertEquals($claimValue, $claims[$claimName]);

    }

}
