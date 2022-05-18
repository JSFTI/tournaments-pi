<?php

namespace App\Http\Controllers\Tournament;

use App\Http\Controllers\Controller;
use App\Http\Requests\WinnerRequest;
use App\Models\Bracket;
use App\Models\Tournament;
use Illuminate\Http\Request;

/**
 * @group Match Management
 * 
 * APIS for managing tournament matches.
 * 
 */
class MatchController extends Controller
{
    /**
     * Get Match
     * 
     */
    public function show(int $tournament_id, int $match_num){
        $tournament = Tournament::find($tournament_id);
        if(!$tournament){
            return response()->json(['message' => 'Tournament not found'], 404);
        }
        
        $match = Bracket::where('tournament_id', $tournament_id)->where('match', $match_num)
            ->with(['children.player', 'player'])->first();

        if(!$match){
            return response()->json(['message' => 'Match not found'], 404);
        }

        unset($match['_lft']);
        unset($match['_rgt']);
        unset($match['parent_id']);

        for($i = 0; $i < count($match['children']); $i++){
            unset($match['children'][$i]['_lft']);
            unset($match['children'][$i]['_rgt']);
            unset($match['children'][$i]['parent_id']);
        }

        $match['prev_match'] = [
            'left' => $match['children'][0],
            'right' => $match['children'][1]
        ];
        unset($match['children']);

        return response()->json($match);
    }

    /**
     * Declare Match Winner
     * 
     * Assigns a winner to a match
     * 
     * @urlParam match_num Match number of a tournament.
     */
    public function createWinner(WinnerRequest $request, int $tournament_id, int $match_num){
        $tournament = Tournament::find($tournament_id);
        if(!$tournament){
            return response()->json(['message' => 'Tournament not found'], 404);
        }

        if(!$tournament->started){
            return response()->json(['message' => 'Tournament has not started'], 400);
        }

        $match = Bracket::where('tournament_id', $tournament_id)->where('match', $match_num)->first();

        if(!$match){
            return response()->json(['message' => 'Match not found'], 404);
        }
        $match->player_id = $request->player_id;
        $match->save();

        return response()->json(['message' => 'Winner declared.'], 200);
    }
}
