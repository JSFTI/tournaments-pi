<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlayerRequest;
use App\Models\Bracket;
use App\Models\Player;
use Illuminate\Http\Request;

/**
 * @group Player Management
 * 
 * APIS for managing players
 */
class PlayerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => 'show']);
    }

    /**
     * Get a player
     * 
     * @urlParam player int required Player ID
     * 
     * @responseField id integer Tournament ID.
     * @responseField name string Tournament name.
     * @responseField _url string URL to player resource.
     * @responseField tournament_url string URL to a tournament which the player belongs to.
     * @responseField created_at string Tournament creation timestamp.
     * @responseField updated_at string Last update timestamp.
     * 
     * @responseFile 200 scenario="Success" responses/players/get_player.json
     * @responseFile 404 scenario="Not Found" responses/errors/model.not_found.json
     */
    public function show(int $id){
        $player = Player::find($id);

        if(!$player){
            return response()->json(['message' => 'Player not found'], 404);
        }

        return response()->json($player);
    }

    /**
     * Update a player
     * 
     * @urlParam player int required Player ID
     * 
     * @authenticated
     * @responseFile 200 scenario="Success" responses/players/get_player.json
     * @responseFile 404 scenario="Not Found" responses/errors/model.not_found.json
     * @responseFile 422 scenario="Invalid Request Body" responses/players/post_player.error.json
     */
    public function replace(PlayerRequest $request, int $id){
        $player = Player::find($id);

        if(!$player){
            return response()->json(['message' => 'Player not found'], 404);
        }

        $player->name = $request->name;
        $player->save();

        return response()->json($player);
    }
    
    /**
     * Delete a player
     * 
     * @urlParam player int required Player ID
     * 
     * @authenticated
     * @response 200 scenario="Success" {"status": "Success"}
     * @responseFile 404 scenario="Not Found" responses/errors/model.not_found.json
     */
    public function destroy(int $id){
        $player = Player::find($id);

        if(!$player){
            return response()->json(['message' => 'Player not found'], 404);
        }

        Bracket::where('player_id', $id)->update([
            'player_id' => null
        ]);        

        $player->delete();

        return response()->json(['status' => 'Success']);
    }
}
