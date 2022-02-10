<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fixture extends Model
{
    use HasFactory;

    // protected $casts = [
    //     'match_da'te' => 'date:D,F d',
    //     'start_time' => 'datetime:h,i a'
    // ];


    protected $fillable = [
        'match_no',
        'tournament_id',
        'league_group_id',
        'additional_group_id',
        'round_type',
        'round_name',
        'power_play',
        'match_type',
        'knockout_round',
        'group_round',
        'fixture_type',
        'is_break',
        'events',
        'ground_id',
        'match_overs',
        'overs_per_bowler',
        'ball_type',
        'match_date',
        'start_time',
        'settings',

        'home_team_id',
        'away_team_id',

        'toss_winner_team_id',
        'team_elected_to',

        'match_winner_team_id',
        'match_loser_team_id',
        'is_live',
        'is_match_start',
        'is_match_finished',
        'is_match_postponed',
        'is_match_cancelled',
        'match_final_result',

        'match_overs',

        'home_team_overs',
        'away_team_overs',

        'home_team_runs',
        'away_team_runs',

        'home_team_wickets',
        'away_team_wickets',

        'player_of_the_match',

        'temp_team_one',
        'temp_team_two',
        'temp_team_one_name',
        'temp_team_two_name',
    ];

    protected $casts = ['settings' => 'array'];

    public function teamPlayers(){
        return $this->hasMany(TeamPlayer::class, 'team_id', 'home_team_id');
    }
    public function playingElevens(){
        return $this->hasMany(PlayingEleven::class);
    }
    public function innings(){
        return $this->hasMany(Inning::class, 'fixture_id')->orderBy('is_first_innings','desc');
    }
    public function winner_innings(){
        return $this->hasMany(Inning::class,'batting_team_id', 'match_winner_team_id');
    }
    public function powerplay(){
        return $this->hasMany(MatchPowerPlay::class, 'fixture_id');
    }

    public function home_team_innings(){
        return $this->hasMany(Inning::class, 'batting_team_id', 'home_team_id');
    }

    public function bestPlayerBatting(){
        return $this->hasOne(InningBatterResult::class);
    }

    public function bestPlayerBowling(){
        return $this->hasOne(InningBowlerResult::class);
    }

    public function away_team_innings(){
        return $this->hasMany(Inning::class, 'batting_team_id', 'away_team_id');
    }

    public function innings_batter(){
        return $this->hasMany(InningBatterResult::class, 'batter_id', 'player_of_the_match',);
    }

    public function innings_bowler(){
        return $this->hasMany(InningBowlerResult::class, 'bowler_id', 'player_of_the_match');
    }

    public function caught_by(){
        return $this->hasMany(Delivery::class, 'caught_by', 'player_of_the_match');
    }

    public function wicket_by(){
        return $this->hasMany(Delivery::class, 'wicket_by', 'player_of_the_match');
    }

    public function referees()
    {
        return $this->hasMany(MatchOfficial::class, 'fixture_id');

    }
    public function playerOftheMatch(){
        return $this->belongsTo(User::class, 'player_of_the_match', 'id');
     }

    public function home_team()
    {
        return $this->belongsTo(Team::class, 'home_team_id')->select('id', 'team_name', 'team_logo','team_short_name');

    }

    public function away_team()
    {
        return $this->belongsTo(Team::class, 'away_team_id')->select('id', 'team_name', 'team_logo','team_short_name');
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function ground(): BelongsTo
    {
        return $this->belongsTo(Ground::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function tossWinnerTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'toss_winner_team_id');
    }

    public function winnerTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'match_winner_team_id');
    }

    public function loserTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'match_loser_team_id');
    }

    public function umpires(): BelongsToMany
    {
        return $this
        ->belongsToMany(User::class, 'match_officials')
        ->where('official_type', 'UMPIRE')
        ->orderBy('position');
    }

    public function scorers(): BelongsToMany
    {
        return $this
        ->belongsToMany(User::class, 'match_officials')
        ->where('official_type', 'SCORER')
        ->orderBy('position');
    }

    public function othersOfficials(): BelongsToMany
    {
        return $this
        ->belongsToMany(User::class, 'match_officials')
        ->where('official_type', 'COMMENTATOR')
        ->orWhere('official_type', 'REFEREE')
        ->orWhere('official_type', 'STREAMER')
        ->withPivot('official_type');
    }

    public function leagueGroup(): BelongsTo
    {
        return $this->belongsTo(LeagueGroup::class, 'league_group_id');
    }

    public function awardLike(){
        return $this->hasMany(AwardLike::class);
    }


}
