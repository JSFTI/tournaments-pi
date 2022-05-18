<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    use HasFactory;

    protected $appends = ['_url', 'tournament_url'];

    public function tournament(){
        return $this->belongsTo(Tournament::class);
    }

    public function brackets(){
        return $this->hasMany(Bracket::class);
    }

    public function getUrlAttribute(){
        if($this->id){
            return route('player', ['player' => $this->id], false);
        }
        return null;
    }

    public function getTournamentUrlAttribute(){
        if($this->tournament_id){
            return route('tournament', ['tournament' => $this->tournament_id], false);
        }
        return null;   
    }
}
