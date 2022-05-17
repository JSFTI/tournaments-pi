<?php

use App\Http\Controllers\PlayerController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\Tournament\PlayerController as TournamentPlayerController;
use Illuminate\Http\Request;
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

Route::group(['prefix' => 'tournaments', 'controller' => TournamentController::class], function(){
    Route::get('/', 'index')->name('tournaments');
    Route::get('/{id}', 'show')->name('tournament');
    Route::post('/', 'create');
    Route::put('/{id}', 'replace');
    Route::delete('/{id}', 'destroy');

    Route::group(['prefix' => '{tournament}/players', 'controller' => TournamentPlayerController::class], function(){
        Route::get('/', 'index')->name('tournaments.players');
        Route::post('/', 'create');
    });
});

Route::group(['prefix' => 'players'], function(){
    Route::get('/{id}', [PlayerController::class, 'show'])->name('player');
    Route::put('/{id}', [PlayerController::class, 'replace']);
    Route::delete('/{id}', [PlayerController::class, 'destroy']);
});

Route::any('{any}', function(){
    return response()->json(['status' => 'Not Found'], 404);
})->name('not_found');