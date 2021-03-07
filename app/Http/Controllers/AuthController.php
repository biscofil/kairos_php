<?php


namespace App\Http\Controllers;


use App\Http\AuthProviders\GoogleAuthProvider;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

/**
 * Class AuthController
 * @package App\Http\Controllers
 */
class AuthController extends Controller
{

    /**
     * @return \array[][]|Authenticatable|null
     */
    public function check()
    {

        $auth_providers = null;
        $user = null;
        $newToken = null;

        if (auth('api')->check()) {
            $user = auth('api')->user();
            $newToken = auth()->refresh();
        } else {
            $auth_providers = [
                'enabled_auth_systems' => [
                    [
                        "name" => "google",
                        "clientId" => config('services.google.client_id')
                    ],
                    [
                        "name" => "facebook",
                        "clientId" => config('services.facebook.client_id')
                    ],
                ],
            ];
        }

        return [
            'login_box' => $auth_providers,
            'user' => $user,
            "access_token" => $newToken,
            'expires_in' => auth()->factory()->getTTL() * 60
        ];

    }

    /**
     * @param string $provider
     * @param Request $request
     * @return array
     */
    public function providerLogin(string $provider, Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string']
        ]);

        $token = $data['code'];

        switch ($provider) {
            case "google":
                $u = (new GoogleAuthProvider())->getUserData($token);
                $user = User::findByProvider('google', $u->id, $u->email);
                if (is_null($user)) {
                    $user = User::create([
                        'provider' => 'google',
                        'provider_id' => $u->id,
                        'email' => $u->email,
                        'name' => $u->name
                    ]);
                }

                // Get the token
                $token = auth()->login($user);

                return [
                    "user" => $user,
                    "access_token" => $token,
                    'expires_in' => auth()->factory()->getTTL() * 60
                ];

            default:
                return response()->json([], 400);
        }

    }


}
