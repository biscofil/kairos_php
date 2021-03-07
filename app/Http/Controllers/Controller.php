<?php

namespace App\Http\Controllers;

use App\Models\Election;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
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

}
