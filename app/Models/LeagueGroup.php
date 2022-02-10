<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeagueGroup extends Model
{
    use HasFactory;

    protected $fillable = ['league_group_name', 'tournament_id', 'round_type'];

    public function group_teams(){
        return $this->hasMany(LeagueGroupTeam::class);
    }
}
