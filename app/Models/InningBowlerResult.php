<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InningBowlerResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'bowler_id',
        'tournament_id',
        'league_group_id',
        'league_group_team_id',
        'match_type',
        'fixture_id',
        'inning_id',
        'team_id',
        'overs_bowled',
        'balls_bowled',
        'maiden_overs',
        'runs_gave',
        'wide_balls',
        'no_balls',
        'wickets',
        'is_on_strike'
    ];

    public function scopefilterBowlingLeaderBoard($q, $category, $year, $inning, $over, $ball_type, $team, $tournament){
        $q->when($year, function($q) use ($year){
            $q->Year($year);
        });
        $q->when($inning, function($q) use ($inning){
            $q->Inning($inning);
        });
        $q->when($over, function($q) use ($over){
            $q->Over($over);
        });
        $q->when($ball_type, function($q) use ($ball_type){
            $q->ballType($ball_type);
        });
        $q->when($team, function($q) use ($team){
            $q->Team($team);
        });
        $q->when($tournament, function($q) use ($tournament){
            $q->Tournament($tournament);
        });
        $q->when($category, function($q) use ($category){
            $q->Category($category);
        });
    }
    public function scopeYear($query, $value)
    {
        return $query->whereRaw("Year(created_at) = $value ");
    }

    public function scopeInning($query, $value)
    {
        return $query->where('match_type', $value);
    }

    public function scopeTeam($query, $value)
    {
        return $query->where('team_id', $value);
    }

    public function scopeTournament($query, $value)
    {
        return $query->where('tournament_id', $value);
    }

    public function scopeCategory($query, $value)
    {
        return $query->whereIn('tournament_id', function ($query2) use ($value) {
            $query2->select('id')->from('tournaments')->where('tournament_category', $value);
         });
    }

    public function scopeOver($query, $value)
    {
        return $query->whereHas('fixture', function (Builder $query) use ($value) {
            $query->where('match_overs', $value);
        });
    }

    public function scopeballType($query, $value)
    {
        return $query->whereHas('fixture', function (Builder $query) use ($value) {
            $query->where('ball_type', $value);
        });
    }

    public function bowler(){
        return $this->belongsTo(User::class, 'bowler_id')->select('id','first_name','last_name','profile_pic');
    }
    public function team(){
        return $this->belongsTo(Team::class, 'team_id')->select('id','team_name','team_unique_name','team_short_name','team_logo');
    }

    public function deliveries(){
        return $this->hasMany(Delivery::class, 'inning_id', 'inning_id');
    }
    public function innings_batter()
    {
        return $this->hasOne(InningBatterResult::class,'inning_id','inning_id');
    }
    public function innings(){
        return $this->belongsTo(Inning::class, 'inning_id')->select('id','is_first_innings');
    }
    public function fixture(){
        return $this->belongsTo(Fixture::class, 'fixture_id')->select('id','match_winner_team_id','match_loser_team_id');
    }

    public function scopeClubFilter($query, $attributes)
    {
        return $query
            ->when(!$attributes['team_id'], function ($q) use ($attributes) {
                $q->whereIn('team_id', function ($q) use ($attributes) {
                    $q
                        ->select('id')
                        ->from('teams')
                        ->where('owner_id', $attributes['club_owner_id']);
                });
            })
            ->when($attributes['team_id'], function ($q) use ($attributes) {
                $q->where('team_id', $attributes['team_id']);
            })
            ->when($attributes['is_filter_enabled'], function ($query) use ($attributes) {
                $query
                    ->whereHas('fixture', function ($q) use ($attributes) {
                        $q
                            ->when($attributes['match_overs'], function ($query) use ($attributes) {
                                $query->where('match_overs', $attributes['match_overs']);
                            })
                            ->when($attributes['ball_type'], function ($query) use ($attributes) {
                                $query->where('ball_type', $attributes['ball_type']);
                            })
                            ->when($attributes['year'], function ($query) use ($attributes) {
                                $query->whereYear('match_date', $attributes['year']);
                            })
                            ->when($attributes['match_type'], function ($query) use ($attributes) {
                                $query->where('match_type', $attributes['match_type']);
                            })
                            ->when($attributes['tournament_id'], function ($query) use ($attributes) {
                                $query->where('tournament_id', $attributes['tournament_id']);
                            })
                            ->when($attributes['tournament_category'], function ($query) use ($attributes) {
                                $query->whereHas('tournament', function ($query) use ($attributes) {
                                    $query->where('tournament_category', $attributes['tournament_category']);
                                });
                            });
                    });
            });
    }
}
