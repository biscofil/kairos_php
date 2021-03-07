<?php


namespace App\Http\Controllers;


/**
 * Class SPAController
 * @package App\Http\Controllers
 */
class SPAController extends Controller
{


    public function home()
    {
        return view('spa');
    }

}
