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

    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

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

        if (auth('user_api')->check()) {
            $elections_administered = getAuthUser()->administeredElections()
                ->limit(10)
                ->orderByDesc('id')
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

        if (auth('user_api')->check()) {
            $user = auth('user_api')->user();
        } else {
            $auth_providers = [
                'enabled_auth_systems' => [
                    [
                        'name' => 'google',
                        'clientId' => config('services.google.client_id')
                    ],
//                    [
//                        'name' => 'facebook',
//                        'clientId' => config('services.facebook.client_id')
//                    ],
                ],
            ];
        }

        $meServer = getCurrentServer();

        return response()->json([
            'settings' => [
                'SITE_TITLE' => $meServer->site_title,
                'FOOTER_LOGO_URL' => $meServer->footer_logo_url,
                'MAIN_LOGO_URL' => $meServer->main_logo_url,
                'SHOW_USER_INFO' => $meServer->show_user_info,
                'WELCOME_MESSAGE' => $meServer->welcome_message,
                'SHOW_LOGIN_OPTIONS' => $meServer->show_login_options,
                'HELP_EMAIL_ADDRESS' => $meServer->help_email_address,
            ],
            'peer' => $meServer,
            'login_box' => $auth_providers,
            'user' => $user,
        ]);

    }

}
