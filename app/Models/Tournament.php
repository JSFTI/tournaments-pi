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
        $this->hasMany(Player::class);
    }

    public function getUrlAttribute(){
        if($this->id && $this->id !== null){
            return route('tournament', ['id' => $this->id], false);
        }
        return null;
    }
}
