<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PlayingEleven extends Model
{
    use HasFactory;

    protected $fillable = [
        'fixture_id',
        'team_id',
        'player_id',
        'playing_role',
        'type',
        'is_captain',
        'is_wicket_keeper',
        'is_played'
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id');
    }
    public function fixture(){
        return $this->belongsTo(Fixture::class, 'fixture_id');
    }
    public function innings_batter(){
        return $this->belongsTo(InningBatterResult::class, 'fixture_id', 'fixture_id');
    }

    public function inningsBattingResults(): BelongsTo{
        return $this->belongsTo(InningBatterResult::class, 'player_id', 'batter_id');
    }

    public function innings_bowler(){
        return $this->belongsTo(InningBowlerResult::class, 'fixture_id', 'fixture_id');
    }
    public function winner_team()
    {
        return $this->hasOne(Fixture::class, 'match_winner_team_id', 'team_id');
    }
    public function loser_team()
    {
        return $this->hasOne(Fixture::class, 'match_loser_team_id', 'team_id');
    }
    public function toss_winner()
    {
        return $this->belongsTo(Fixture::class, 'team_id', 'toss_winner_team_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
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
