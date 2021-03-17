<?php


namespace App\Http\Controllers;


use App\Http\AuthProviders\GoogleAuthProvider;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class AuthController
 * @package App\Http\Controllers
 */
class AuthController extends Controller
{

    /**
     * @param string $provider
     * @return string
     */
    public function providerLoginOK(string $provider): string
    {
        return "OK ($provider), modal should close";
    }

    /**
     * @param string $provider
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function providerLogin(string $provider, Request $request): JsonResponse
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
                $token = auth('api')->login($user);

                return response()->json([
                    "user" => $user,
                    "access_token" => $token,
                    'expires_in' => auth('api')->factory()->getTTL() * 60
                ]);

            default:
                return response()->json([], 400);
        }

    }


}
