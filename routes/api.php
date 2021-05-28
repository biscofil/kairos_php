<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CastVoteController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\P2PController;
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

Route::get('/p2p', [P2PController::class, 'list_peers']);
Route::middleware(['admin'])->group(function () {
    // these routes are only accessible by an admin
    Route::post('/p2p/new_peer', [P2PController::class, 'add_peer']);
});
Route::post('/p2p/{message}', [P2PController::class, 'receive']);

Route::get('/', [Controller::class, 'home']);

Route::get('/settings_auth', [Controller::class, 'settings_auth']);
Route::get('/auth/after/{provider}', [AuthController::class, 'providerLoginOK']);
Route::post('/auth/after/{provider}', [AuthController::class, 'providerLogin']);

Route::get('/election_editor', [ElectionController::class, 'get_editor_parameters']);

// auth middleware
Route::middleware('auth:user_api')->group(function () {

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
            Route::put('/elections/{election}/trustees/threshold', [TrusteeController::class, 'threshold']);
            Route::post('/elections/{election}/trustees', [TrusteeController::class, 'store']);

        });
    });

    Route::middleware(['election_trustee'])->group(function () {

        Route::get('/elections/{election}/trustee/home', [TrusteeController::class, 'trustee_home']);

        Route::middleware(['not_frozen'])->group(function () {
            Route::post('/elections/{election}/trustee/upload-pk', [TrusteeController::class, 'upload_public_key']);
        });
    });

});

Route::middleware(['frozen', 'authenticate_with_election_creator_jwt'])->group(function () { // , 'can_vote'
    Route::post('/elections/{election}/cast', [CastVoteController::class, 'store']);
});

Route::get('/elections', [ElectionController::class, 'index']);
Route::get('/elections/{election}', [ElectionController::class, 'show']);
Route::get('/elections/{election}/trustees', [TrusteeController::class, 'index']);

Route::get('/elections/{election}/votes', [CastVoteController::class, 'index']);

//Route::fallback(function () {
//    return response()->json([
//        "error" => "not found"
//    ], 404);
//});
