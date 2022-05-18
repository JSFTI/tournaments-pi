<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Kalnoy\Nestedset\NodeTrait;

class Bracket extends Model
{
    use HasFactory;
    use NodeTrait;
    protected $fillable = ['match', 'player_id', 'tournament_id'];

    public function tournament(){
        return $this->belongsTo(Tournament::class);
    }

    public function player(){
        return $this->belongsTo(Player::class);
    }

    static private function buildToRoot(int $tournament_id, array $leaves, int $match){
        $remainderRounds = log(count($leaves), 2) - 1;

        for($i = 0; $i < $remainderRounds; $i++){
            for($j = 0; $j < 2 ** ($remainderRounds - $i); $j++){
                $l1 = array_shift($leaves);
                $l2 = array_shift($leaves);
                $l1['match'] = $match++;
                $l2['match'] = $match++;
                $parent = [
                    'tournament_id' => $tournament_id,
                    'children' => [
                        $l1,
                        $l2
                    ]
                ];
                array_push($leaves, $parent);
            }
        }

        $l1 = array_shift($leaves);
        $l2 = array_shift($leaves);
        $l1['match'] = $match++;
        $l2['match'] = $match++;

        self::create([
            'tournament_id' => $tournament_id,
            'match' => $match,
            'children' => [
                $l1,
                $l2
            ]
        ]);
    }

    static public function generate(int $tournament_id,array $player_ids){
        DB::beginTransaction();
        $playersCount = count($player_ids);
        $byes = ($playersCount > 2) ? calculateByes($playersCount) : 2;
        $firstRounders = $playersCount - $byes;
        $singularByes = $byes > $firstRounders / 2 ? $firstRounders / 2 : $byes;

        /**
         * +--+
         * |XX|---+
         * +--+   |   +--+
         *        +---|  |---+
         * +--+   |   +--+   |   +--+
         * |XX|---+          +---|  |---[Main Tree]
         * +--+       +--+   |   +--+
         *            |XX|---+
         *            +--+
         */
        $singularByesNodes = [];
        while($singularByes > 0){
            $newPlayerNode = [
                'tournament_id' => $tournament_id,
                'children' => [
                    [
                        'tournament_id' => $tournament_id,
                        'children' => [
                            [
                                'tournament_id' => $tournament_id,
                                'player_id' => array_pop($player_ids),
                            ],[
                                'tournament_id' => $tournament_id,
                                'player_id' => array_pop($player_ids),
                            ],
                        ],
                    ],
                    [
                        'tournament_id' => $tournament_id,
                        'player_id' => array_pop($player_ids),
                    ]
                ],
            ];
            $singularByesNodes[] = $newPlayerNode;
            $firstRounders -= 2;
            $singularByes--;
            $byes--;
        }

        /**
         * +--+
         * |XX|---+
         * +--+   |   +--+
         *        +---|  |---[Main Tree]
         * +--+   |   +--+ 
         * |XX|---+
         * +--+       
         */
        $byesNodes = [];
        while($byes){
            $newPlayerNode = [
                'tournament_id' => $tournament_id,
                'children' => [
                    [
                        'tournament_id' => $tournament_id,
                        'player_id' => array_pop($player_ids)
                    ],[
                        'tournament_id' => $tournament_id,
                        'player_id' => array_pop($player_ids)
                    ]
                ]
            ];
            $byesNodes[] = $newPlayerNode;
            $byes -= 2;
        }
        
        /**
         * +--+
         * |XX|---+
         * +--+   |   +--+
         *        +---|  |---+
         * +--+   |   +--+   |   
         * |XX|---+          |
         * +--+              |   +--+
         *                   +---|  |---[Main Tree]
         * +--+              |   +--+
         * |XX|---+          |
         * +--+   |   +--+   |
         *        +---|  |---+
         * +--+   |   +--+
         * |XX|---+
         * +--+    
         */
        $firstRoundersNodes = [];
        while($firstRounders){
            $newPlayerNode = [
                'tournament_id' => $tournament_id,
                'children' => [
                    [
                        'tournament_id' => $tournament_id,
                        'children' => [
                            [
                                'tournament_id' => $tournament_id,
                                'player_id' => array_pop($player_ids)
                            ],[
                                'tournament_id' => $tournament_id,
                                'player_id' => array_pop($player_ids)
                            ]
                        ]
                    ],[
                        'tournament_id' => $tournament_id,
                        'children' => [
                            [
                                'tournament_id' => $tournament_id,
                                'player_id' => array_pop($player_ids)
                            ],[
                                'tournament_id' => $tournament_id,
                                'player_id' => array_pop($player_ids)
                            ]
                        ]
                    ]
                ]
            ];
            $firstRoundersNodes[] = $newPlayerNode;
            $firstRounders -= 4;
        }

        $match = 1;
        for($i = 0; $i < count($singularByesNodes); $i++){
            $singularByesNodes[$i]['children'][0]['match'] = $match++;
        }
        for($i = 0; $i < count($firstRoundersNodes); $i++){
            $firstRoundersNodes[$i]['children'][0]['match'] = $match++;
            $firstRoundersNodes[$i]['children'][1]['match'] = $match++;
        }
        $bracketNodes = [...$byesNodes, ...$singularByesNodes, ...$firstRoundersNodes];
        if(count($bracketNodes) > 1){
            self::buildToRoot($tournament_id, $bracketNodes, $match);
        } else {
            $bracketNodes[0]['match'] = $match;
            self::create($bracketNodes[0]);
        }
        DB::commit();
    }
}
