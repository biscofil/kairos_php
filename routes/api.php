<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CastVoteController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\TrusteeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::middleware('auth:api')->get('auth/profile', function (Request $request) {
//    return $request->user();
//});

Route::get('/', [Controller::class, 'home']);

Route::get('auth/profile', [AuthController::class, 'check']);
Route::post('auth/after/{provider}', [AuthController::class, 'providerLogin']);

// auth middleware
Route::middleware('auth:api')->group(function () {

    Route::post('/elections', [ElectionController::class, 'store']);
    Route::post('/elections/{election}/copy', [ElectionController::class, 'copy']);

    // these routes are only accessible by the creator of the election
    Route::middleware(['election_admin'])->group(function () {

        Route::post('/elections/{election}/archive', [ElectionController::class, 'archive']);
        Route::post('/elections/{election}/feature', [ElectionController::class, 'feature']);

        Route::middleware(['not_frozen'])->group(function () {

            Route::put('/elections/{election}', [ElectionController::class, 'update']);
            Route::put('/elections/{election}/questions', [ElectionController::class, 'questions']);

            Route::middleware(['no_issues'])->group(function () {
                Route::post('/elections/{election}/freeze', [ElectionController::class, 'freeze']);
            });

            Route::delete('/elections/{election}/trustees/{trustee}', [TrusteeController::class, 'destroy']);
            Route::post('/elections/{election}/trustees', [TrusteeController::class, 'store']);
            Route::post('/elections/{election}/trustees/add-helios', [TrusteeController::class, 'store_system_trustee']);

        });
    });

    Route::middleware(['election_trustee'])->group(function () {

        Route::get('/elections/{election}/trustee/home', [TrusteeController::class, 'trustee_home']);

        Route::middleware(['not_frozen'])->group(function () {
            Route::post('/elections/{election}/trustee/upload-pk', [TrusteeController::class, 'upload_public_key']);
        });
    });

    Route::middleware(['frozen', 'can_vote'])->group(function () {
        Route::post('/elections/{election}/cast', [CastVoteController::class, 'store']);
    });
});

Route::get('/elections', [ElectionController::class, 'index']);
Route::get('/elections/{election}', [ElectionController::class, 'show']);
Route::get('/elections/{election}/trustees', [TrusteeController::class, 'index']);

// #######################################
// ########################### CATEGORIES
// #######################################

Route::get('/categories', [CategoriesController::class, 'index']);
Route::middleware(['auth:api', 'admin'])->group(function () {
    Route::post('/categories', [CategoriesController::class, 'store']);
    Route::delete('/categories/{category}', [CategoriesController::class, 'destroy']);
});
