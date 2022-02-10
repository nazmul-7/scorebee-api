<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_name',
        'team_unique_name',
        'team_short_name',
        'team_banner',
        'team_logo',
        'city',
        'owner_id',
        'captain_id',
        'wicket_keeper_id',
    ];

   public function teamPlayers()
   {
       return $this->hasMany(TeamPlayer::class);
   }
   public function tournament_team()
   {
       return $this->hasMany(TournamentTeam::class, 'team_id', 'id');
   }
   public function match_rank()
   {
       return $this->hasMany(MatchRank::class, 'team_id', 'id');
   }
   public function batting_team_inning()
   {
       return $this->hasMany(Inning::class, 'batting_team_id', 'id');
   }
   public function bowling_team_inning()
   {
       return $this->hasMany(Inning::class, 'bowling_team_id', 'id');
   }
   public function fixture()
   {
       return $this->hasMany(Fixture::class, 'home_team_id', 'id');
   }
   public function home_team_fixture()
   {
       return $this->hasMany(Fixture::class, 'home_team_id', 'id');
   }
    public function away_team_fixture(){
        return $this->hasMany(Fixture::class, 'away_team_id', 'id');
    }
    public function tournament(){
        return $this->hasOne(TournamentTeam::class, 'team_id','id');
    }
    public function captain(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captain_id');
    }

    public function wicketKeeper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wicket_keeper_id');
    }

    public function playerRequests()
    {
        return $this->belongsToMany(User::class, 'team_players', 'team_id', 'player_id');
    }
    public function playing_eleven(){
        return $this->hasOne(PlayingEleven::class,'team_id', 'id')->select('id', 'player_id', 'team_id', 'is_captain')
        ->where('is_captain', 1);
    }
    public function club(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
    public function turnament_club(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id')->select('id', 'app_token');
    }

    public function players(): BelongsToMany{
        return $this->belongsToMany(User::class, 'team_players', 'team_id', 'player_id')->select('first_name','last_name','profile_pic','playing_role','batting_style','bowling_style');
    }
}
