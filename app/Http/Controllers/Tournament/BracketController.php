<?php

namespace App\Http\Controllers\Tournament;

use App\Http\Controllers\Controller;
use App\Models\Bracket;
use App\Models\Player;
use App\Models\Tournament;
use Illuminate\Http\Request;

/**
 * @group Manage Brackets
 * 
 * API end-points to manage tournament brackets.
 */
class BracketController extends Controller
{
    /**
     * Restructure tree bracket to fit documentation.
     * 
     * @param array $trees Bracket trees to be restructured, passed by reference.
     * @param int $maxRound Maximum ammount of rounds for the bracket tree.
     * 
     * @return void
     */
    static private function restructureTreeBracketValues(array &$tree, int $maxRound){
        unset($tree['_lft']);
        unset($tree['_rgt']);

        $tree['next_match_id'] = $tree['parent_id'];
        // $tree['round'] = $maxRound - $tree['depth'];
        // if($tree['round'] === 0){
            // $tree['round'] = null;
        // }
        unset($tree['depth']);

        unset($tree['parent_id']);

        if(!$tree['children'] && count($tree['children']) === 0){
            $tree['prev_match'] = null;
            unset($tree['children']);
            return;
        }
        $tree['prev_match'] = [
            'left' => $tree['children'][0],
            'right' => $tree['children'][1]
        ];
        unset($tree['children']);
        
        self::restructureTreeBracketValues($tree['prev_match']['left'], $maxRound);
        self::restructureTreeBracketValues($tree['prev_match']['right'], $maxRound);
    }

    static private function generateStructure($dataStructure, $brackets){
        $maxRound = $brackets->max((function($x){ return $x->depth; }));

        if($dataStructure === 'tree'){
            $tree = $brackets->toTree()->toArray()[0];
            self::restructureTreeBracketValues($tree, $maxRound);

            return $tree;
        } else {
            $flatTree = $brackets->toFlatTree();

            return $flatTree->map(function($x) use ($maxRound){
                $prev_match = null;
                $round = $maxRound - $x->depth === 0 ? null : $maxRound - $x->depth;
                
                $children = $x->children->map(function($x) use ($round){
                    return [
                        'id' => $x->id,
                        'match' => $x->match,
                        'player_id' => $x->player_id,
                        'player' => $x->player,
                        'tournament_id' => $x->tournament_id,
                        'created_at' => $x->created_at,
                        'updated_at' => $x->updated_at,
                        // 'round' => $round - 1 === 0 ? null : $round - 1
                    ]; 
                });

                if($children && count($children) > 0){
                    $prev_match = [
                        'left' => $children[0],
                        'right' => $children[1]
                    ];
                }

                return [
                    'id' => $x->id,
                    'match' => $x->match,
                    'player_id' => $x->player_id,
                    'player' => $x->player,
                    'tournament_id' => $x->tournament_id,
                    'created_at' => $x->created_at,
                    'updated_at' => $x->updated_at,
                    // 'round' => $round,
                    'prev_match' => $prev_match
                ];
            });
        }
    }

    /**
     * Get brackets
     * 
     * @queryParam dataStructure enum(tree,list) Defaults to "list". Returns created brackets in tree or list.<br/>For **trees**, brackets will be structured recursively in a **```binary tree```**, under the ```prev_match``` attribute with the last match as root.<br/>For **lists**, brackets will be structured in an **```array```**.
     * 
     * @responseField foo int Bar.
     * 
     * @responseFile 201 status="Created" responses/brackets/get_brackets_list.json
     * @response 204 status="No Content (No bracket in tournament)"
     * @responseFile 404 scenario="Not Found" responses/errors/model.not_found.json
     */
    public function index(Request $request, int $tournament_id){
        $tournament = Tournament::with('players')->where('id', $tournament_id)->first();
        if(!$tournament){
            return response()->json(['message' => 'Tournament not found'], 404);
        }

        $players = Player::where('tournament_id', $tournament_id)
            ->has('brackets')->get();

        if($players->count() === 0){
            return response()->noContent();
        }

        $remainingPlayers = Player::where('tournament_id', $tournament_id)
            ->doesntHave('brackets')->get();

        $dataStructure = 'tree';

        if($request->dataStructure === 'list'){
            $dataStructure = 'list';
        }
        
        $brackets = Bracket::where('tournament_id', $tournament_id)->with('player')->withDepth()
            ->get();

        $maxRound = $brackets->max((function($x){ return $x->depth; }));

        return response()->json([
            'brackets' => self::generateStructure($dataStructure, $brackets),
            '_url' => route('tournaments.brackets', ['tournament' => $tournament_id], false),
            'tournament_url' => route('tournament', ['tournament' => $tournament_id], false),
            'added_players' => $players,
            'remaining_players' => $remainingPlayers,
            'total_players' => $tournament->players->count(),
            'total_rounds' => $maxRound
        ]);
    }

    /**
     * Create new brackets or replace old brackets.
     * 
     * Brackets will be filled randomly with players associated with the tournament. Brackets will be build until the final bracket.
     * This endpoint can be called again to randomly insert recently added players into the brackets.
     * 
     * <aside class="danger">Calling this endpoint replaces previously generated bracket.</aside>
     * 
     * <aside class="info">No body parameters are required for this end-point. Any body parameters will be ignored</aside>
     * 
     * @queryParam dataStructure enum(tree,list) Defaults to "list". Returns created brackets in tree or list.<br/>For **trees**, brackets will be structured recursively in a **```binary tree```**, under the ```prev_match``` attribute with the last match as root.<br/>For **lists**, brackets will be structured in an **```array```**.
     * @queryParam empty boolean Defaults to "true". If ```empty``` is specified, players will not be added automatically. Refer to **Upsert Player in Bracket**.
     * 
     * @responseField brackets object[] Array of bracket objects.
     * @responseField brackets.match int If a bracket has previous match, it will be filled by match number,
     * @responseField brackets.player object Player in this bracket. If **null**, it means that the bracket has no player yet.
     * @responseField brackets._url string URL to bracket.
     * @responseField brackets.prev_match object Previous match. Seperated to left and right side. If **null**, it means that the bracket has no previous match.
     * @responseField brackets.prev_match.left object Prevous match on the left side.
     * @responseField brackets.prev_match.right object Prevous match on the right side.
     * @responseField _url string URL to bracket list of the tournament.
     * @responseField tournament_url string URL to the tournament.
     * 
     * @responseFile 200 status="Success (Brackets replaced)" responses/brackets/get_brackets_list.json
     * @responseFile 201 status="Created (Brackets created)" responses/brackets/get_brackets_list.json
     * @response 204 status="No Content (No players in tournament)"
     * @responseFile 404 scenario="Not Found" responses/errors/model.not_found.json
     */
    public function create(Request $request, int $tournament_id){
        $tournament = Tournament::with('players')->where('id', $tournament_id)->first();

        if(!$tournament){
            return response()->json(['message' => 'Tournament not found'], 404);
        }

        if($tournament->players->count() === 0){
            return response()->noContent();
        }

        $players = $tournament->players->map(function($x) use ($request){
            return $request->empty ? null : $x->id;
        })->toArray();
        shuffle($players);

        $dataStructure = 'tree';

        if($request->dataStructure === 'list'){
            $dataStructure = 'list';
        }

        $code = 201;
        $oldBracket = $tournament->brackets;
        if($oldBracket){
            $code = 200;
            Bracket::where('tournament_id', $tournament_id)->delete();
        }

        Bracket::generate($tournament_id, $players);

        $brackets = Bracket::where('tournament_id', $tournament_id)->with('player')->withDepth()
            ->get();

        $maxRound = $brackets->max((function($x){ return $x->depth; }));

        $returnJson = [
            'brackets' => self::generateStructure($dataStructure, $brackets),
            '_url' => route('tournaments.brackets', ['tournament' => $tournament_id], false),
            'tournament_url' => route('tournament', ['id' => $tournament_id], false),
            'added_players' => $request->empty ? [] : $tournament->players,
            'total_players' => $tournament->players->count(),
            'total_rounds' => $maxRound
        ];

        return response()->json($returnJson, $code);
    }
}
