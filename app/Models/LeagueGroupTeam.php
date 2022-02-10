<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeagueGroupTeam extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'league_group_id',
        'team_id',
        'team_squad_id'
    ];

    public function team_points(){
        return $this->hasMany(MatchRank::class, 'league_group_team_id');
    }

    public function team_batting_innings(){
        return $this->hasMany(Inning::class, 'league_group_team_id', 'id')
        ->where('innings_status', '=', 'Finished')
        ->whereDoesntHave('fixture', function (Builder $query) {
            $query->where('is_match_no_result', '=', '1');
            $query->orWhere('is_match_finished', '=', 0);
        });
    }

    public function team_bowling_innings(){
        return $this->hasMany(Inning::class, 'league_group_bowling_team_id', 'id')
        ->where('innings_status', '=', 'Finished')
        ->whereDoesntHave('fixture', function (Builder $query) {
            $query->where('is_match_no_result', '=', '1');
            $query->orWhere('is_match_finished', '=', 0);
        });
    }
    public function teams(){
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }
}
