<?php

use App\Http\Controllers\SPAController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [SPAController::class, 'home']);
Route::get('/voted-elections', [SPAController::class, 'home']);
Route::get('/administered-elections', [SPAController::class, 'home']);
Route::get('/admin/network', [SPAController::class, 'home']);
Route::get('/admin/elections', [SPAController::class, 'home']);
Route::get('/admin/recent-votes', [SPAController::class, 'home']);
Route::get('/admin/problem-elections', [SPAController::class, 'home']);
Route::get('/admin', [SPAController::class, 'home']);
Route::get('/new-election', [SPAController::class, 'home']);
Route::get('/elections/{slug}/vote', [SPAController::class, 'home']);
Route::get('/elections/{slug}/votes', [SPAController::class, 'home']);
Route::get('/elections/{slug}/verifier', [SPAController::class, 'home']);
Route::get('/elections/{slug}/edit', [SPAController::class, 'home']);
Route::get('/elections/{slug}/extend', [SPAController::class, 'home']);
Route::get('/elections/{slug}/questions', [SPAController::class, 'home']);
Route::get('/elections/{slug}/voters/email', [SPAController::class, 'home']);
Route::get('/elections/{slug}/voters', [SPAController::class, 'home']);
Route::get('/elections/{slug}/trustee', [SPAController::class, 'home']);
Route::get('/elections/{slug}/trustees', [SPAController::class, 'home']);
Route::get('/elections/{slug}/audited-ballots', [SPAController::class, 'home']);
Route::get('/elections/{slug}/proofs', [SPAController::class, 'home']);
Route::get('/elections/{slug}', [SPAController::class, 'home']);
Route::get('/', [SPAController::class, 'home']);

Route::get('/{all}', [SPAController::class, 'home_404'])->where('all', '^(?!api).*$');
