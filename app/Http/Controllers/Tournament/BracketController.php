<?php

namespace App\Http\Controllers\Tournament;

use App\Http\Controllers\Controller;
use App\Models\Bracket;
use App\Models\Player;
use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * @group Manage Brackets
 * 
 * API end-poitns to manage tournament brackets.
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
        $tree['round'] = $maxRound - $tree['depth'];
        if($tree['round'] === 0){
            $tree['round'] = null;
        }
        unset($tree['depth']);

        unset($tree['parent_id']);
        info(count($tree['children']));
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

    /**
     * Restructure flat tree bracket to fit documentation.
     * 
     * @param array $trees Bracket list to be restructured, passed by reference.
     * @param int $maxRound Maximum ammount of rounds for the bracket tree.
     * 
     * @return void
     */
    static private function restructureListBracketValues(array &$flatTree, int $maxRound){

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
        $tournament = Tournament::find($tournament_id);
        if(!$tournament){
            return response()->json(['message' => 'Tournament not found'], 404);
        }

        $dataStructure = 'tree';

        if($request->dataStructure === 'list'){
            $dataStructure = 'list';
        }
        
        $brackets = Bracket::where('tournament_id', $tournament_id)->withDepth()
            ->get();

        $maxRound = $brackets->max((function($x){ return $x->depth; }));

        $returnJson = [
            'brackets' => null,
            '_url' => route('tournaments.brackets', ['tournament' => $tournament_id], false),
            'tournament_url' => route('tournament', ['id' => $tournament_id], false),
            'total_rounds' => $maxRound
        ];

        if($dataStructure === 'tree'){
            $tree = $brackets->toTree()->toArray()[0];
            self::restructureTreeBracketValues($tree, $maxRound);
            $returnJson['brackets'] = $tree;
        } else {
            $flatTree = $brackets->toFlatTree();
            $returnJson['brackets'] = $flatTree->map(function($x) use ($maxRound){
                $prev_match = null;
                $round = $maxRound - $x->depth === 0 ? null : $maxRound - $x->depth;
                
                $children = $x->children->map(function($x) use ($round){
                    return [
                        'id' => $x->id,
                        'match' => $x->match,
                        'player_id' => $x->player_id,
                        'tournament_id' => $x->tournament_id,
                        'created_at' => $x->created_at,
                        'updated_at' => $x->updated_at,
                        'round' => $round - 1 === 0 ? null : $round - 1
                    ]; 
                });

                if($children && count($children) > 0){
                    $prev_match = [
                        'left' => $children,
                        'right' => $children
                    ];
                }

                return [
                    'id' => $x->id,
                    'match' => $x->match,
                    'player_id' => $x->player_id,
                    'tournament_id' => $x->tournament_id,
                    'created_at' => $x->created_at,
                    'updated_at' => $x->updated_at,
                    'round' => $round,
                    'prev_match' => $prev_match
                ];
            });
        }

        return response()->json($returnJson);
    }

    /**
     * Create new brackets
     * 
     * Brackets will be filled randomly with players associated with the tournament. Brackets will be build until the final bracket.
     * 
     * <aside class="info">No body parameters are required for this end-point. Any body parameters will be ignored</aside>
     * 
     * @queryParam dataStructure enum(tree,list) Defaults to "list". Returns created brackets in tree or list.<br/>For **trees**, brackets will be structured recursively in a **```binary tree```**, under the ```prev_match``` attribute with the last match as root.<br/>For **lists**, brackets will be structured in an **```array```**.
     * 
     * @responseField brackets object[] Array of bracket objects.
     * @responseField brackets.round int Consider Number of round the bracket is in.
     * @responseField brackets.match int If a bracket has previous match, it will be filled by match number,
     * @responseField brackets.player object Player in this bracket. If **null**, it means that the bracket has no player yet.
     * @responseField brackets._url string URL to bracket.
     * @responseField brackets.prev_match object Previous match. Seperated to left and right side. If **null**, it means that the bracket has no previous match.
     * @responseField brackets.prev_match.left object Prevous match on the left side.
     * @responseField brackets.prev_match.right object Prevous match on the right side.
     * @responseField _url string URL to bracket list of the tournament.
     * @responseField tournament_url string URL to the tournament.
     * 
     * @responseFile 201 status="Created" responses/brackets/get_brackets_list.json
     * @response 204 status="No Content (No players in tournament)"
     * @responseFile 404 scenario="Not Found" responses/errors/model.not_found.json
     */
    public function create(Request $request, int $tournament_id){
        $tournament = Tournament::find($tournament_id);
        if(!$tournament){
            return response()->json(['message' => 'Tournament not found'], 404);
        }

        $dataStructure = 'tree';

        if($request->dataStructure === 'list'){
            $dataStructure = 'list';
        }

        $players = Player::select('id')->where('tournament_id', $tournament_id)->get()->map(function($x){
            return $x->id;
        })->toArray();

        if(count($players) === 0){
            return response()->noContent();
        }

        shuffle($players);
        Bracket::generate($tournament_id, $players);

        $returnJson = [
            'brackets' => null,
            '_url' => route('tournaments.brackets', ['tournament' => $tournament_id], false),
            'tournament_url' => route('tournament', ['id' => $tournament_id], false)
        ];

        return response()->json($returnJson);
    }
}
