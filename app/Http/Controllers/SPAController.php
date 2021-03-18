<?php


namespace App\Http\Controllers;


use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

/**
 * Class SPAController
 * @package App\Http\Controllers
 */
class SPAController extends Controller
{


    /**
     * @return array|Application|Factory|View
     */
    public function home()
    {
        return view('spa');
    }


    /**
     * @return Response
     */
    public function home_404(): Response
    {
        return response(view('spa'), 404);
    }

}
