<?php

namespace App\Http\Controllers\Tournament;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlayerRequest;
use App\Models\Player;
use App\Models\Tournament;
use Illuminate\Http\Request;

/**
 * @group Tournament Management
 */
class PlayerController extends Controller
{
    /**
     * Get players of a tournament
     * 
     * Players list is ordered in descending order by creation time.
     * 
     * @queryParam page int  Defaults to ```1```. Values less than 1 will default to 1. Example: 1
     * @queryParam name string Search for player that contains ```name``` keyword. No-example
     * @queryParam limit int Defaults to ```10```. Values less than 1 will default to 1. Example: 10
     * 
     * @responseField count int
     * @responseField total_pages int
     * @responseField next_page string Returns ```null``` if last page is reached.
     * @responseField prev_page string Returns ```null``` if it is the first page.
     * 
     * @responseField results object[] List of players in tournament.
     * @responseField results.id integer Player ID.
     * @responseField results.name string Player name.
     * @responseField results._url string URL to player resource.
     * @responseField results.tournament_url string URL to a tournament which the player belongs to.
     * 
     * @responseFile 200 scenario="Success" responses/players/get_players.json
     * @responseFile 404 scenario="Not Found" responses/errors/model.not_found.json
     */
    public function index(Request $request, int $tournament_id){
        $tournament = Tournament::find($tournament_id);

        if(!$tournament){
            return response()->json(['message' => 'Tournament not found'], 404);
        }

        $page = 1;
        if($request->page && $request->page >= 1){
            $page = $request->page;
        }
        $limit = 10;
        $offset = ($page - 1) * $limit;
        if($request->limit && $request->limit > 1){
            $limit = $request->limit;
        }

        $sql = Player::where('tournament_id', $tournament_id)->orderBy('created_at', 'DESC');
        if($request->name){
            $sql->where('name', 'LIKE', "%$request->name%");
        }

        $results = $sql->limit($limit)->offset($offset)->get();
        $count = $sql->count('id');
        $totalPages = ceil($count / $limit);
        $nextPage = $page > $totalPages ? null : route('tournaments.players', ['page' => $page + 1, 'tournament' => $tournament_id], false);
        $prevPage = $page === 1 ? null : route('tournaments.players', ['page' => $page - 1, 'tournament' => $tournament_id], false);

        return response()->json([
            'count' => $count,
            'total_pages' => $totalPages,
            'next_page' => $nextPage,
            'prev_page' => $prevPage,
            'results' => $results
        ]);   
    }

    /**
     * Create a player in a tournament
     * 
     * @responseFile 201 scenario="Created" responses/players/get_player.json
     * @responseFile 404 scenario="Not Found" responses/errors/model.not_found.json
     * @responseFile 422 scenario="Invalid Request Body" responses/players/post_player.error.json
     */
    public function create(PlayerRequest $request, int $tournament_id){
        $tournament = Tournament::find($tournament_id);

        if(!$tournament){
            return response()->json(['message' => 'Tournament not found'], 404);
        }

        if($tournament->started){
            return response()->json(['message' => 'Tournament has started'], 400);
        }

        $player = new Player();

        $player->name = $request->name;
        $player->tournament_id = $tournament_id;

        $player->save();

        $player->refresh();

        return response()->json($player, 201);
    }
}
