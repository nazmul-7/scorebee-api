<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubPlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'requested_by',
        'status',
        'club_owner_id',
        'player_id'
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id');
    }

    public function matchesPlayed()
    {
        return $this->hasMany(PlayingEleven::class, 'player_id', 'player_id')
            ->where('is_played', 1);
    }

    public function inningsBattingResults()
    {
        return $this->hasMany(InningBatterResult::class, 'batter_id', 'player_id');
    }

    public function inningsBowlingResults()
    {
        return $this->hasMany(InningBowlerResult::class, 'bowler_id', 'player_id');
    }

    public function inningsCaughtOutResults()
    {
        return $this->hasMany(InningBatterResult::class, 'caught_by', 'player_id');
    }

    public function inningsAssistedOutResults()
    {
        return $this->hasMany(InningBatterResult::class, 'assisted_by', 'player_id');
    }

    public function inningsRunOutResults()
    {
        return $this->hasMany(InningBatterResult::class, 'run_out_by', 'player_id');
    }

    public function inningsStumpedOutResults()
    {
        return $this->hasMany(InningBatterResult::class, 'stumped_by', 'player_id');
    }

    public function scopeClubTeamFilter($query, $attributes)
    {
        return $query
            ->when(!$attributes['team_id'], function ($q) use ($attributes) {
                $q
                    ->where('club_owner_id', $attributes['club_owner_id'])
                    ->where('status', 'ACCEPTED');
            })
            ->when($attributes['team_id'], function ($q) use ($attributes) {
                $q->whereIn('club_player_id', function ($q) use ($attributes) {
                    $q
                        ->select('player_id')
                        ->from('team_players')
                        ->where('team_id', $attributes['team_id']);
                });
            });
    }
}
