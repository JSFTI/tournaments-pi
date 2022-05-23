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
     * Get Match by Match Number
     * 
     * @urlParam match_num int required
     * @responseField id int Id of match bracket.
     * @responseField created_at string Date bracket created.
     * @responseField updated_at string Date bracket created.
     * @responseField match int If a bracket has previous match, it will be filled by match number,
     * @responseField player object Player in this bracket. If **null**, it means that the bracket has no player yet.
     * @responseField _url string URL to bracket.
     * @responseField prev_match object Previous match. Seperated to left and right side. If **null**, it means that the bracket has no previous match.
     * @responseField prev_match.left object Prevous match on the left side.
     * @responseField prev_match.right object Prevous match on the right side.
     * 
     * @responseFile 200 scenario="Success" responses/matches/get_match.json
     * @responseFile 404 scenario="Not Found" responses/errors/model.not_found.json
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
     * 
     * @response 200 scenario="Success" {"message": "Winner declared"}
     * @responseFile 404 scenario="Not Found" responses/errors/model.not_found.json
     * @response 400 scenario="Tournament has not started" {"message": "Tournament has not started"}
     * @response 400 scenario="Player not candidate" {"message": "Player is not winner candidate"}
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

        if(!($match->children[0]->player_id !== $request->player_id || $match->children[1]->player_id !== $request->player_id)){
            return response()->json(['message' => 'Player is not winner candidate'], 400);
        }

        $match->player_id = $request->player_id;
        $match->save();

        return response()->json(['message' => 'Winner declared'], 200);
    }
}
