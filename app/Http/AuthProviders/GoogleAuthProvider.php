<?php


namespace App\Http\AuthProviders;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class GoogleAuthProvider extends AuthProvider
{

    /**
     * Get google id token given authCode
     * @param string $authCode
     * @return string
     * @throws GuzzleException
     */
    protected function getAccessTokenFromAuthCode(string $authCode): string
    {
        $client = new Client();

        $response = $client->request('POST', 'https://www.googleapis.com/oauth2/v4/token', [
            'form_params' => [
                'code' => $authCode,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => url('api/auth/after/google'),
                'grant_type' => 'authorization_code'
            ]
        ]);
        $response = json_decode($response->getBody()->getContents());
        return $response->access_token;

    }

    protected function getProviderName(): string
    {
        return 'google';
    }
}
