<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\TournamentController;
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

Route::group(['prefix' => 'tournaments'], function(){
    Route::get('/', TournamentController::class)->name('tournaments');
    Route::get('/{id}', [TournamentController::class, 'show'])->name('tournament');
    Route::post('/', [TournamentController::class, 'create']);
    Route::put('/{id}', [TournamentController::class, 'update']);
    Route::delete('/{id}', [TournamentController::class, 'destroy']);
});

Route::any('{any}', function(){
    return response()->json(['status' => 'Not Found'], 404);
})->name('not_found');