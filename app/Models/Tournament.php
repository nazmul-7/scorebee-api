<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_name',
        'tournament_logo',
        'tournament_banner',
        'tournament_category',
        'tournament_type',

        'match_type',
        'ball_type',
        'test_match_duration',
        'test_match_session',

        'city',
        'start_date',
        'end_date',

        'is_start',
        'is_finished',
        'is_verified_player',
        'is_whatsapp',
        'player_of_the_tournament',
        'details',
        'tags',
        'organizer_id',
        'organizer_name',
        'organizer_phone',
        'wagon_settings',
        'group_settings'
    ];

    protected $casts = [
        // 'sellingPrice' => 'integer',
        // 'description' => 'array',
        'group_settings' => 'array',
        // 'wagon_settings' => 'array',
        // 'averageBuyingPrice' => 'integer',
        // 'openingUnitPrice' => 'integer',


     ];



    public function tournament_team()
    {
        return $this->hasMany(TournamentTeam::class);
    }

    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id', 'id');
    }

    public function grounds()
    {
        return $this->belongsToMany(Ground::class, 'tournament_grounds')->withTimestamps();
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'tournament_teams')->withTimestamps();
    }

    public function match_ranks(){
        return $this->hasMany(MatchRank::class, 'tournament_id', 'id');
    }
    public function innings(){
        return $this->hasMany(Inning::class, 'tournament_id', 'id');
    }
    public function innings_batter(){
        return $this->hasMany(InningBatterResult::class, 'tournament_id', 'id');
    }
    public function innings_bowler(){
        return $this->hasMany(InningBowlerResult::class, 'tournament_id', 'id');
    }
    public function fixtures(){
        return $this->hasMany(Fixture::class, 'tournament_id', 'id');
    }

    public function deliveries(){
        return $this->hasMany(Delivery::class, 'tournament_id', 'id');
    }

    public function bestPlayerBattings(){
        return $this->hasMany(InningBatterResult::class, 'tournament_id');
    }

    public function bestPlayerBowlings(){
        return $this->hasMany(InningBowlerResult::class, 'tournament_id');
    }

    public function awardLike(){
        return $this->hasMany(AwardLike::class);
    }

}
