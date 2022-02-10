<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InningBatterResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'fixture_id',
        'league_group_id',
        'league_group_team_id',
        'match_type',
        'inning_id',
        'team_id',
        'batter_id',
        'overs_faced',
        'balls_faced',
        'runs_achieved',
        'strike_rate',
        'fours',
        'sixes',
        'is_out',
        'is_on_strike',
        'wicket_type',
        'wicket_by',
        'assist_by',
        'caught_by',
        'stumped_by',
        'position'
    ];

    public function scopefilterBattingLeaderBoard($q, $year, $inning, $ball_type, $over, $tournament, $category, $team){
        $q->when($year, function($q2) use ($year){
            $q2->whereRaw("Year(created_at) = $year");
         });
         $q->when($team, function($q2) use ($team){
            $q2->where('team_id', $team);
         });
         $q->when($inning, function($q2) use ($inning){
            $q2->where('match_type', $inning);
         });
         $q->when($ball_type, function($q2) use ($ball_type){
             $q2->whereIn('fixture_id', function ($q3) use ($ball_type) {
                 $q3->select('id')->from('fixtures')
                 ->where('ball_type', $ball_type);
             });
         });
         $q->when($over, function($q2) use ($over){
             $q2->whereIn('fixture_id', function ($q3) use ($over) {
                 $q3->select('id')->from('fixtures')
                 ->where('match_overs', $over);
             });
         });
         $q->when($tournament, function($q2) use($tournament) {
             $q2->where('tournament_id', $tournament);
         });
         $q->when($category, function($q2) use($category) {
             $q2->whereIn('tournament_id', function ($q2) use ($category) {
                 $q2->select('id')->from('tournaments')
                 ->where('tournament_category', $category);
              });
         });
    }

    public function batter()
    {
        return $this->belongsTo(User::class, 'batter_id')->select('id', 'first_name', 'last_name', 'profile_pic', 'playing_role', 'batting_style');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id')->select('id', 'team_name', 'team_unique_name', 'team_short_name', 'team_logo');
    }

    public function caught_by()
    {
        return $this->belongsTo(User::class, 'caught_by')->select('id', 'first_name', 'last_name', 'profile_pic');
    }

    public function stumped_by()
    {
        return $this->belongsTo(User::class, 'stumped_by')->select('id', 'first_name', 'last_name', 'profile_pic');
    }

    public function assist_by()
    {
        return $this->belongsTo(User::class, 'assist_by')->select('id', 'first_name', 'last_name', 'profile_pic');
    }

    public function wicket_by()
    {
        return $this->belongsTo(User::class, 'wicket_by')->select('id', 'first_name', 'last_name', 'profile_pic');
    }

    public function fixture(): BelongsTo
    {
        return $this->belongsTo(Fixture::class);
    }

    public function fixers()
    {
        return $this->hasOne(Fixture::class, 'match_winner_team_id', 'team_id')->select('id', 'match_winner_team_id');
    }

    public function delevery()
    {
        return $this->hasMany(Delivery::class, 'inning_id', 'inning_id');
    }

    public function innings_bowler()
    {
        return $this->hasOne(InningBowlerResult::class, 'inning_id', 'inning_id');
    }


    public function scopeClubFilter($query, $attributes)
    {
        return $query
            ->when($attributes['stats_type'] == 'BATTING' and !$attributes['team_id'], function ($q) use ($attributes) {
                $q->whereIn('team_id', function ($q) use ($attributes) {
                    $q
                        ->select('id')
                        ->from('teams')
                        ->where('owner_id', $attributes['club_owner_id']);
                });
            })
            ->when($attributes['stats_type'] == 'BATTING' and $attributes['team_id'], function ($q) use ($attributes) {
                $q->where('team_id', $attributes['team_id']);
            })
            ->when($attributes['stats_type'] == 'FIELDING' and !$attributes['team_id'], function ($q) use ($attributes) {
                $q->whereIn('inning_id', function ($q) use ($attributes) {
                    $q
                        ->select('id')
                        ->from('innings')
                        ->whereIn('bowling_team_id', function ($q) use ($attributes) {
                            $q
                                ->select('id')
                                ->from('teams')
                                ->where('owner_id', $attributes['club_owner_id']);
                        });
                });
            })
            ->when($attributes['stats_type'] == 'FIELDING' and $attributes['team_id'], function ($q) use ($attributes) {
                $q->whereIn('inning_id', function ($q) use ($attributes) {
                    $q
                        ->select('id')
                        ->from('innings')
                        ->where('bowling_team_id', $attributes['team_id']);
                });
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
