<?php

use App\Models\User;
use phpseclib3\Math\BigInteger;

/**
 * @return bool
 */
function isLogged(): bool
{
    return auth('api')->check();
}

/**
 * @return User
 * @noinspection PhpIncompatibleReturnTypeInspection
 */
function getAuthUser(): User
{
    return auth('api')->user();
}

/**
 * Returns 1 as Big Integer
 * @return BigInteger
 */
function BI1(): BigInteger
{
    return new BigInteger(1);
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

    throw new \Exception("Can't extract domain from URL : $url");
}
