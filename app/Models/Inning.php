<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Inning extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'league_group_id',
        'league_group_team_id',
        'league_group_bowling_team_id',
        'fixture_id',
        'batting_team_id',
        'bowling_team_id',
        'home_team_id',
        'away_team_id',
        'title',
        'initial_bowler_id',
        'initial_striker_id',
        'initial_non_striker_id',
        'initial_keeper_id',
        'is_first_innings',
        'total_overs',
        'total_runs',
        'total_wickets',
    ];

    public function inning_bowler()
    {
        return $this->hasMany(InningBowlerResult::class, 'team_id', 'bowling_team_id');
    }

    public function inning_batter()
    {
        return $this->hasMany(InningBatterResult::class, 'team_id', 'batting_team_id');
    }

    public function did_not_bat()
    {
        return $this->hasMany(PlayingEleven::class, 'team_id', 'batting_team_id');
    }

    public function innings_overs(): HasMany
    {
        return $this->hasMany(Over::class, 'inning_id');
    }

    public function batting_team()
    {
        return $this->belongsTo(Team::class, 'batting_team_id')->select('id', 'team_name', 'team_short_name', 'team_logo');
    }

    public function bowling_team()
    {
        return $this->belongsTo(Team::class, 'bowling_team_id')->select('id', 'team_name', 'team_short_name', 'team_logo');
    }

    public function fixture(){
        return $this->belongsTo(Fixture::class);
    }

    public function bowlers(): HasMany
    {
        return $this->hasMany(InningBowlerResult::class);
    }

    public function currentBowler(): HasOne
    {
        return $this->hasOne(InningBowlerResult::class)->where('is_on_strike', 1);
    }

    public function currentStriker(): HasOne
    {
        return $this->hasOne(InningBatterResult::class)
            ->where('is_out', 0)
            ->where('is_on_strike', 1);
    }

    public function currentNonStriker(): HasOne
    {
        return $this->hasOne(InningBatterResult::class)
            ->where('is_out', 0)
            ->where('is_on_strike', 0);
    }

    public function fall_of_wicket(){
        return $this->hasMany(WicketFall::class, 'inning_id', 'id');
    }

    public function previous_innings(){
        return $this->hasOne(Inning::class, 'bowling_team_id','batting_team_id');
    }

    public function oppTeam(){
        return $this->hasOne(Inning::class, 'fixture_id','fixture_id');
    }

    public function powerplay(){
        return $this->hasMany(MatchPowerPlay::class);
    }

    public function deliveries(){
        return $this->hasMany(Delivery::class);
    }

}
