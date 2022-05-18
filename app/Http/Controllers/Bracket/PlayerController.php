<?php

namespace App\Http\Controllers\Bracket;

use App\Http\Controllers\Controller;
use App\Http\Requests\BracketPlayerRequest;
use App\Models\Bracket;
use App\Models\Player;
use App\Models\Tournament;
use Illuminate\Http\Request;

/**
 * @group Manage Brackets
 * 
 * API end-poitns to manage tournament brackets.
 */
class PlayerController extends Controller
{
    /**
     * Upsert Player in Bracket
     * 
     * Insert a player to a bracket or replace a player in a bracket. Upsert can only be done if tournament has not started yet.
     * 
     * <aside class="info">If upserted player is already in the tournament brackets and the bracket is already assigned to a player, then both players' position in the bracket will be swapped.</aside>
     * 
     * @bodyParam player_id int Target player.
     */
    public function edit(BracketPlayerRequest $request, $bracket_id){
        $bracket = Bracket::with('children')->find($bracket_id);
        
        if(!$bracket){
            return response()->json(['message' => 'Bracket not found'], 404);
        }

        if($bracket->match !== null){
            return response()->json(['message' => 'Only the first brackets are upsert-able'], 400);
        }

        $tournament = Tournament::find($bracket->tournament_id);

        if($tournament->started){
            return response()->json(['message' => 'Tournament has started'], 400);
        }

        $targetBracket = Bracket::where('player_id', $request->player_id)->first();

        if($bracket->player_id){
            $targetBracket->player_id = $bracket->player_id;
            $targetBracket->save();
        } else if(!$bracket->player_id && $targetBracket->player_id) {
            $targetBracket->player_id = null;
            $targetBracket->save();
        }

        $bracket->player_id = $request->player_id;
        $bracket->save();

        return response()->json(['message' => 'Player upserted'], 200);
    }
}
