<?php

namespace App\Http\Controllers;

use App\Http\Requests\TournamentRequest;
use App\Models\Tournament;
use Illuminate\Http\Request;

/**
 * @group Tournament Management
 * 
 * APIS for managing tournaments
 */
class TournamentController extends Controller
{
    /**
     * Get tournaments
     * 
     * Tournament list is ordered in descending order by creation time.
     * 
     * @queryParam page int  Defaults to ```1```. Values less than 1 will default to 1. Example: 1
     * @queryParam name string Search for tournament that contains ```name``` keyword. No-example
     * @queryParam limit int Defaults to ```10```. Values less than 1 will default to 1. Example: 10
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
     * @responseFile 201 scenario="Created" responses/tournaments/get_tournament.json
     * @responseFile 422 scenario="Invalid Request Body" responses/tournaments/post_tournament.error.json
     */
    public function create(TournamentRequest $request){
        $tournament = new Tournament();
        $tournament->name = $request->name;
        $tournament->description = $request->description;
        $tournament->save();

        $tournament->refresh();

        return response()->json($tournament);
    }

    /**
     * Replace a tournament
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

        $tournament->name = $request->name;
        $tournament->description = $request->description ?? null;

        $tournament->save();

        return response()->json($tournament);
    }

    /**
     * Delete a tournament
     * 
     * @response 200 scenario="Success" {"message": "Tournament deleted"}
     * @responseFile 404 scenario="Not Found" responses/errors/model.not_found.json
     */
    public function destroy(int $id){
        $tournament = Tournament::find($id);
        
        if(!$tournament){
            return response()->json(['message' => 'Tournament not found'], 404);
        }

        $tournament->delete();
        return response()->json(['message' => 'Tournament deleted'], 200);
    }
}
