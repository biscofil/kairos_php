<?php

namespace App\Http\Controllers;

use App\Models\Election;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

/**
 * Class Controller
 * @package App\Http\Controllers
 */
class Controller extends BaseController
{

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @return array
     */
    public function home()
    {

        $featured_elections = Election::featured()->limit(10)
            ->select(['id', 'name', 'slug', 'admin_id'])
            ->get();

        $elections_administered = [];
        $elections_voted = [];

        if (auth('api')->check()) {
            $elections_administered = getAuthUser()->administeredElections()
                ->limit(10)
                ->select(['id', 'name', 'slug', 'admin_id'])
                ->get();
        }

        return [
            'elections' => $featured_elections,
            'elections_administered' => $elections_administered,
            'elections_voted' => $elections_voted,
        ];
    }

    /**
     * Returns settings and user auth
     */
    public function settings_auth(): JsonResponse
    {
        $auth_providers = null;
        $user = null;

        if (auth('api')->check()) {
            $user = auth('api')->user();
        } else {
            $auth_providers = [
                'enabled_auth_systems' => [
                    [
                        'name' => 'google',
                        'clientId' => config('services.google.client_id')
                    ],
                    [
                        'name' => 'facebook',
                        'clientId' => config('services.facebook.client_id')
                    ],
                ],
            ];
        }

        return response()->json([
            'settings' => [
                'SITE_TITLE' => 'Kairos',
                'FOOTER_LOGO_URL' => asset('favicon.ico'),
                'MAIN_LOGO_URL' => asset('favicon.ico'),
                'SHOW_USER_INFO' => true,
                'WELCOME_MESSAGE' => 'welcome',
                'SHOW_LOGIN_OPTIONS' => true, // TODO
                'HELP_EMAIL_ADDRESS' => 'info@example.com'
            ],
            'login_box' => $auth_providers,
            'user' => $user,
        ]);

    }

}
