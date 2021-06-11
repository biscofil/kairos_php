<?php


namespace Tests\Http\Controllers;

use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Class SPAController
 * @package App\Http\Controllers
 */
class SPAControllerTest extends TestCase
{

    /**
     * @test
     */
    public function home()
    {
        $response = $this->get('/');
        self::assertResponseStatusCode(200, $response);
    }


    /**
     * @test
     */
    public function home_404()
    {
        $response = $this->get('/' . Str::random(10));
        self::assertResponseStatusCode(404, $response);
    }

}
