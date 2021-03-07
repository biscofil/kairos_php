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
 * @return BigInteger
 */
function BI1() : BigInteger
{
    return new BigInteger(1);
}
