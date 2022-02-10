<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'first_name',
        'last_name',

        'username',
        'phone',
        'email',
        'password',
        'app_token',
        'country',
        'state',
        'city',

        'date_of_birth',
        'birth_place',

        'playing_role',
        'batting_style',
        'bowling_style',

        'profile_pic',
        'cover',
        'nid_pic',
        'gender',
        'bio',
        'hire_info',
        'social_accounts',

        'registration_type',
        'forgot_code',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'pivot'
    ];

    public function playerElevens()
    {
        return $this->hasMany(PlayingEleven::class, 'player_id', 'id');
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class, 'bowler_id', 'id');
    }

    public function inningsbatter()
    {
        return $this->hasMany(InningBatterResult::class, 'batter_id', 'id');
    }

    public function bowlerWickets()
    {
        return $this->hasMany(InningBatterResult::class, 'wicket_by', 'id');
    }

    public function inningsBowler()
    {
        return $this->hasMany(InningBowlerResult::class, 'bowler_id', 'id');
    }
    public function best_in_test()
    {
        return $this->hasMany(InningBowlerResult::class, 'bowler_id', 'id');
    }

    public function batting()
    {
        return $this->hasMany(Delivery::class, 'batter_id', 'id');
    }
    public function batting_by_deliveries()
    {
        return $this->hasMany(Delivery::class, 'batter_id', 'id')
            ->where(function ($q) {
                $q
                    ->where('ball_type', '=', 'LEGAL')
                    ->orWhere('ball_type', '=', 'NB');
            })
            ->whereNull('run_type');

            // ->where(function ($q) {
            //     $q
            //         ->where('ball_type', '!=', 'DB')
            //         ->orWhere('ball_type', '!=', 'WD');
            // })
            // ->where(function ($q) {
            //     $q
            //         ->where('run_type', '!=', 'B')
            //         ->orWhere('run_type', '!=', 'LB');
            // });
    }

    public function playingElevens()
    {
        return $this->hasMany(PlayingEleven::class, 'player_id', 'id');
    }

    public function assistBy()
    {
        return $this->hasMany(Delivery::class, 'assist_by', 'id');
    }

    public function caughtBy()
    {
        return $this->hasMany(Delivery::class, 'caught_by', 'id');
    }

    public function bowlerWicketsDelivery()
    {
        return $this->hasMany(Delivery::class, 'wicket_by', 'id');
    }

    public function stumpedBy()
    {
        return $this->hasMany(Delivery::class, 'stumped_by', 'id');
    }

    public function nunOutBy()
    {
        return $this->hasMany(Delivery::class, 'run_out_by', 'id');
    }

    public function memberOfClubs(): HasMany
    {
        return $this->hasMany(ClubPlayer::class, 'player_id');
    }

    public function clubMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'club_players', 'club_owner_id', 'player_id');
    }

    public function memberOfTeams(): HasMany
    {
        return $this->hasMany(TeamPlayer::class, 'player_id');
    }

    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class, 'organizer_id', 'id');
    }

    public function matchesPlayed()
    {
        return $this->hasMany(PlayingEleven::class, 'player_id', 'id')
            ->where('is_played', 1);
    }

    public function inningsBattingResults()
    {
        return $this->hasMany(InningBatterResult::class, 'batter_id', 'id');
    }

    public function inningsBowlingResults()
    {
        return $this->hasMany(InningBowlerResult::class, 'bowler_id', 'id');
    }

    public function inningsCaughtOutResults()
    {
        return $this
            ->hasMany(InningBatterResult::class, 'caught_by', 'id')
            ->where('is_out', 1);
    }

    public function inningsAssistedOutResults()
    {
        return $this
            ->hasMany(InningBatterResult::class, 'assist_by', 'id')
            ->where('is_out', 1);
    }

    public function inningsRunOutResults()
    {
        return $this
            ->hasMany(InningBatterResult::class, 'run_out_by', 'id')
            ->where('is_out', 1);
    }

    public function inningsStumpedOutResults()
    {
        return $this
            ->hasMany(InningBatterResult::class, 'stumped_by', 'id')
            ->where('is_out', 1);
    }

    public function scopeClubTeamFilter($query, $attributes)
    {
        return $query
            // ->when(!$attributes['team_id'], function ($q) use ($attributes) {
            //     $q->whereIn('id', function($q){
            //         $q->select('player_id')->where('club_owner_id', $attributes['club_owner_id'])
            //         ->where('status', 'ACCEPTED');
            //     });
            // })
            // ->when($attributes['team_id'], function ($q) use ($attributes) {
            //     $q->whereIn('club_player_id', function ($q) use ($attributes) {
            //         $q
            //             ->select('player_id')
            //             ->from('team_players')
            //             ->where('team_id', $attributes['team_id']);
            //     });
            // })
            ->when(!$attributes['team_id'], function ($q) use ($attributes) {
                $q->whereHas('memberOfClubs', function ($query) use ($attributes) {
                    $query
                        ->where('club_owner_id', $attributes['club_owner_id'])
                        ->where('status', 'ACCEPTED');
                });
            })
            ->when($attributes['team_id'], function ($q) use ($attributes) {
                $q->whereHas('memberOfTeams', function ($query) use ($attributes) {
                    $query
                        ->where('team_id', $attributes['team_id']);
                });
            });
    }
}
