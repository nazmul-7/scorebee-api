<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatchRank extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_type',
        'tournament_id',
        'league_group_id',
        'league_group_team_id',
        'team_id',
        'fixture_id',
        'match_played',
        'matchPlayed',
        'won',
        'loss',
        'draw',
        'points'
    ];

// live.
// live.
    public function team(){
        return $this->belongsTo(Team::class, 'team_id', 'id')->select('id','team_name', 'team_unique_name', 'team_logo', 'owner_id', 'city');
    }
}
