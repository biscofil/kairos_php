<?php


namespace App\Http\AuthProviders;


use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User;

/**
 * Class AuthProvider
 * @package App\Http\AuthProviders
 * @property string $providerName
 */
class AuthProvider
{

    protected function getProviderName(): string
    {
        return '';
    }


    /**
     * @param string $authCode
     * @return string
     */
    protected function getAccessTokenFromAuthCode(string $authCode): string
    {
        // default: do nothing
        return $authCode;
    }

    /**
     * @param string $token
     */
    public function getUserData(string $token): User
    {

        $access_token = $this->getAccessTokenFromAuthCode($token);

        //$access_token = Socialite::driver(self::providerName)->getAccessTokenResponse($token);
        //$access_token['access_token']

        return Socialite::driver($this->getProviderName())->stateless()->userFromToken($access_token);
    }

}
