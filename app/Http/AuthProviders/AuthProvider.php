<?php


namespace App\Http\AuthProviders;


use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User;

/**
 * Class AuthProvider
 * @package App\Http\AuthProviders
 * @property string $providerName
 */
abstract class AuthProvider
{

    /**
     * @return string
     */
    abstract protected function getProviderName(): string;

    /**
     * @param string $authCode
     * @return string
     */
    abstract protected function getAccessTokenFromAuthCode(string $authCode): string;

    /**
     * @param string $token
     * @return \Laravel\Socialite\Two\User
     */
    public function getUserData(string $token): User
    {

        $access_token = $this->getAccessTokenFromAuthCode($token);

        //$access_token = Socialite::driver(self::providerName)->getAccessTokenResponse($token);
        //$access_token['access_token']

        return Socialite::driver($this->getProviderName())->stateless()->userFromToken($access_token);
    }

}
