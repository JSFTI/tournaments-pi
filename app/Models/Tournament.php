<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description'];
    protected $appends = ['_url'];

    public function players(){
        return $this->hasMany(Player::class);
    }

    public function brackets(){
        return $this->hasMany(Bracket::class);
    }

    public function getUrlAttribute(){
        if($this->id && $this->id !== null){
            return route('tournament', ['tournament' => $this->id], false);
        }
        return null;
    }
}
