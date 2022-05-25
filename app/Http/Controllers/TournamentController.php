<?php

namespace App\Http\Controllers;

use App\Http\Requests\TournamentRequest;
use App\Http\Requests\WinnerRequest;
use App\Models\Bracket;
use App\Models\Tournament;
use Illuminate\Http\Request;

/**
 * @group Tournament Management
 * 
 * APIS for managing tournaments
 */
class TournamentController extends Controller
{
    public function __construct(){
        $this->middleware('auth:api', ['except' => ['index', 'show']]);
    }

    /**
     * Get tournaments
     * 
     * Tournament list is ordered in descending order by creation time.
     * 
     * @queryParam page int  Defaults to ```1```. Values less than 1 will default to 1. Example: 1
     * @queryParam name string Search for tournament that contains ```name``` keyword. No-example
     * @queryParam limit int Defaults to ```10```. Values less than 1 will default to 1. Example: 10
     * @queryParam user int Get tournaments owned by specified user with id.
     * 
     * @responseField count int
     * @responseField total_pages int
     * @responseField next_page string Returns ```null``` if last page is reached.
     * @responseField prev_page string Returns ```null``` if it is the first page.
     * 
     * @responseField results object[] List of tournaments
     * @responseField results.id integer Tournament ID.
     * @responseField results.name string Tournament name.
     * @responseField results.description string Tournament description.
     * @responseField results.started boolean Tournament started.
     * @responseField results._url string URL to tournament resource.
     * @responseField results.created_at string Tournament creation timestamp.
     * @responseField results.updated_at string Last update timestamp.
     * 
     * @responseFile 200 scenario="Success" responses/tournaments/get_tournaments.json
     * @responseFile 404 scenario="Not Found" responses/errors/model.not_found.json
     */
    public function index(Request $request){
        $page = 1;
        if($request->page && $request->page >= 1){
            $page = $request->page;
        }
        $limit = 10;
        $offset = ($page - 1) * $limit;
        if($request->limit && $request->limit > 1){
            $limit = $request->limit;
        }

        $sql = Tournament::orderBy('created_at', 'DESC');
        if($request->name){
            $sql->where('name', 'LIKE', "%$request->name%");
        }

        if($request->user){
            $sql->where('user_id', $request->user);
        }

        $results = $sql->limit($limit)->offset($offset)->get();
        $count = $sql->count('id');
        $totalPages = ceil($count / $limit);
        $nextPage = $page > $totalPages ? null : route('tournaments', ['page' => $page + 1], false);
        $prevPage = $page === 1 ? null : route('tournaments', ['page' => $page - 1], false);

        return response()->json([
            'count' => $count,
            'total_pages' => $totalPages,
            'next_page' => $nextPage,
            'prev_page' => $prevPage,
            'results' => $results
        ]);
    }

    /**
     * Get a tournament
     * 
     * @responseField id integer Tournament ID.
     * @responseField name string Tournament name.
     * @responseField description string Tournament description.
     * @responseField started boolean Tournament started.
     * @responseField _url string URL to tournament resource.
     * @responseField created_at string Tournament creation timestamp.
     * @responseField updated_at string Last update timestamp.
     * 
     * @responseFile 200 scenario="Success" responses/tournaments/get_tournament.json
     * @responseFile 404 scenario="Not Found" responses/errors/model.not_found.json
     */
    public function show(int $id){
        $tournament = Tournament::find($id);

        if(!$tournament){
            return response()->json(['message' => 'Tournament not found'], 404);
        }

        return response()->json($tournament);
    }

    /**
     * Create a tournament
     * 
     * @authenticated
     * @responseFile 201 scenario="Created" responses/tournaments/get_tournament.json
     * @responseFile 422 scenario="Invalid Request Body" responses/tournaments/post_tournament.error.json
     * @response 401 scenario="Unauthorized" {"message": "Unauthenticated"}
     */
    public function create(TournamentRequest $request){
        $tournament = new Tournament();
        $tournament->name = $request->name;
        $tournament->description = $request->description;
        $tournament->user_id = auth()->user()->id;
        $tournament->save();

        $tournament->refresh();

        return response()->json($tournament);
    }

    /**
     * Replace a tournament
     * 
     * Tournament can only be replaces/edited by tournament owner.
     * 
     * @responseFile 200 scenario="Success" responses/tournaments/get_tournament.json
     * @responseFile 404 scenario="Not Found" responses/errors/model.not_found.json
     * @responseFile 422 scenario="Invalid Request Body" responses/tournaments/post_tournament.error.json
     */
    public function replace(TournamentRequest $request, int $id){
        $tournament = Tournament::find($id);
        
        if(!$tournament){
            return response()->json(['message' => 'Tournament not found'], 404);
        }

        if($tournament->user_id !== auth()->user()->id){
            return response()->json(['message' => 'Unauthorized: Not tournament owner'], 401);
        }

        $tournament->name = $request->name;
        $tournament->description = $request->description ?? null;

        $tournament->save();

        return response()->json($tournament);
    }

    /**
     * Delete a tournament
     * 
     * Tournament can only be deleted by tournament owner.
     * 
     * @authenticated
     * @response 200 scenario="Success" {"message": "Tournament deleted"}
     * @responseFile 404 scenario="Not Found" responses/errors/model.not_found.json
     * @authenticated
     * @response 401 scenario="Unauthorized" {"message": "Unauthenticated"}
     */
    public function destroy(int $id){
        $tournament = Tournament::find($id);
        
        if(!$tournament){
            return response()->json(['message' => 'Tournament not found'], 404);
        }

        if($tournament->user_id !== auth()->user()->id){
            return response()->json(['message' => 'Unauthorized: Not tournament owner'], 401);
        }

        $tournament->delete();
        return response()->json(['message' => 'Tournament deleted'], 200);
    }

    /**
     * Start Tournament
     * 
     * Start a tournament. Tournament can only be started if all initial brackets are filled and can only be started by tournamnet owner.
     * 
     * @authenticated
     * @response 200 scenario="Success" {"message": "Tournament started"}
     * @responseFile 404 scenario="Not Found" responses/errors/model.not_found.json
     * @response 400 scenario="Bad Request" {"message": "Error message"}
     * @response 401 scenario="Unauthorized" {"message": "Unauthenticated"}
     * 
     */
    public function start(int $tournament_id){
        $tournament = Tournament::find($tournament_id);
        
        if(!$tournament){
            return response()->json(['message' => 'Tournament not found'], 404);
        }
        
        if($tournament->user_id !== auth()->user()->id){
            return response()->json(['message' => 'Unauthorized: Not tournament owner'], 401);
        }

        if($tournament->started){
            return response()->json(['message' => 'Tournament already started'], 400);
        }

        $bracket = Bracket::whereNull('match')->whereNull('player_id')->get();
        if($bracket->count() > 0){
            return response()->json(['message' => 'Please fill all initial brackets before starting tournament'], 400);
        }

        $tournament->started = true;
        $tournament->save();

        return response()->json(['message' => 'Tournament started'], 200);
    }
}
