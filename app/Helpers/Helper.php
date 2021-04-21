<?php

use App\Events\WebsocketLog;
use App\Models\PeerServer;
use App\Models\User;
use App\Voting\CryptoSystems\RSA\RSAKeyPair;
use phpseclib3\Math\BigInteger;

/**
 * @return bool
 */
function isLogged(): bool
{
    return auth('user_api')->check();
}

/**
 * @return User
 * @noinspection PhpIncompatibleReturnTypeInspection
 */
function getAuthUser(): User
{
    return auth('user_api')->user();
}

/**
 *
 * Returns a new Big Integer
 * @param int|string $i
 * @param int $base
 * @return BigInteger
 */
function BI($i, int $base = 10): BigInteger
{
    return new BigInteger($i, $base);
}

/**
 * Returns 1 as Big Integer
 * @return BigInteger
 */
function BI1(): BigInteger
{
    return BI(1);
}

/**
 * Returns a random big integer in the range [ 1 , $gt - 1 ]
 * @param BigInteger $gt
 * @return BigInteger
 */
function randomBIgt(BigInteger $gt): BigInteger
{
    return BigInteger::randomRange(BI1(), $gt->subtract(BI1()));
}

/**
 * TODO block IPs
 * @param string $url
 * @return string
 * @throws Exception
 */
function extractDomain(string $url): string
{

    $domain = parse_url($url, PHP_URL_HOST);
    if (!is_null($domain)) {
        return $domain;
    }

    $domain = filter_var($url, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
    if ($domain !== false) {
        return $domain;
    }

    throw new Exception("Can't extract domain from URL : $url");
}

/**
 * @param string $ip
 * @param int $port
 * @return bool
 */
function ping(string $ip, int $port = 80): bool
{
    $url = $ip . ':' . $port;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    $health = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    dump($health);
    return boolval($health);
}

/**
 * @return RSAKeyPair
 */
function getJwtRSAKeyPair(): RSAKeyPair
{
    return RSAKeyPair::fromPemFiles(
        config('jwt.keys.private'),
        config('jwt.keys.public')
    );
}

/**
 * Returns the Lagrangian coefficient with modulo
 * @param int[] $I
 * @param int $j
 * @param \phpseclib3\Math\BigInteger $mod
 * @return BigInteger
 */
function getLagrangianCoefficientMod(array $I, int $j, BigInteger $mod): BigInteger
{
    $out = BI(1);
    foreach ($I as $k) {
        if ($j === $k) {
            continue;
        }
        $out = $out->multiply(
            BI($k)->multiply(
                BI($k - $j)->modInverse($mod)
            )
        );
    }
    return $out;
}

/**
 * Log
 * @param string $msg
 */
function websocketLog(string $msg) : void
{
    WebsocketLog::dispatch(PeerServer::me()->domain . ' > ' . $msg);
}
