<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\Tournament\PlayerController as TournamentPlayerController;
use App\Http\Controllers\Tournament\BracketController as TournamentBracketController;
use App\Http\Controllers\Bracket\PlayerController as BracketPlayerController;
use App\Http\Controllers\Tournament\MatchController as TournamentMatchController;
use App\Http\Controllers\BracketController;
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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::group(['prefix' => 'tournaments', 'controller' => TournamentController::class], function(){
    Route::get('/', 'index')->name('tournaments');
    Route::get('/{tournament}', 'show')->name('tournament');
    Route::post('/', 'create');
    Route::put('/{tournament}', 'replace');
    Route::delete('/{tournament}', 'destroy');

    Route::put('/{tournament}/start', 'start');

    Route::group(['prefix' => '{tournament}/players', 'controller' => TournamentPlayerController::class], function(){
        /* GET      /tournaments/{tournament}/players */
        Route::get('/', 'index')->name('tournaments.players');
        /* POST     /tournaments/{tournament}/players */
        Route::post('/', 'create');
    });

    Route::group(['prefix' => '{tournament}/brackets', 'controller' => TournamentBracketController::class], function(){
        /* GET      /tournaments/{tournament}/brackets */
        Route::get('/', 'index')->name('tournaments.brackets');
        /* PUT      /tournaments/{tournament}/brackets */
        Route::put('/', 'create');
    });

    Route::group(['prefix' => '{tournament}/matches', 'controller' => TournamentMatchController::class], function(){
        Route::get('{match_num}', 'show');
        Route::put('{match_num}/winner', 'createWinner');
    });
});

Route::group(['prefix' => 'brackets', 'controller' => BracketController::class], function(){
    Route::group(['prefix' => '{bracket}/player', 'controller' => BracketPlayerController::class], function(){
        Route::put('/', 'edit');
    });
});

Route::group(['prefix' => 'players'], function(){
    Route::get('/{player}', [PlayerController::class, 'show'])->name('player');
    Route::put('/{player}', [PlayerController::class, 'replace']);
    Route::delete('/{player}', [PlayerController::class, 'destroy']);
});

Route::any('{any}', function(){
    return response()->json(['status' => 'Not Found'], 404);
})->name('not_found');