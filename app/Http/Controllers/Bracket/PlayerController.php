<?php

namespace App\Http\Controllers\Bracket;

use App\Http\Controllers\Controller;
use App\Http\Requests\BracketPlayerRequest;
use App\Models\Bracket;
use App\Models\Player;
use Illuminate\Http\Request;

/**
 * @group Manage Brackets
 * 
 * API end-poitns to manage tournament brackets.
 */
class PlayerController extends Controller
{
    /**
     * Replace player
     * 
     * Replace current player with player from another bracket.
     * 
     * <aside class="info">Players from both bracket will be swapped</aside>
     * 
     * @bodyParam player_id int Target player.
     */
    public function edit(BracketPlayerRequest $request, $bracket_id){
        $bracket = Bracket::with('children')->find($bracket_id);
        
        if(!$bracket){
            return response()->json(['message' => 'Bracket not found'], 404);
        }

        if($bracket->children->count() > 0){
            return response()->json(['message' => 'Only the first brackets are switchable'], 403);
        }

        $targetBracket = Bracket::where('player_id', $request->player_id)->first();

        $targetBracket->player_id = $bracket->player_id;
        $targetBracket->save();

        $bracket->player_id = $request->player_id;
        $bracket->save();

        return response()->json(['message' => 'Switched players from both brackets'], 200);
    }
}
