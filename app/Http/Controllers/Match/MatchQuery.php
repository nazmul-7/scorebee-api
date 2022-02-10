<?php

namespace App\Http\Controllers\Match;

use App\Models\MatchRank;
use App\Models\MatchOfficial;
use App\Models\MatchPowerPlay;
use App\Models\User;
use App\Models\Team;
use App\Models\Over;
use App\Models\Inning;
use App\Models\Panalty;
use App\Models\Fixture;
use App\Models\TeamPlayer;
use App\Models\FixtureEvent;
use App\Models\FieldCoordinate;
use App\Models\Delivery;
use App\Models\WicketFall;
use App\Models\Tournament;
use App\Models\FixtureMedia;
use App\Models\PlayingEleven;
use App\Models\LeagueGroupTeam;
use App\Models\InningBatterResult;
use App\Models\InningBowlerResult;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Log;
use PhpParser\Node\Expr\Cast\Double;

class MatchQuery
{
    public function getInningsPowerPlay($inning_id){
        return MatchPowerPlay::where('inning_id', $inning_id)->get();
    }
    public function getChallengedMatchesListQuery($data)
    {
        $limit = $data['limit'] ?? 10;

        return Fixture::select(
            'id',
            'match_date',
            'start_time',
            'is_match_start',
            'is_match_finished',
            'home_team_id',
            'away_team_id',
            'fixture_type'
        )
            ->where(function ($query) use ($data) {
                $query
                    ->whereHas('homeTeam', function ($query) use ($data) {
                        $query->where('owner_id', $data['club_owner_id']);
                    })
                    ->orWhereHas('awayTeam', function ($query) use ($data) {
                        $query->where('owner_id', $data['club_owner_id']);
                    });
            })
            ->where(function ($q) {
                $q
                    ->where('fixture_type', 'TEAM_CHALLENGE')
                    ->orWhere('fixture_type', 'CLUB_CHALLENGE');
            })
            ->with('homeTeam:id,team_name,team_logo')
            ->with('awayTeam:id,team_name,team_logo')
            ->orderByDesc('match_date')
            ->orderByDesc('start_time')
            ->paginate($limit);
    }

    public function getMatchesByRoundQuery($tournament_id, $round_type)
    {
        return Fixture::select(
            'id',
            'match_date',
            'start_time',
            'is_match_start',
            'is_match_finished',
            'home_team_id',
            'away_team_id',
            'temp_team_one_name',
            'temp_team_two_name',
        )
            ->where('tournament_id', $tournament_id)
            ->where('round_type', $round_type)
            ->with('homeTeam:id,team_name,team_logo')
            ->with('awayTeam:id,team_name,team_logo')
            ->orderByRaw("match_date != '1111-11-11' DESC")
            ->orderByDesc('match_date')
            ->orderByDesc('start_time')
            ->orderBy('id')
            ->get();
    }

    public function getGeneratedMatchById($fixtureId)
    {
        return Fixture::select(
            'id',
            'home_team_id',
            'away_team_id',
        )
            ->where('id', $fixtureId)
            ->with('homeTeam:id,team_name,team_logo')
            ->with('awayTeam:id,team_name,team_logo')
            ->first();
    }

    public function getMyMatchesListQuery($data)
    {
        $userId = $data['user_id'] ?? null;
        $userType = $data['user_type'] ?? null;
        $teamId = $data['team_id'] ?? null;
        $matchOvers = $data['match_overs'] ?? null;
        $ballType = $data['ball_type'] ?? null;
        $year = $data['year'] ?? null;
        $matchType = $data['match_type'] ?? null;
        $tournamentId = $data['tournament_id'] ?? null;
        $tournamentCategory = $data['tournament_category'] ?? null;
        $limit = $data['limit'] ?? 10;
        $isMatchStart = $data['is_match_start'] ?? 0;
        $isMatchFinished = $data['is_match_finished'] ?? 0;
        $status = $data['status'] ?? null;
        $match_status = $data['match_status'] ?? null;
        // return $match_status;
        return Fixture::select(
            'id', 'tournament_id', 'league_group_id', 'ground_id',
            'round_type', 'match_no', 'match_overs',
            'match_date', 'start_time', 'toss_winner_team_id', 'team_elected_to',
            'is_match_start', 'is_match_finished', 'match_winner_team_id', 'match_final_result',
            'home_team_id', 'home_team_runs', 'home_team_overs', 'home_team_wickets',
            'away_team_id', 'away_team_runs', 'away_team_overs', 'away_team_wickets',
            'temp_team_one', 'temp_team_two', 'temp_team_one_name', 'temp_team_two_name',
        )
            ->when($match_status, function ($q) use ($isMatchStart, $isMatchFinished, $match_status) {
                $q->where(function ($query) use ($isMatchStart, $isMatchFinished) {
                    $query->where('is_match_start', $isMatchStart)->where('is_match_finished', $isMatchFinished);
                });
                $q->when($match_status == "UPCOMING", function($q){
                    $q->orderBy('match_date')->orderby('id');
                });
                $q->when($match_status == "LIVE", function($q){
                    $q->orderBy('match_date')->orderby('start_time');
                });
                $q->when($match_status == "RECENT", function($q){
                    $q->orderBy('match_date')->orderby('start_time');
                });
            })

            ->when($teamId, function ($query) use ($teamId) {
                $query->where(function ($query) use ($teamId) {
                    $query->where('home_team_id', $teamId)
                        ->orWhere('away_team_id', $teamId);
                });
            })

            ->with(['teamPlayers' => function ($q) use ($userId) {
                $q->where('player_id', $userId);
            }])

            ->when($status === 'my_match', function ($q) use ($userId, $userType, $match_status) {

                $q->when($userType === 'PLAYER', function ($q) use ($userId, $match_status) {

                        //Confused
                    $q->when($match_status === "UPCOMING", function($q2) use($userId){

                        $q2->whereIn('home_team_id', function ($q3) use ($userId) {
                            $q3->select('team_id')->from('team_players')
                                ->where('player_id', $userId);
                        });

                        $q2->orWhereIn('away_team_id', function ($q3) use ($userId) {
                            $q3->select('team_id')->from('team_players')
                                ->where('player_id', $userId);
                        });
                    });

                    $q->when($match_status !== "UPCOMING", function ($q) use ($userId) {
                        $q->whereHas('playingElevens', function (Builder $query) use ($userId) {
                            $query->where('player_id', $userId);
                        });
                    });
                })

                    ->when($userType === 'ORGANIZER', function ($q) use ($userId) {
                        $q->whereHas('tournament', function (Builder $query) use ($userId) {
                            $query->where('organizer_id', $userId);
                        });
                    })

                    ->when($userType === 'CLUB_OWNER', function ($q) use ($userId) {
                        $q->wherehas('home_team', function ($query) use ($userId) {
                            $query->where('owner_id', $userId);
                        });
                        $q->orWherehas('away_team', function ($query) use ($userId) {
                            $query->where('owner_id', $userId);
                        });
                    })
                    ->when(
                        $userType === 'UMPIRE' ||
                        $userType === 'COMMENTATOR' ||
                        $userType === 'SCORER' ||
                        $userType === 'STREAMER' ||
                        $userType === 'REFEREE',
                        function ($q) use($userId) {
                        $q->wherehas('referees', function ($query) use ($userId) {
                            $query->where('user_id', $userId);
                        });
                    });
                })
            ->when($matchOvers, function ($query) use ($matchOvers) {
                $query->where('match_overs', $matchOvers);
            })
            ->when($ballType, function ($query) use ($ballType) {
                $query->where('ball_type', $ballType);
            })
            ->when($year, function ($query) use ($year) {
                $query->whereYear('match_date', $year);
            })
            ->when($matchType, function ($query) use ($matchType) {
                $query->where('match_type', $matchType);
            })
            ->when($tournamentId, function ($query) use ($tournamentId) {
                $query->where('tournament_id', $tournamentId);
            })
            ->when($tournamentCategory, function ($query) use ($tournamentCategory) {
                $query->whereHas('tournament', function ($query) use ($tournamentCategory) {
                    $query->where('tournament_category', $tournamentCategory);
                });
            })
            ->with('homeTeam:id,team_name,team_logo')
            ->with('awayTeam:id,team_name,team_logo')
            ->with('innings')
            ->withCount('innings AS total_innings')
            ->orderByRaw("is_match_start - is_match_finished DESC")
            ->orderBy('is_match_finished')
            ->orderByRaw("match_date != '1111-11-11' DESC")
            ->orderByDesc('match_date')
            ->orderByDesc('start_time')
            ->paginate($limit);
    }

    public function getMyMatchesListByStatus($data)
    {
        $userId = $data['user_id'] ?? null;
        $userType = $data['user_type'] ?? null;
        $teamId = $data['team_id'] ?? null;
        $matchOvers = $data['match_overs'] ?? null;
        $ballType = $data['ball_type'] ?? null;
        $year = $data['year'] ?? null;
        $matchType = $data['match_type'] ?? null;
        $tournamentId = $data['tournament_id'] ?? null;
        $tournamentCategory = $data['tournament_category'] ?? null;
        $limit = $data['limit'] ?? 10;
        $isMatchStart = $data['is_match_start'] ?? 0;
        $isMatchFinished = $data['is_match_finished'] ?? 0;
        $status = $data['status'] ?? null;
        $match_status = $data['match_status'] ?? null;
        // return $match_status;
        return Fixture::select(
            'id', 'tournament_id', 'league_group_id', 'ground_id',
            'round_type', 'match_no', 'match_overs',
            'match_date', 'start_time', 'toss_winner_team_id', 'team_elected_to',
            'is_match_start', 'is_match_finished', 'match_winner_team_id', 'match_final_result',
            'home_team_id', 'home_team_runs', 'home_team_overs', 'home_team_wickets',
            'away_team_id', 'away_team_runs', 'away_team_overs', 'away_team_wickets',
            'temp_team_one', 'temp_team_two', 'temp_team_one_name', 'temp_team_two_name',
        )
            ->when($match_status, function ($q) use ($isMatchStart, $isMatchFinished) {
                $q->where(function ($query) use ($isMatchStart, $isMatchFinished) {
                    $query->where('is_match_start', $isMatchStart)->where('is_match_finished', $isMatchFinished);
                });
            })

            ->when($teamId, function ($query) use ($teamId) {
                $query->where(function ($query) use ($teamId) {
                    $query->where('home_team_id', $teamId)
                        ->orWhere('away_team_id', $teamId);
                });
            })
            ->with(['teamPlayers' => function ($q) use ($userId) {
                $q->where('player_id', $userId);
            }])

            ->when($status === 'my_match', function ($q) use ($userId, $userType, $match_status) {

                $q->when($userType === 'PLAYER', function ($q) use ($userId, $match_status) {

                        //Confused
                    $q->when($match_status === "UPCOMING", function($q2) use($userId){

                        $q2->whereIn('home_team_id', function ($q3) use ($userId) {
                            $q3->select('team_id')->from('team_players')
                                ->where('player_id', $userId);
                        });

                        $q2->orWhereIn('away_team_id', function ($q3) use ($userId) {
                            $q3->select('team_id')->from('team_players')
                                ->where('player_id', $userId);
                        });
                    });

                    $q->when($match_status !== "UPCOMING", function ($q) use ($userId) {
                        $q->whereHas('playingElevens', function (Builder $query) use ($userId) {
                            $query->where('player_id', $userId);
                        });
                    });
                })

                    ->when($userType === 'ORGANIZER', function ($q) use ($userId) {
                        $q->whereHas('tournament', function (Builder $query) use ($userId) {
                            $query->where('organizer_id', $userId);
                        });
                    })

                    ->when($userType === 'CLUB_OWNER', function ($q) use ($userId) {
                        $q->wherehas('home_team', function ($query) use ($userId) {
                            $query->where('owner_id', $userId);
                        });
                        $q->orWherehas('away_team', function ($query) use ($userId) {
                            $query->where('owner_id', $userId);
                        });
                    })
                    ->when(
                        $userType === 'UMPIRE' ||
                        $userType === 'COMMENTATOR' ||
                        $userType === 'SCORER' ||
                        $userType === 'STREAMER' ||
                        $userType === 'REFEREE',
                        function ($q) use($userId) {
                        $q->wherehas('referees', function ($query) use ($userId) {
                            $query->where('user_id', $userId);
                        });
                    });
                })
            ->when($matchOvers, function ($query) use ($matchOvers) {
                $query->where('match_overs', $matchOvers);
            })
            ->when($ballType, function ($query) use ($ballType) {
                $query->where('ball_type', $ballType);
            })
            ->when($year, function ($query) use ($year) {
                $query->whereYear('match_date', $year);
            })
            ->when($matchType, function ($query) use ($matchType) {
                $query->where('match_type', $matchType);
            })
            ->when($tournamentId, function ($query) use ($tournamentId) {
                $query->where('tournament_id', $tournamentId);
            })
            ->when($tournamentCategory, function ($query) use ($tournamentCategory) {
                $query->whereHas('tournament', function ($query) use ($tournamentCategory) {
                    $query->where('tournament_category', $tournamentCategory);
                });
            })
            ->with('homeTeam:id,team_name,team_logo')
            ->with('awayTeam:id,team_name,team_logo')
            ->with('innings')
            ->withCount('innings AS total_innings')
            ->orderByRaw("is_match_start - is_match_finished DESC")
            ->orderBy('is_match_finished')
            ->orderByRaw("match_date != '1111-11-11' DESC")
            ->orderByDesc('match_date')
            ->orderByDesc('start_time')
            ->paginate($limit);
    }

    public function getAllMatchesListV2Query($type, $limit = 5, $timezone){

        $currentDatetime = Carbon::now($timezone)->format('Y-m-d');
        return Fixture::select(
                'id', 'tournament_id', 'league_group_id', 'ground_id',
                'round_type', 'fixture_type', 'match_no', 'match_overs',
                'match_date', 'start_time', 'toss_winner_team_id', 'team_elected_to',
                'is_match_start', 'is_match_finished', 'match_winner_team_id', 'match_final_result',
                'home_team_id', 'home_team_runs', 'home_team_overs', 'home_team_wickets',
                'away_team_id', 'away_team_runs', 'away_team_overs', 'away_team_wickets',
                'temp_team_one', 'temp_team_two', 'temp_team_one_name', 'temp_team_two_name',
            )
            ->with('homeTeam:id,team_name,team_logo')
            ->with('awayTeam:id,team_name,team_logo')
            ->when($type === 'LIVE', function($q){
                $q
                ->addSelect(\DB::raw("'LIVE' AS type"))
                ->where('is_match_start', 1)
                ->where('is_match_finished', 0)
                ->with('innings')
                ->withCount('innings AS total_innings')
                ->orderByDesc('match_date')
                ->orderByDesc('start_time');
            })
            ->when($type === 'UPCOMING', function($q){
                $q
                ->addSelect(\DB::raw("'UPCOMING' AS type"))
                ->where('is_match_start', 0)
                ->where('is_match_finished', 0)
                ->where(function($q){
                    $q
                    ->whereDate('match_date', '>=', now())
                    ->orWhere('match_date', '1111-11-11');
                })
                ->where('is_match_postponed', 0)
                ->where('is_match_cancelled', 0)
                ->orderByRaw("match_date != '1111-11-11' DESC")
                ->orderBy('match_date')
                ->orderBy('start_time')
                ->orderBy('id');
            })
            ->when($type === 'RECENT', function($q) use($currentDatetime){
                $q
                ->addSelect(\DB::raw("'RECENT' AS type"))
                ->where(function($q){
                    $q->where('is_match_start', 1)
                    ->where('is_match_finished', 1);
                })
                ->orWhere(function($q) use($currentDatetime){
                    $q
                        ->whereDate('match_date', '<', $currentDatetime)
                        ->where('match_date', '!=', '1111-11-11');
                })
                ->orWhere('is_match_postponed', 1)
                ->orWhere('is_match_cancelled', 1)
                ->orderByDesc('match_date')
                ->orderByDesc('start_time');
            })
            ->limit($limit)
            ->get();
    }

    public function getAllMatchesListQuery($data)
    {
        return Fixture::select(
            'id',
            'match_no',
            'tournament_id',
            'fixture_type',
            'round_type',
            'match_overs',
            'match_date',
            'start_time',
            'home_team_id',
            'away_team_id',
            'tournament_id',
            'league_group_id',
            'toss_winner_team_id',
            'team_elected_to',
            'is_match_start',
            'toss_winner_team_id',
            'team_elected_to',
            'is_match_finished',
            'match_winner_team_id',
            'match_final_result',
            'home_team_runs',
            'home_team_overs',
            'home_team_wickets',
            'away_team_runs',
            'away_team_overs',
            'away_team_wickets',
            'temp_team_one_name',
            'temp_team_two_name',
        )
            ->where('is_match_finished', 0)
            ->with('tournament:id,tournament_name')
            ->with('leagueGroup:id,league_group_name')
            ->with('homeTeam:id,team_name,team_logo')
            ->with('awayTeam:id,team_name,team_logo')
            ->withCount('innings AS total_innings')
            ->with('innings:id,fixture_id,innings_status,is_first_innings')
            ->orderByDesc('is_match_start')
            ->orderByRaw("match_date != '1111-11-11' DESC")
            ->orderByDesc('match_date')
            ->orderByDesc('start_time')
            ->limit(5)
            ->get();
    }

    public function getAllLiveMatchesListQuery($data)
    {
        $limit = $data['limit'] ?? 10;

        return Fixture::select(
            'id',
            'match_no',
            'tournament_id',
            'fixture_type',
            'round_type',
            'match_overs',
            'match_date',
            'start_time',
            'home_team_id',
            'away_team_id',
            'tournament_id',
            'league_group_id',
            'is_match_start',
            'toss_winner_team_id',
            'team_elected_to',
            'match_final_result',
            'home_team_runs',
            'home_team_overs',
            'home_team_wickets',
            'away_team_runs',
            'away_team_overs',
            'away_team_wickets',
        )
            ->where('is_match_start', 1)
            ->where('is_match_finished', 0)
            ->with('tournament:id,tournament_name')
            ->with('leagueGroup:id,league_group_name')
            ->with('homeTeam:id,team_name,team_logo')
            ->with('awayTeam:id,team_name,team_logo')
            ->with('ground:id,ground_name,city')
            ->with('innings')
            ->withCount('innings AS total_innings')
            ->orderByDesc('match_date')
            ->orderByDesc('start_time')
            ->paginate($limit);
    }

    public function getMatchesListByTypeQuery($data)
    {
        $timezone = $data['timezone'] ?? 'Asia/Dhaka';
        $currentDatetime = Carbon::now($timezone)->format('Y-m-d');
        $isMatchStart = $data['is_match_start'];
        $isMatchFinished = $data['is_match_finished'];
        $matchType = $data['match_type'];
        $limit = $data['limit'] ?? 10;

        return Fixture::select(
            'id',
            'match_no',
            'tournament_id',
            'round_type',
            'fixture_type',
            'match_overs',
            'match_date',
            'start_time',
            'home_team_id',
            'away_team_id',
            'tournament_id',
            'league_group_id',
            'toss_winner_team_id',
            'team_elected_to',
            'toss_winner_team_id',
            'team_elected_to',
            'is_match_start',
            'is_match_finished',
            'is_match_postponed',
            'is_match_cancelled',
            'match_winner_team_id',
            'match_final_result',
            'home_team_runs',
            'home_team_overs',
            'home_team_wickets',
            'away_team_runs',
            'away_team_overs',
            'away_team_wickets',
            'temp_team_one_name',
            'temp_team_two_name',
        )
            ->where(function($q) use($isMatchStart, $isMatchFinished){
                $q
                ->where('is_match_start', $isMatchStart)
                ->where('is_match_finished', $isMatchFinished);
            })
            ->with('tournament:id,tournament_name')
            ->with('leagueGroup:id,league_group_name')
            ->with('homeTeam:id,team_name,team_logo')
            ->with('awayTeam:id,team_name,team_logo')
            ->when($data['match_type'] == 'UPCOMING', function ($q) use($currentDatetime) {
                $q
                    ->where(function($q) use($currentDatetime){
                        $q
                        ->whereDate('match_date', '>=', $currentDatetime)
                        ->orWhere('match_date', '1111-11-11');
                    })
                    ->where('is_match_postponed', 0)
                    ->where('is_match_cancelled', 0)
                    ->orderByRaw("match_date != '1111-11-11' DESC")
                    ->orderBy('match_date')
                    ->orderBy('start_time')
                    ->orderBy('id');
            })
            ->when($data['match_type'] == 'RECENT', function ($q) use($currentDatetime) {
                $q
                    ->orWhere(function($q) use($currentDatetime) {
                        $q
                            ->whereDate('match_date', '<', $currentDatetime)
                            ->where('match_date', '!=', '1111-11-11');
                    })
                    ->orWhere('is_match_postponed', 1)
                    ->orWhere('is_match_cancelled', 1)
                    ->orderByDesc('match_date')
                    ->orderByDesc('start_time');
            })
            ->paginate($limit);
    }

    public function getAllMatchesByGroupQuery($gId, $data)
    {

        $q = Fixture::where('league_group_id', $gId)
            ->select('id', 'is_match_finished', 'league_group_id', 'match_no', 'ground_id', 'home_team_id', 'away_team_id', 'match_date', 'match_final_result', 'start_time', 'is_match_start')
            ->with('away_team')
            ->with('home_team')
            ->with('leagueGroup')
            ->with('ground:id,city')
            ->orderByRaw("match_date != '1111-11-11' DESC")
            ->orderByDesc('match_date')
            ->orderByDesc('start_time')
            ->orderBy('id');

        if (isset($data['date']) && $data['date']) {
            $q->whereDate('match_date', $data['date']);
        }

        // if(isset($data['last_id']) && $data['last_id']){
        //   $q->whereDate('id','<',$data['last_id']);
        // }
        // $allcollections =  $q->limit(15)->get();
        $allcollections = $q->get();

        $grouped = $allcollections->groupBy('match_date');

        return $grouped;
    }

    public function getTournamentMatches($data)
    {
        $tId = $data['id'];
        $teamId = $data['team_id'] ?? null;

        $q = Fixture::where('tournament_id', $tId)->select(
            'id',
            'match_no',
            'league_group_id',
            'ground_id',
            'home_team_id',
            'away_team_id',
            'match_date',
            'match_final_result',
            'start_time',
            'is_match_start',
            'is_match_finished',
            'home_team_runs',
            'away_team_runs',
            'home_team_overs',
            'away_team_overs',
            'home_team_wickets',
            'away_team_wickets',
            'temp_team_one_name',
            'temp_team_two_name',
            'round_type',
        )
            ->with('away_team')->with('home_team')->with('ground:id,city')->with('leagueGroup:id,league_group_name')
            ->when($teamId, function ($q) use ($teamId) {
                $q->where('home_team_id', $teamId);
                $q->orWhere('away_team_id', $teamId);
            });


        $allcollections =
        $q->orderByDesc('match_date')
        ->orderBy('id')
        ->Paginate(10);


        $grouped = $allcollections->groupBy('match_date');
        return $grouped;
    }

    public function getTournamentMatchesByStatus($data)
    {
        $tId = $data['id'];
        $teamId = $data['team_id'] ?? null;
        $status = $data["status"] ?? null;
        // return $status;
        $fixtures = Fixture::where('tournament_id', $tId)->select(
                'id',
                'match_no',
                'league_group_id',
                'ground_id',
                'home_team_id',
                'away_team_id',
                'match_date',
                'match_final_result',
                'start_time',
                'is_match_start',
                'is_match_finished',
                'home_team_runs',
                'away_team_runs',
                'home_team_overs',
                'away_team_overs',
                'home_team_wickets',
                'away_team_wickets',
                'temp_team_one_name',
                'temp_team_two_name',
                'round_type',
            )
            ->with('away_team')->with('home_team')
            ->with('ground:id,city')
            ->with('leagueGroup:id,league_group_name')

            ->when($status == "LIVE", function($q){
                $q->where('is_match_start', 1)->where('is_match_finished', 0)
                 ->orderBy('match_date')->orderby('start_time');
            })
            ->when($status === "RECENT", function($q){
                $q->where('is_match_start', 1);
                $q->where('is_match_finished', 1);
                $q->orderBy('match_date')->orderby('start_time');
            })
            ->when($status === "UPCOMING", function($q){
                $q->where('is_match_start', 0);
                $q->where('is_match_finished', 0);
                $q->orderBy('match_date')->orderby('id');
            })

            ->when($teamId, function ($q) use ($teamId) {
                $q->where('home_team_id', $teamId);
                $q->orWhere('away_team_id', $teamId);
            })
            ->Paginate(10);


        $grouped = $fixtures->groupBy('match_date');
        return $grouped;
    }

    public function getSingleMatchQuery($id)
    {
        return Fixture::where('id', $id)
            ->select(
                'id',
                'match_type',
                'match_overs',
                'match_date',
                'toss_winner_team_id',
                'team_elected_to',
                'ground_id',
                'power_play',
                'settings',
                'overs_per_bowler',
                'events'
            )
            ->with('referees')->with('away_team')->with('home_team')->with('powerplay')
            ->first();
    }

    public function getMatchOfficialQuery($type, $str)
    {
        $q = User::where('registration_type', $type)->select('id', 'first_name', 'last_name', 'gender', 'registration_type');
        if ($str) {
            $q->where('first_name', 'like', '%' . $str . '%')
                ->orWhere('last_name', 'like', '%' . $str . '%');
        }


        return $q->get();
    }

    public function updateMatchQuery($data)
    {
        return Fixture::where('id', $data['id'])->update($data);
    }

    public function endInningsQuery($data)
    {
        return Inning::where('id', $data['id'])->update($data);
    }

    public function getEditableMatchDetailsQuery($data)
    {
        return Fixture::select('id', 'match_type', 'match_overs', 'match_no', 'overs_per_bowler', 'ground_id', 'match_date', 'start_time')
            ->where('id', $data['fixture_id'])
            // ->where('is_match_start', 0)
            ->where('is_match_finished', 0)
            ->with('ground')
            ->with(['umpires:id,first_name,last_name,profile_pic', 'scorers:id,first_name,last_name,profile_pic', 'othersOfficials:id,first_name,last_name,profile_pic'])
            ->with('powerplay')
            ->first();
    }

    public function updateEditableMatchDetailsQuery($fId, $uId, $data)
    {
        // Log::channel('slack')->info('data', ['d' => $data]);
        return Fixture::where('id', $fId)
            // ->where('is_match_start', 0)
            ->where('is_match_finished', 0)
            // ->whereHas('tournament', function ($query) use ($uId) {
            //     $query->where('organizer_id', $uId);
            // })
            ->update($data);
    }

    public function updateMatchPowerPlaysQuery($fixtureId, $inningsId = null, $data = null)
    {
        if (!isset($inningsId) and isset($data)) {
            // Log::channel('slack')->info('first');
            MatchPowerPlay::where('fixture_id', $fixtureId)->delete();

            foreach ($data as $value) {
                $attributes = [
                    'fixture_id' => $fixtureId,
                    'inning_id' => 0,
                    'type' => $value['type'],
                    'start' => $value['start'],
                    'end' => $value['end'],
                ];

                MatchPowerPlay::create($attributes);
                MatchPowerPlay::create($attributes);
            }
        } else if (!isset($inningsId) and !isset($data)) {
            // Log::channel('slack')->info('second');
            $innings = Inning::where('fixture_id', $fixtureId)->pluck('id');

            $inningsPowerPlays = MatchPowerPlay::where('fixture_id', $fixtureId)
                ->where('inning_id', 0)
                ->get();

            $items = $inningsPowerPlays->groupBy('type');

            foreach ($items as $item) {
                $item[0]->update(['inning_id' => $innings[0]]);
                $item[1]->update(['inning_id' => $innings[1]]);
            }
        } else if (isset($inningsId)) {
            // Log::channel('slack')->info('third');
            foreach ($data as $value) {
                MatchPowerPlay::updateOrCreate(
                    [
                        'fixture_id' => $fixtureId,
                        'inning_id' => $inningsId,
                        'type' => $value['type'],
                    ],
                    [
                        'start' => $value['start'],
                        'end' => $value['end'],
                    ]
                );
            }
        }

        return 1;
    }

    public function addMatchOfficialQuery($data)
    {
        return MatchOfficial::updateOrCreate(
            [
                'official_type' => $data['official_type'],
                'position' => $data['position'],
            ],
            $data
        );

        //  return MatchOfficial::where('id', $user['id'])->with('user')->first();
    }

    public function getMatchOfficial_by_fixtureQuery($id)
    {
        return MatchOfficial::where('fixture_id', $id)->with('user')->get();
    }

    public function addMatchEventQuery($data)
    {
        return FixtureEvent::create($data);
    }

    public function addMatchTossQuery($data)
    {
        return Fixture::where('id', $data['id'])->update($data);
    }

    public function removeMatchOfficialQuery($data)
    {
        return MatchOfficial::where('id', $data['id'])->delete();
    }

    public function isMatchOwnerQuery($uId, $fId)
    {
        return Fixture::where('id', $fId)->first();
    }

    public function singleMatchInfo($fId)
    {
        return Fixture::where('id', $fId)->withCount([
            'innings as innings_begin' => function($q){
                $q->where(function($q){
                    $q->where('is_first_innings', 1)
                    ->whereNotIn('innings_status', ['Started', 'Finished']);
                });
            },
            'innings as innings_break' => function($q){
                $q->where(function($q){
                    $q->where('is_first_innings', 0)
                    ->whereNotIn('innings_status', ['Started', 'Finished']);
                });
            },
        ])->with('tossWinnerTeam')->first();
    }

    public function getRunningMatchById($fixtureId){
        return Fixture::where('id', $fixtureId)
        ->with('tossWinnerTeam')
        ->with('innings')
        ->withCount('innings AS total_innings')
        ->first();
    }

    public function getLeagueGroupTeamsQuery($lId, $tId)
    {
        return LeagueGroupTeam::where('league_group_id', $lId)->where('team_id', $tId)->first();
    }

    public function createInningsQuery($data)
    {
        return Inning::updateOrCreate(
            [
                'tournament_id' => $data['tournament_id'],
                'league_group_id' => $data['league_group_id'],
                'fixture_id' => $data['fixture_id'],
                'batting_team_id' => $data['batting_team_id'],
            ],
            [
                'home_team_id' => $data['home_team_id'],
                'bowling_team_id' => $data['bowling_team_id'],
                'away_team_id' => $data['away_team_id'],
                'is_first_innings' => $data['is_first_innings'],
                'league_group_team_id' => $data['league_group_team_id'],
                'league_group_bowling_team_id' => $data['league_group_bowling_team_id'],
            ]
        );
    }

    public function getInningsDetails($id, $isFixture = null)
    {
        if ($isFixture != null)
            return Inning::where('id', $id)->with('fixture.tournament')->first();

        return Inning::where('id', $id)->first();
    }

    public function checkInnings($id, $inning_number)
    {
        return Inning::where('fixture_id', $id)->with('batting_team')
        ->with(['previous_innings' => function ($q) use ($id) {
            $q->where('fixture_id', $id);
            $q->where('is_first_innings', 1);
            $q->where('innings_status', '=', 'Finished');
            $q->select('id', 'bowling_team_id', 'total_runs', 'total_overs', 'is_first_innings', 'total_wickets');
        }])
        ->where('is_first_innings', $inning_number)->first();
    }

    public function getAnotherInningsDetails($id)
    {
        return Inning::where('fixture_id', $id)->where('is_first_innings', 1)->first();
    }

    public function getMatchInningsQuery($fixture_id)
    {
        return Inning::where('fixture_id', $fixture_id)->with('batting_team', 'bowling_team')->orderBy('id', 'asc')->get();
    }

    public function getPanaltyOrBonusRunsQuery($data)
    {
        return Panalty::select('id', 'team_id', 'runs', 'reason')
        ->with('team:id,team_name,team_short_name,team_logo')
        ->where('inning_id', $data['innings_id'])
        ->where('type', $data['type'])
        ->orderBy('id', 'desc')
        ->get();
    }

    public function removePanaltyOrBonusRunsQuery($data)
    {
        return Panalty::where('id', $data['id'])->delete();
    }

    public function storePanalty($data)
    {
        return Panalty::create($data);
    }

    public function updateInnings($data)
    {
        return Inning::where('id', $data['id'])->update($data);
    }

    public function createOver($data)
    {


        // Over::select()->where('inning_id',$data['inning_id'])->count();
        $over_number = Over::select(DB::raw('count(DISTINCT over_number) as total'))->where('inning_id', $data['inning_id'])->first();
        $data['over_number'] = $over_number->total + 1;

        return Over::create($data);
    }

    public function createReplaceOver($data)
    {


        // Over::select()->where('inning_id',$data['inning_id'])->count();
        $over_number = Over::select(DB::raw('count(DISTINCT over_number) as total'))->where('inning_id', $data['inning_id'])->first();
        $data['over_number'] = $over_number->total;

        return Over::create($data);
    }

    public function getOver($id)
    {
        return Over::where('inning_id', $id)->orderBy('id', 'desc')->first();
    }

    public function getPlyaingElevenQuery($data)
    {
        $q = PlayingEleven::where('fixture_id', $data['fixture_id'])->where('team_id', $data['team_id']);
        $str = isset($data['str']) ? $data['str'] : '';
        if ($str) {
            $q->with(['player' => function ($query) use ($str) {
                $query->select('id', 'first_name', 'last_name', 'profile_pic')
                    ->where('first_name', 'like', '%' . $str . '%')
                    ->orWhere('last_name', 'like', '%' . $str . '%');
            }]);
        } else {
            $q->with(['player' => function ($query) use ($str) {
                $query->select('id', 'first_name', 'last_name', 'profile_pic', 'playing_role', 'batting_style', 'bowling_style');
            }]);
        }
        return $q->get();
    }

    public function getAllPlayerOfMatchQuery($data)
    {
        $q = PlayingEleven::where('fixture_id', $data['fixture_id']);
        $str = isset($data['str']) ? $data['str'] : '';
        if ($str) {
            $q->with(['player' => function ($query) use ($str) {
                $query->select('id', 'first_name', 'last_name', 'profile_pic')
                    ->where('first_name', 'like', '%' . $str . '%')
                    ->orWhere('last_name', 'like', '%' . $str . '%');
            }]);
        } else {
            $q->with(['player' => function ($query) use ($str) {
                $query->select('id', 'first_name', 'last_name', 'profile_pic', 'playing_role', 'batting_style', 'bowling_style');
            }]);
        }
        return $q->get();
    }

    public function getBattingTeamWicketsLeft($fixtureId, $teamId){
        return PlayingEleven::where('fixture_id', $fixtureId)
            ->whereNotIn('player_id', function ($query) use($fixtureId, $teamId) {
                $query
                ->select('batter_id')
                ->from('inning_batter_results')
                ->where('team_id', $teamId)
                ->where('fixture_id', $fixtureId)
                ->where('is_out', 1);
            })
            ->where('team_id', $teamId)
            ->where('is_played', 1)
            ->count();
    }

    public function getPlyaingElevenWithSubs($data)
    {
        $q = PlayingEleven::where('fixture_id', $data['fixture_id'])->where('team_id', $data['team_id']);
        $str = isset($data['str']) ? $data['str'] : '';
        if ($str) {
            $q->with(['player' => function ($query) use ($str) {
                $query->select('id', 'first_name', 'last_name', 'profile_pic')
                    ->where('first_name', 'like', '%' . $str . '%')
                    ->orWhere('last_name', 'like', '%' . $str . '%');
            }]);
        } else {
            $q->with(['player' => function ($query) use ($str) {
                $query->select('id', 'first_name', 'last_name', 'profile_pic');
            }]);
        }
        return $q->get();
    }

    public function createNewBatterQuery($data)
    {
        $position = InningBatterResult::where('inning_id', $data['inning_id'])->count();
        $data['position'] = $position + 1;
        return InningBatterResult::create($data);
    }

    public function changeWicketkeeperQuery($data)
    {
        PlayingEleven::where('fixture_id', $data['fixture_id'])->where('team_id', $data['team_id'])->update([
            'is_wicket_keeper' => 0
        ]);
        return PlayingEleven::where('fixture_id', $data['fixture_id'])->where('team_id', $data['team_id'])->where('player_id', $data['player_id'])->update($data);
    }

    public function createNewBowlerQuery($data)
    {
        return InningBowlerResult::updateOrCreate(
            [
                'tournament_id' => $data['tournament_id'],
                'inning_id' => $data['inning_id'],
                'fixture_id' => $data['fixture_id'],
                'league_group_id' => $data['league_group_id'],
                'league_group_team_id' => $data['league_group_team_id'],
                'team_id' => $data['team_id'],
                'bowler_id' => $data['bowler_id'],
            ],
            [
                'is_on_strike' => 1
            ]
        );
        return InningBowlerResult::create($data);
    }

    public function updateNewBowlerQuery($data)
    {
        return InningBowlerResult::where('inning_id', $data['inning_id'])->update($data);
    }

    public function storeDeliveryQuery($data)
    {
        return Delivery::create($data);
    }

    public function updatetDeliveryQuery($data)
    {
        return Delivery::where('id', $data['id'])->update($data);
    }

    public function getSingleMatchWithAllDetailsQuery($fId)
    {

        return Fixture::select([
            'id', 'tournament_id', 'league_group_id', 'ground_id', 'home_team_id', 'away_team_id',
            'match_no', 'round_type', 'fixture_type', 'match_date', 'start_time',
            'toss_winner_team_id', 'team_elected_to', 'player_of_the_match',
        ])
            ->where('id', $fId)
            ->with('tournament', function($q){
                $q
                    ->select('id','tournament_type','tournament_name')
                    ->with('grounds')
                    ->withCount('grounds AS total_grounds');
            })
            ->with('tournament.grounds')
            ->with('ground:id,ground_name,city,capacity')
            ->with('homeTeam:id,team_name,team_short_name')
            ->with('awayTeam:id,team_name,team_short_name')
            ->with('leagueGroup:id,league_group_name')
            ->with('umpires:id,first_name,last_name')
            ->first();
    }

    public function getMatchLiveScoreQuery($iId)
    {
        $delivery = Delivery::select(DB::raw("SUM(extras + runs) as total_runs , SUM(CASE WHEN wicket_type IS NOT NULL THEN 1 ELSE 0 END) AS total_wicket , SUM(CASE WHEN ball_type = 'LEGAL' THEN 1 ELSE 0 END) AS total_over "))->where('inning_id', $iId)->first();

        return $delivery;
    }

    public function getMatchLiveBatsman($iId)
    {
        $delivery = InningBatterResult::select('batter_id', 'team_id', 'balls_faced', 'runs_achieved', 'is_on_strike')->with('batter', 'team')->where('inning_id', $iId)->where('is_out', 0)->whereNull('wicket_type')->limit(2)->orderBy('id', 'desc')->get();
        return $delivery;
    }

    public function getMatchLivebowler($iId)
    {
        $delivery = InningBowlerResult::select('bowler_id', 'team_id', 'overs_bowled', 'wickets', 'maiden_overs', DB::raw('(wide_balls+no_balls) as extra'), 'runs_gave')->with('bowler', 'team')->where('inning_id', $iId)->where('is_on_strike', 1)->first();
        return $delivery;
    }

    public function getLastMatchLivebowler($iId)
    {
        $delivery = InningBowlerResult::where('inning_id', $iId)->where('is_on_strike', 1)->first();
        return $delivery;
    }

    public function getLastMatchLivebatsman($iId, $batter_id)
    {
        $delivery = InningBatterResult::where('inning_id', $iId)->where('batter_id', $batter_id)->first();
        return $delivery;
    }

    public function storeFixtureMediaQuery($data)
    {
        return FixtureMedia::create($data);
    }

    public function getMatchLiveOver_details($over_number, $inning_id)
    {
        $delivery = Delivery::where('inning_id', $inning_id)->where('over_number', $over_number)->get();
        return $delivery;
    }

    public function singleMatchScoredQuery($id)
    {
        $fixture_id = isset($id) ? $id : 0;
        return Fixture::where('id', $fixture_id)
            ->select('id', 'match_winner_team_id', 'match_final_result', 'player_of_the_match', 'is_match_finished')
            ->with('playerOftheMatch:id,first_name,last_name,profile_pic')
            ->withSum(['innings_batter as best_player_runs' => function ($q) use ($fixture_id) {
                $q->where('fixture_id', $fixture_id);
            }], 'runs_achieved')
            ->withSum(['innings_batter as best_player_balls_faced' => function ($q) use ($fixture_id) {
                $q->where('fixture_id', $fixture_id);
            }], 'balls_faced')
            ->withSum(['innings_bowler as best_player_wickets' => function ($q) use ($fixture_id) {
                $q->where('fixture_id', $fixture_id);
            }], 'wickets')
            ->withSum(['innings_bowler as best_player_balls_bowled' => function ($q) use ($fixture_id) {
                $q->where('fixture_id', $fixture_id);
            }], 'balls_bowled')
            ->withSum(['innings_bowler as best_player_runs_gave' => function ($q) use ($fixture_id) {
                $q->where('fixture_id', $fixture_id);
            }], 'runs_gave')
            ->withCount(['caught_by as best_player_caught' => function ($q) use ($fixture_id) {
                $q->where('fixture_id', $fixture_id);
            }])
            ->withCount(['wicket_by as best_player_bowled_caught' => function ($q) use ($fixture_id) {
                $q->where('fixture_id', $fixture_id);
                $q->where('wicket_type', '=', 'CAUGHT_BOWLED');
            }])
            ->with(['innings' => function ($q) {
                $q->select('id', 'is_first_innings', 'fixture_id', 'total_runs', 'total_wickets', 'total_overs', 'batting_team_id');
                $q->with('batting_team');
            }])
            ->first();
    }


    public function totalLEGALBowl($over_id)
    {
        $delivery = Delivery::where('over_id', $over_id)->where('ball_type', 'LEGAL')->count();
        return $delivery;
    }

    public function totalBowlByOver($over_id, $inning_id)
    {
        $delivery = Delivery::where('over_id', $over_id)->count();
        $over = Over::where('inning_id', $inning_id)->count();
        return [
            'delivery_count' => $delivery,
            'over_count' => $over
        ];
    }

    public function getPreviousOverDeliveryAndDeleteCurrentOne($fixture_id, $inning_id, $over_id)
    {

        Over::where('id', $over_id)->delete();
        $previousOver = Over::where('inning_id', $inning_id)->orderBy('id', 'desc')->first();
        $previousBowler = InningBowlerResult::where('inning_id', $inning_id)->where('bowler_id', $previousOver->bowler_id)->first();
        InningBowlerResult::where('inning_id', $inning_id)->update([
            'is_on_strike' => 0,
        ]);
        InningBowlerResult::where('id', $previousBowler->id)->update([
            'is_on_strike' => 1,
        ]);
        return Delivery::where('fixture_id', $fixture_id)->where('over_id', $previousOver->id)->orderBy('id', 'desc')->first();
    }

    public function delDeliveryQuery($fixture_id, $over_id)
    {
        $delivery = Delivery::where('fixture_id', $fixture_id)->where('over_id', $over_id)->orderBy('id', 'desc')->first();
        if ($delivery) Delivery::where('id', $delivery->id)->delete();
        return $delivery;
    }

    public function singleTeamScoredQuery($data)
    {
        // $id = isset($data['team_id']) ? $data['team_id'] : 0;
        $inning_id = isset($data['inning_id']) ? $data['inning_id'] : 0;
        $singleInning = Inning::where('id', $inning_id)->select('id', 'fixture_id')->first();
        $fId = $singleInning->fixture_id;
        $inning = Inning::where('id', $inning_id)
            ->with(['inning_batter' => function ($q) use ($inning_id) {
                $q->where('inning_id', '=', $inning_id);
                $q->with('batter');
                $q->with('caught_by');
                $q->with('assist_by');
                $q->with('stumped_by');
                $q->with('wicket_by');
                $q->select(
                    'id',
                    'inning_id',
                    'batter_id',
                    DB::raw('round(((runs_achieved / balls_faced) * 100), 2) AS strike_rate'),
                    'team_id',
                    'fixture_id',
                    'runs_achieved',
                    'balls_faced',
                    'fours',
                    'sixes',
                    'wicket_type',
                    'wicket_by',
                    'assist_by',
                    'caught_by',
                    'stumped_by'
                );
            }])
            ->with(['did_not_bat' => function ($q) use ($inning_id, $fId) {
                $q->whereNotIn('player_id', function ($query) use ($inning_id) {
                    $query->select('batter_id')->from('inning_batter_results')->where('inning_id', $inning_id);
                });
                $q->where('is_played', 1);
                $q->where('fixture_id', $fId);
                $q->select('id', 'team_id', 'player_id', 'is_played', 'fixture_id');
                $q->with('player:id,first_name,last_name');
            }])
            ->with(['fall_of_wicket' => function ($q) {
                $q->select('id', 'team_id', 'inning_id', 'batter_id', 'in_which_over', 'score_when_fall');
                $q->with('batter:id,first_name,last_name,username');
            }])
            ->with(['inning_bowler' => function ($q) use ($inning_id) {
                $q->where('inning_id', '=', $inning_id);
                $q->with('bowler');
                $q->select('id', 'fixture_id', 'inning_id', 'bowler_id', 'overs_bowled', 'team_id', 'maiden_overs', 'balls_bowled', 'runs_gave', 'wickets', DB::raw('round((runs_gave / overs_bowled), 1) AS economy_rate'));
            }])->with(['powerplay' => function ($q) {
                $q->select('id', 'inning_id', 'type', 'start', 'end');
            }])
            ->first(['id', 'fixture_id', 'batting_team_id', 'home_team_id', 'away_team_id', 'bowling_team_id']);

        return $inning;
    }

    public function powerplay_overs($i, $start, $end)
    {
        return Delivery::where('inning_id', $i)->whereBetween('over_number', [$start, $end])->sum(DB::raw('runs + extras'));
    }

    public function changeStrikeQuery($data)
    {

        InningBatterResult::where('inning_id', $data['inning_id'])->update([
            'is_on_strike' => 0
        ]);
        return InningBatterResult::where('inning_id', $data['inning_id'])->where('batter_id', $data['batter_id'])->update([
            'is_on_strike' => 1
        ]);
    }

    public function getNotOutBatsmanQuery($data)
    {
        $inning_id = isset($data['inning_id']) ? $data['inning_id'] : 0;
        return PlayingEleven::where('fixture_id', $data['fixture_id'])->where('team_id', $data['team_id'])->where('is_played', 1)
        ->whereNotIn('player_id', function ($query) use ($inning_id) {
            $query->select('batter_id')->from('inning_batter_results')->where('inning_id', $inning_id);
        })->with(['player' => function ($query) {
            $query->select('id', 'first_name', 'last_name', 'profile_pic');
        }])->get();
    }


    public function makeUndoOutAndCalculate($data)
    {
        $new_batsman = InningBatterResult::select('id', 'inning_id')->where('inning_id', $data['inning_id'])->orderBy('id', 'desc')->first();
        $old_fall = WicketFall::select('id', 'inning_id')->where('inning_id', $data['inning_id'])->orderBy('id', 'desc')->first();
        WicketFall::where('id', $old_fall->id)->delete();
        InningBatterResult::where('id', $new_batsman->id)->delete();

        InningBatterResult::where('inning_id', $data['inning_id'])->where('batter_id', $data['batter_id'])->update([
            'is_out' => 0,
            'wicket_type' => null,
            'is_retired' => 0,
            'can_coming_from_retired' => 0
        ]);
    }

    public function makeUndoOutAndCalculateOtherType($data)
    {


        $new_batsman = InningBatterResult::select('id', 'inning_id')->where('inning_id', $data['inning_id'])->orderBy('id', 'desc')->first();
        InningBatterResult::where('id', $new_batsman->id)->delete();

        InningBatterResult::where('inning_id', $data['inning_id'])->where('batter_id', $data['batter_id'])->update([
            'is_out' => 0,
            'wicket_type' => null,
            'is_retired' => 0,
            'can_coming_from_retired' => 0
        ]);
    }

    public function makeOutAndCalculate($inning_id, $batter_id, $data, $wicket_type)
    {
        // $batsman = InningBatterResult::where('inning_id',$inning_id)->where('batter_id',$batter_id)->first();
        $batsman_runs = Delivery::select(DB::raw("batter_id,SUM(runs) as runs_achieved , SUM(CASE WHEN ball_type = 'LEGAL'  THEN 1 ELSE 0 END) AS balls_faced   ,SUM(CASE WHEN boundary_type = 'SIX' THEN 1 ELSE 0 END) AS sixes , SUM(CASE WHEN boundary_type = 'FOUR' THEN 1 ELSE 0 END) AS fours "))->where('inning_id', $inning_id)->where('batter_id', $batter_id)->groupBy('batter_id')->first();

        if ($batsman_runs) {
            $over = floor($batsman_runs->balls_faced / 6);
            $ball = (int)$batsman_runs->balls_faced % 6;
            $total_over = "$over.$ball";

            $updated_data = [
                'runs_achieved' => $batsman_runs->runs_achieved,
                'balls_faced' => $batsman_runs->balls_faced,
                'balls_faced' => $total_over,
                'sixes' => $batsman_runs->sixes,
                'fours' => $batsman_runs->fours,
                'is_out' => 1,
                'wicket_type' => $wicket_type,
                'is_on_strike' => 0,
            ];


            if ($wicket_type == 'RUN_OUT') {
                $updated_data['run_out_by'] = $data['run_out_by'];
                if ($data['assist_by']) $updated_data['assist_by'] = $data['assist_by'];
            }
            if ($wicket_type == 'ACTION_OUT') {
                $updated_data['run_out_by'] = $data['bowler_id'];
            } else {
                $updated_data['wicket_by'] = $data['wicket_by'];
                if ($data['caught_by']) $updated_data['caught_by'] = $data['caught_by'];
                if ($data['stumped_by']) $updated_data['stumped_by'] = $data['stumped_by'];
            }
            InningBatterResult::where('inning_id', $inning_id)->where('batter_id', $batter_id)->update($updated_data);
        } else {
            $over = 0;
            $ball = 0;
            $total_over = "0.0";
            InningBatterResult::where('inning_id', $inning_id)->where('batter_id', $batter_id)->update([
                'runs_achieved' => 0,
                'balls_faced' => 0,
                'balls_faced' => $total_over,
                'sixes' => 0,
                'fours' => 0,
                'is_out' => 1,
                'wicket_type' => $wicket_type,
                'is_on_strike' => 0,
            ]);
        }


        $delivery = Delivery::select(DB::raw("SUM(extras + runs) as total_runs , SUM(CASE WHEN wicket_type IS NOT NULL THEN 1 ELSE 0 END) AS total_wicket , SUM(CASE WHEN ball_type = 'LEGAL' THEN 1 ELSE 0 END) AS total_over "))->where('inning_id', $data['inning_id'])->first();
        $wicket_fall_data = [
            'tournament_id' => $data['tournament_id'],
            'batter_id' => $data['batter_id'],
            'team_id' => $data['team_id'],
            'league_group_id' => $data['league_group_id'],
            'league_group_team_id' => $data['league_group_team_id'],
            'fixture_id' => $data['fixture_id'],
            'inning_id' => $data['inning_id'],
            'in_which_over' => $delivery->total_over,
            'score_when_fall' => $delivery->total_runs,
        ];


        WicketFall::create($wicket_fall_data);
    }

    public function makeRetiredOutAndCalculate($is_retired, $can_coming_from_retired, $data, $wicket_type)
    {
        // $batsman = InningBatterResult::where('inning_id',$inning_id)->where('batter_id',$batter_id)->first();
        $batsman_runs = Delivery::select(DB::raw("batter_id,SUM(runs) as runs_achieved , SUM(CASE WHEN ball_type = 'LEGAL'  THEN 1 ELSE 0 END) AS balls_faced   ,SUM(CASE WHEN boundary_type = 'SIX' THEN 1 ELSE 0 END) AS sixes , SUM(CASE WHEN boundary_type = 'FOUR' THEN 1 ELSE 0 END) AS fours "))->where('inning_id', $data['inning_id'])->where('batter_id', $is_retired)->groupBy('batter_id')->first();

        $updated_data = [];
        if ($batsman_runs) {

            $over = floor($batsman_runs->balls_faced / 6);
            $ball = (int)$batsman_runs->balls_faced % 6;
            $total_over = "$over.$ball";

            $updated_data = [
                'runs_achieved' => $batsman_runs->runs_achieved,
                'balls_faced' => $batsman_runs->balls_faced,
                'overs_faced' => $total_over,
                'sixes' => $batsman_runs->sixes,
                'fours' => $batsman_runs->fours,
                'is_out' => $wicket_type == 'RETIRED_OUT' ? 1 : 0,
                'is_retired' => 1,
                'can_coming_from_retired' => $can_coming_from_retired ? 1 : 0,
                'wicket_type' => $wicket_type,
                'is_on_strike' => 0,
            ];
        } else {
            $updated_data = [
                'runs_achieved' => 0,
                'balls_faced' => 0,
                'overs_faced' => 0,
                'sixes' => 0,
                'fours' => 0,
                'is_out' => $wicket_type == 'RETIRED_OUT' ? 1 : 0,
                'is_retired' => 1,
                'can_coming_from_retired' => $can_coming_from_retired ? 1 : 0,
                'wicket_type' => $wicket_type,
                'is_on_strike' => 0,
            ];
        }

        InningBatterResult::where('inning_id', $data['inning_id'])->where('batter_id', $is_retired)->update($updated_data);
        if ($wicket_type == 'RETIRED_OUT') {

            $delivery = Delivery::select(DB::raw("SUM(extras + runs) as total_runs , SUM(CASE WHEN wicket_type IS NOT NULL THEN 1 ELSE 0 END) AS total_wicket , SUM(CASE WHEN ball_type = 'LEGAL' THEN 1 ELSE 0 END) AS total_over "))->where('inning_id', $data['inning_id'])->first();
            $wicket_fall_data = [
                'tournament_id' => $data['tournament_id'],
                'batter_id' => $data['batter_id'],
                'team_id' => $data['team_id'],
                'league_group_id' => $data['league_group_id'],
                'league_group_team_id' => $data['league_group_team_id'],
                'fixture_id' => $data['fixture_id'],
                'inning_id' => $data['inning_id'],
                'in_which_over' => $delivery->total_over,
                'score_when_fall' => $delivery->total_runs,
            ];


            WicketFall::create($wicket_fall_data);
        }
    }

    public function makeObstructingFilendOutAndCalculate($is_obstructing_field, $data, $wicket_type)
    {
        // $batsman = InningBatterResult::where('inning_id',$inning_id)->where('batter_id',$batter_id)->first();
        $batsman_runs = Delivery::select(DB::raw("batter_id,SUM(runs) as runs_achieved , SUM(CASE WHEN ball_type = 'LEGAL'  THEN 1 ELSE 0 END) AS balls_faced   ,SUM(CASE WHEN boundary_type = 'SIX' THEN 1 ELSE 0 END) AS sixes , SUM(CASE WHEN boundary_type = 'FOUR' THEN 1 ELSE 0 END) AS fours "))->where('inning_id', $data['inning_id'])->where('batter_id', $is_obstructing_field)->groupBy('batter_id')->first();
        $updated_data = [];
        if ($batsman_runs) {

            $over = floor($batsman_runs->balls_faced / 6);
            $ball = (int)$batsman_runs->balls_faced % 6;
            $total_over = "$over.$ball";

            $updated_data = [
                'runs_achieved' => $batsman_runs->runs_achieved,
                'balls_faced' => $batsman_runs->balls_faced,
                'overs_faced' => $total_over,
                'sixes' => $batsman_runs->sixes,
                'fours' => $batsman_runs->fours,
                'is_out' => 1,
                'wicket_type' => $wicket_type,
                'is_on_strike' => 0,
            ];
        } else {
            $updated_data = [
                'runs_achieved' => 0,
                'balls_faced' => 0,
                'overs_faced' => 0,
                'sixes' => 0,
                'fours' => 0,
                'is_out' => 1,
                'wicket_type' => $wicket_type,
                'is_on_strike' => 0,
            ];
        }

        InningBatterResult::where('inning_id', $data['inning_id'])->where('batter_id', $is_obstructing_field)->update($updated_data);


        $delivery = Delivery::select(DB::raw("SUM(extras + runs) as total_runs , SUM(CASE WHEN wicket_type IS NOT NULL THEN 1 ELSE 0 END) AS total_wicket , SUM(CASE WHEN ball_type = 'LEGAL' THEN 1 ELSE 0 END) AS total_over "))->where('inning_id', $data['inning_id'])->first();
        $wicket_fall_data = [
            'tournament_id' => $data['tournament_id'],
            'batter_id' => $is_obstructing_field,
            'team_id' => $data['team_id'],
            'league_group_id' => $data['league_group_id'],
            'league_group_team_id' => $data['league_group_team_id'],
            'fixture_id' => $data['fixture_id'],
            'inning_id' => $data['inning_id'],
            'in_which_over' => $delivery->total_over,
            'score_when_fall' => $delivery->total_runs,
        ];


        WicketFall::create($wicket_fall_data);
    }

    public function makeAbsentOutAndCalculate($data, $wicket_type)
    {
        // $batsman = InningBatterResult::where('inning_id',$inning_id)->where('batter_id',$batter_id)->first();


        $updated_data = [
            'runs_achieved' => 0,
            'balls_faced' => 0,
            'overs_faced' => 0,
            'sixes' => 0,
            'fours' => 0,
            'is_out' => 0,
            'wicket_type' => $wicket_type,
            'is_on_strike' => 0,
        ];
        if ($wicket_type == 'TIME_OUT') {
            $updated_data['is_out'] = 1;
            InningBatterResult::where('inning_id', $data['inning_id'])->where('batter_id', $data['is_time_out'])->update($updated_data);
            $delivery = Delivery::select(DB::raw("SUM(extras + runs) as total_runs , SUM(CASE WHEN wicket_type IS NOT NULL THEN 1 ELSE 0 END) AS total_wicket , SUM(CASE WHEN ball_type = 'LEGAL' THEN 1 ELSE 0 END) AS total_over "))->where('inning_id', $data['inning_id'])->first();
            $wicket_fall_data = [
                'tournament_id' => $data['tournament_id'],
                'batter_id' => $data['is_time_out'],
                'team_id' => $data['team_id'],
                'league_group_id' => $data['league_group_id'],
                'league_group_team_id' => $data['league_group_team_id'],
                'fixture_id' => $data['fixture_id'],
                'inning_id' => $data['inning_id'],
                'in_which_over' => $delivery->total_over,
                'score_when_fall' => $delivery->total_runs,
            ];


            WicketFall::create($wicket_fall_data);
        } else {

            InningBatterResult::where('inning_id', $data['inning_id'])->where('batter_id', $data['is_absent'])->update($updated_data);
        }
    }

    public function calculateDeliveriesQuery($iId, $delivery_id)
    {
        $delivery = Delivery::select(DB::raw("SUM(extras + runs) as total_runs , SUM(CASE WHEN wicket_type IS NOT NULL THEN 1 ELSE 0 END) AS total_wicket , SUM(CASE WHEN ball_type = 'LEGAL' THEN 1 ELSE 0 END) AS total_over "))->where('inning_id', $iId)->first();

        $inning_details = Inning::where('id', $iId)->first();
        $is_first_innings = $inning_details->is_first_innings;
        $totalRuns = $delivery->total_runs ? $delivery->total_runs : 0;
        $specialRuns = Panalty::select(DB::raw(
            "SUM(CASE WHEN type = 'BONUS' THEN runs ELSE 0 END) AS total_bonus_runs,
            SUM(CASE WHEN type = 'PENALTY' THEN runs ELSE 0 END) AS total_penalty_runs"
            ))
            ->where('inning_id', $iId)
            ->first();

            $bonusRuns = $specialRuns['total_bonus_runs'] ?? 0;
            $penaltyRuns = $specialRuns['total_penalty_runs'] ?? 0;

            $totalRuns = $totalRuns + (int)$bonusRuns - (int)$penaltyRuns;


        if ($delivery) {
            $ball_faced = $delivery->total_over;
            $over = floor($ball_faced / 6);
            $ball = (int)$ball_faced % 6;
            $total_over_yes = "$over.$ball";
            $ob = [
                'total_runs' => $totalRuns,
                'total_wicket' => $delivery->total_wicket ? $delivery->total_wicket : 0,
                'total_over' => $total_over_yes,
            ];

            if ($delivery_id) {


                $total_over_yes = (float)$total_over_yes;
                if ($ball_faced % 6 == 0) {
                    $total_over_yes = $total_over_yes - 0.4;
                }
                Delivery::where('id', $delivery_id)->update([
                    'ball_number' => $total_over_yes
                ]);
            }

            if ($is_first_innings) {
                Fixture::where('id', $inning_details->fixture_id)->update([
                    'home_team_runs' => $ob['total_runs'],
                    'home_team_wickets' => $ob['total_wicket'],
                    'home_team_overs' => $ob['total_over'],
                ]);
            } else {
                Fixture::where('id', $inning_details->fixture_id)->update([
                    'away_team_runs' => $ob['total_runs'],
                    'away_team_wickets' => $ob['total_wicket'],
                    'away_team_overs' => $ob['total_over'],
                ]);
            }

            // $is_first_innings = Inning::where('')


            Inning::where('id', $iId)->update([
                'total_runs' => $ob['total_runs'],
                'total_wickets' => $ob['total_wicket'],
                'total_overs' => $ob['total_over'],
            ]);
        }


        $batsman_runs = Delivery::select(DB::raw("batter_id,SUM(runs) as runs_achieved , SUM(CASE WHEN ball_type = 'LEGAL'  THEN 1 ELSE 0 END) AS balls_faced ,SUM(CASE WHEN ball_type = 'NB'  THEN 1 ELSE 0 END) AS total_no_ball  ,SUM(CASE WHEN boundary_type = 'SIX' THEN 1 ELSE 0 END) AS sixes , SUM(CASE WHEN boundary_type = 'FOUR' THEN 1 ELSE 0 END) AS fours "))->where('inning_id', $iId)->groupBy('batter_id')->get();
        $allBatsIds = [];

        InningBatterResult::where('inning_id', $iId)->update([
            'runs_achieved' => 0,
            'balls_faced' => 0,
            'overs_faced' => 0,
            'sixes' => 0,
            'fours' => 0,
        ]);

        if ($batsman_runs && sizeof($batsman_runs) > 0) {

            foreach ($batsman_runs as $value) {
                $ball_faced = $value->total_no_ball + $value->balls_faced;
                $over = floor($ball_faced / 6);
                $ball = (int)$ball_faced % 6;
                $total_over = "$over.$ball";
                array_push($allBatsIds, $value->batter_id);
                InningBatterResult::where('inning_id', $iId)->where('batter_id', $value['batter_id'])->update([
                    'runs_achieved' => $value->runs_achieved,
                    'balls_faced' => $ball_faced,
                    'overs_faced' => $total_over,
                    'sixes' => $value->sixes,
                    'fours' => $value->fours,
                ]);
            }
        }


        // if(sizeof($allBatsIds) > 0){
        //     InningBatterResult::where('inning_id', $iId)->whereNotIn('batter_id',$allBatsIds)->delete();
        // }


        $bowls = Delivery::select(DB::raw("bowler_id,SUM(runs + extras) as runs_gave , SUM(CASE WHEN ball_type = 'LEGAL'  THEN 1 ELSE 0 END) AS total_legal_bowl,SUM(CASE WHEN ball_type IS NOT NULL  THEN 1 ELSE 0 END) AS total_bowl , SUM(CASE WHEN ball_type = 'WD'  THEN 1 ELSE 0 END) AS wide_balls , SUM(CASE WHEN ball_type = 'NB'  THEN 1 ELSE 0 END) AS no_balls, SUM(CASE WHEN (    wicket_type IS NOT NULL  AND wicket_type != 'RUN_OUT' ) THEN 1 ELSE 0 END) AS wickets"))->where('inning_id', $iId)->groupBy('bowler_id')->get();

        $allBowlerIds = [];

        InningBowlerResult::where('inning_id', $iId)->update([
            'runs_gave' => 0,
            'balls_bowled' => 0,
            'overs_bowled' => 0,
            'maiden_overs' => 0,
            'wide_balls' => 0,
            'no_balls' => 0,
            'wickets' => 0
        ]);

        if ($bowls && sizeof($bowls) > 0) {

            foreach ($bowls as $value) {
                $over = floor($value->total_legal_bowl / 6);
                $ball = (int)$value->total_legal_bowl % 6;
                $total_over = "$over.$ball";
                array_push($allBowlerIds, $value->bowler_id);
                $total_maiden = Delivery::select('over_number', DB::raw(" SUM(runs + extras) AS Maiden"))->where('bowler_id', $value['bowler_id'])->groupBy('over_number')->havingRaw(" SUM(runs + extras) < 1 and SUM(CASE WHEN ball_type = 'LEGAL'  THEN 1 ELSE 0 END)  = 6 ")->count();
                $bower_updated_data = [
                    'runs_gave' => $value->runs_gave,
                    'balls_bowled' => $value->total_bowl,
                    'overs_bowled' => $total_over,
                    'wickets' => $value->wickets,
                    'maiden_overs' => $total_maiden,
                    'wide_balls' => $value->wide_balls,
                    'no_balls' => $value->no_balls,
                ];
                InningBowlerResult::where('inning_id', $iId)->where('bowler_id', $value['bowler_id'])->update($bower_updated_data);
            }
        }

        return "done";
    }


    public function deliveriesByOverQuery($data)
    {
        $inning_id = $data['inning_id'];
        $fId = $data['fixture_id'];
        $lastNumber = isset($data['last_over_number']) ? $data['last_over_number'] : 0;
        $d = Delivery::where('fixture_id', $fId)->where('inning_id', $inning_id)
            ->select('id', 'fixture_id', 'inning_id', 'over_id', 'over_number', 'extras', 'runs')->with('bowler:id,first_name,last_name');

        if ($lastNumber) {
            $d->where('over_number', '<', $lastNumber);
        }

        $deliveries = $d->with(['oversDeliveries' => function ($q) use ($inning_id) {
            $q->select('id', 'fixture_id', 'over_number', 'bowler_id', 'ball_number', 'extras', 'ball_type', 'runs', 'wicket_type', 'run_type', 'is_time_out', 'is_absent', 'is_retired');
            $q->where('inning_id', $inning_id);
        }])
            ->orderByDesc('id')->groupBy('over_number')->limit(10)->get();
        return $deliveries;
    }


    public function getInnings($fId)
    {
        return Inning::where('fixture_id', $fId)
            ->select('id', 'is_first_innings', 'innings_status')
            ->orderByDesc('is_first_innings')->get();
    }

    public function countOvers($id)
    {
        return Over::where('inning_id', $id)->count();
    }

    public function getPreviousInningsResult($data)
    {
        $fixtureId = $data['fixture_id'];

        $innings = Inning::where('fixture_id', $fixtureId)
            ->select('id')
            ->where('is_first_innings', 1)
            ->withSum('bowlers as target', 'runs_gave')
            ->first();

        return $innings;
    }

    public function getCurrentInningsLiveQuery($data)
    {
        $fixtureId = $data['fixture_id'];
        $innings = Inning::where('fixture_id', $fixtureId)
            ->select('id', 'fixture_id', 'batting_team_id', 'is_first_innings')
            ->with('batting_team:id,team_short_name')
            ->withSum('bowlers as overs_faced', 'overs_bowled')
            ->withSum('bowlers as wickets_gave', 'wickets')
            ->withSum('bowlers as runs_take', 'runs_gave')
            ->withCount(['bowlers as current_run_rate' => function ($q) {
                $q->select(DB::raw('ROUND(SUM(runs_gave) / SUM(overs_bowled), 2)'));
            }])
            ->with('fixture:id,match_overs,match_final_result,is_match_finished')
            ->with('currentBowler', function ($q) {
                $q->select('id', 'inning_id', 'runs_gave', 'overs_bowled', 'wickets', 'bowler_id')
                    ->with('bowler:id,first_name,last_name')->first();
            })
            ->with('currentStriker', function ($q) {
                $q
                    ->select('id', 'inning_id', 'runs_achieved', 'balls_faced', 'is_on_strike', 'batter_id')
                    ->with('batter:id,first_name,last_name')
                    ->get();
            })
            ->with('currentNonStriker', function ($q) {
                $q
                    ->select('id', 'inning_id', 'runs_achieved', 'balls_faced', 'is_on_strike', 'batter_id')
                    ->with('batter:id,first_name,last_name')
                    ->get();
            })
            ->with('innings_overs', function ($q) {
                $q
                    ->select('id', 'inning_id')
                    ->with('oversDelivery', function ($q) {
                        $q
                            ->select('id', 'over_id', 'ball_type', 'run_type', 'boundary_type', 'runs', 'extras', 'wicket_type')
                            ->orderByDesc('id');
                    })
                    ->orderByDesc('id');
            })
            ->latest('id')
            ->first();

        return $innings;
    }

    public function getDeliveriesByInningsQuery($inningsId)
    {

        return Delivery::select('id', 'over_number', 'extras', 'ball_type', 'runs', 'wicket_type', 'run_type', 'is_time_out', 'is_absent', 'is_retired')
            ->where('inning_id', $inningsId)
            ->orderByDesc('id')
            ->get();
    }

    public function getLiveScorebyInnings($fixture_id)
    {
        $innings = Inning::where('fixture_id', $fixture_id)->where('innings_status', '=', 'Started')
            ->select(
                'id',
                'fixture_id',
                'total_runs',
                'total_overs',
                'total_wickets',
                'is_first_innings',
                'batting_team_id',
                'bowling_team_id',
            )
            ->with('batting_team')
            ->with(['fixture' => function ($q) {
                $q->select('id', 'toss_winner_team_id');
                $q->with('tossWinnerTeam:id,team_name,team_unique_name,team_short_name');
            }])
            ->with(['previous_innings' => function ($q) use ($fixture_id) {
                $q->where('fixture_id', $fixture_id);
                $q->where('is_first_innings', 1);
                $q->where('innings_status', '=', 'Finished');
                $q->orderBy('id', 'desc');
                $q->limit(1);
                $q->select('id', 'bowling_team_id', 'total_runs', 'total_overs', 'is_first_innings', 'total_wickets');
            }])
            ->with(['currentStriker' => function ($q) {
                $q->select('id', 'inning_id', 'runs_achieved', 'balls_faced', DB::raw('(runs_achieved/balls_faced)*100 as strike_rate'), 'is_on_strike', 'batter_id', 'fours', 'sixes')
                    ->with('batter:id,first_name,last_name');
            }])
            ->with(['currentNonStriker' => function ($q) {
                $q->select('id', 'inning_id', 'runs_achieved', 'balls_faced', DB::raw('(runs_achieved/balls_faced)*100 as strike_rate'), 'is_on_strike', 'batter_id', 'fours', 'sixes')
                    ->with('batter:id,first_name,last_name');
            }])
            ->with(['currentBowler' => function ($q) {
                $q->select('id', 'inning_id', 'overs_bowled', 'maiden_overs', 'balls_bowled', 'runs_gave', DB::raw('(runs_gave/overs_bowled) as economy'), 'wickets', 'is_on_strike', 'bowler_id')
                    ->with('bowler');
            }])
            ->latest('id')->first();
        return $innings;
    }

    public function getLiveScoreByInningsId($inningsId)
    {
        $innings = Inning::where('id', $inningsId)
            ->select(
                'id',
                'fixture_id',
                'total_runs',
                'total_overs',
                'total_wickets',
                'is_first_innings',
                'batting_team_id',
                'bowling_team_id',
            )
            ->with('batting_team')
            ->with('bowling_team')
            ->with(['fixture' => function ($q) {
                $q->select('id', 'toss_winner_team_id');
                $q->with('tossWinnerTeam:id,team_name,team_unique_name,team_short_name');
            }])
            ->with(['currentStriker' => function ($q) {
                $q->select('id', 'inning_id', 'runs_achieved', 'balls_faced', DB::raw('(runs_achieved/balls_faced)*100 as strike_rate'), 'is_on_strike', 'batter_id', 'fours', 'sixes')
                    ->with('batter:id,first_name,last_name');
            }])
            ->with(['currentNonStriker' => function ($q) {
                $q->select('id', 'inning_id', 'runs_achieved', 'balls_faced', DB::raw('(runs_achieved/balls_faced)*100 as strike_rate'), 'is_on_strike', 'batter_id', 'fours', 'sixes')
                    ->with('batter:id,first_name,last_name');
            }])
            ->with(['currentBowler' => function ($q) {
                $q->select('id', 'inning_id', 'overs_bowled', 'maiden_overs', 'balls_bowled', 'runs_gave', DB::raw('(runs_gave/overs_bowled) as economy'), 'wickets', 'is_on_strike', 'bowler_id')
                    ->with('bowler');
            }])
            ->latest('id')->first();
        return $innings;
    }

    public function singleDelivery($id, $inning_id)
    {
        return Delivery::where('id', $id)
            ->with('stumpBy', function ($q) {
                $q->select('id', 'first_name', 'last_name');
            })
            ->with('caughtBy', function ($q) {
                $q->select('id', 'first_name', 'last_name');
            })
            ->with('assistBy', function ($q) {
                $q->select('id', 'first_name', 'last_name');
            })
            ->with('wicketBy', function ($q) {
                $q->select('id', 'first_name', 'last_name');
            })
            ->with('runOutBy', function ($q) {
                $q->select('id', 'first_name', 'last_name');
            })->with(['singleBatter' => function ($q) use ($inning_id) {
                $q->select('id', 'batter_id', 'inning_id', 'runs_achieved', 'balls_faced', 'fours', 'sixes');
                $q->where('inning_id', $inning_id);
            }])
            ->select('id', 'batter_id', 'wicket_type', 'assist_by', 'wicket_by', 'stumped_by', 'run_out_by', 'caught_by')->first();
    }

    public function matchComentaryHighlightQuery($data)
    {
        $fId = $data['fixture_id'];
        $lastId = isset($data['last_id']) ? $data['last_id'] : 0;
        $status = isset($data['status']) ? $data['status'] : '';
        $inning_id = isset($data['inning_id']) ? $data['inning_id'] : '';
        $d = Delivery::where('fixture_id', $fId)->select('id', 'fixture_id', 'over_number', 'ball_number', 'inning_id', 'boundary_type', 'ball_type', 'wicket_type', 'shot_position', 'bowler_id', 'batter_id', 'runs', 'extras', 'is_retired', 'run_type')
            ->with('batter', function ($q) {
                $q->select('id', 'first_name', 'last_name');
            })
            ->with('bowler', function ($q) {
                $q->select('id', 'first_name', 'last_name');
            });

        if ($lastId) {
            $d->where('id', '<', $lastId);
        }

        if ($status && $status == "HIGHLIGHT") {
            $d->where(function (Builder $query) {
                return $query->where('wicket_type', '!=', null)
                    ->orWhere('boundary_type', '!=', null);
            });
        }
        if ($status && ($status == "SIX" || $status == "FOUR")) {
            $d->where('boundary_type', $status);
        }
        if ($status && $status == "WICKETS") {
            $d->where('wicket_type', '!=', null);
        }
        if ($inning_id) {
            $d->where('inning_id', $inning_id);
        }

        $deliveries = $d->orderByDesc('id')->limit(20)->get();
        return $deliveries;
    }

    // DB::raw("SUM(CASE WHEN deliveries.ball_type = 'LEGAL'  THEN 1 ELSE 0 END) AS balls_faced")
    public function matchLiveCommentarty($id)
    {

        $iId = $id;
        $lastId = isset($data['last_id']) ? $data['last_id'] : '';
        $commnetary = Over::where('overs.inning_id', $iId)->join('deliveries as d', 'overs.id', '=', 'd.over_id')
            ->select('overs.id', 'overs.bowler_id', 'overs.over_number', 'd.id as d_id', 'd.ball_type', 'd.runs', 'd.extras', 'd.boundary_type', 'd.boundary_type', 'd.commentary')
            ->orderByDesc('d_id')->withCount(['oversDelivery as legal_count' => function ($q2) {
                $q2->where('ball_type', '=', 'LEGAL');
            }])
            ->get();

        // whereHas('oversDelivery', function (Builder $query) {
        //     $query->where('boundary_type', 'like', 'SIX');
        // })->
        // $c = Over::where('inning_id', $iId)->orderBy('id', 'desc')
        //     ->select('id', 'inning_id', 'bowler_id', 'over_number');

        // if ($lastId) {
        //     $c->where('id', '<', $lastId);
        // }

        // $commnetary = $c->with(['oversDelivery' => function ($q) {
        //     $q->orderBy('id', 'desc');
        //     $q->select('id', 'over_id', 'ball_type', 'runs', 'run_type', 'wicket_type', 'boundary_type', 'commentary');
        // }])->withCount(['oversDelivery as bowling_count' => function ($q2) {
        //     $q2->where('ball_type', '=', 'LEGAL');
        // }])->limit(15)->get();

        return $commnetary->groupBy('over_number');
    }

    public function allCoordinatesByType($type = null)
    {
        if ($type) return FieldCoordinate::where('batsman_type', $type)->get();
        return FieldCoordinate::all();
    }

    public function countOver($iId)
    {
        return Over::where('inning_id', $iId)->count();
    }

    public function countMainSquad($data)
    {
        return TeamPlayer::where('team_id', $data['team_id'])->where('squad_type', $data['squad_type'])->count();
    }

    public function addPlayingXIFromMainSquad($teamId, $team, $fixture_id)
    {
        $team_players = TeamPlayer::where('team_id', $teamId)->with('player')->whereIn('squad_type', ['MAIN', 'EXTRA'])->get();
        PlayingEleven::where('fixture_id', $fixture_id)->where('team_id', $teamId)->delete();
        $xi_players = [];
        foreach ($team_players as $value) {
            $is_captain = $team['id'] == $team['is_captain'] ? 1 : 0;
            $is_wicket_keeper = $team['id'] == $team['is_wicket_keeper'] ? 1 : 0;
            $ob = [
                'fixture_id' => $fixture_id,
                'team_id' => $team['id'],
                'player_id' => $value->player_id,
                'playing_role' => $value->player->playing_role,
                'is_captain' => $is_captain,
                'is_wicket_keeper' => $is_wicket_keeper,
                'type' => $value->squad_type,
                'is_played' => $value->squad_type == 'MAIN' ? 1 : 0,

                // 'is_captain'=> $innings['']
                // 'is_wicket_keeper'=> $innings['']
                // 'is_played' => $value['squad_type']  == 'MAIN'?1 : 0
            ];

            array_push($xi_players, $ob);
        }

        return PlayingEleven::insert($xi_players);
    }

    public function testing($id)
    {
        return Delivery::where('over_id', $id)->get();
    }

    public function getFixtureById($id, $isWithInnings = null)
    {
        $data = Fixture::where('id', $id);
        // ->select('id','away_team_id','home_team_id','away_team_wickets','home_team_wickets','is_match_finished','home_team_overs','away_team_overs','away_team_runs','home_team_runs','match_final_result','tournament_id');
        if ($isWithInnings) {
            $data->with('innings.batting_team');
        }
        return $data->first();
    }

    public function getNextFixture($tournamentId, $tempTeam)
    {
        return Fixture::where('tournament_id', $tournamentId)
            ->where('temp_team_one', $tempTeam)
            ->orWhere('temp_team_two', $tempTeam)
            ->first();
    }

    public function getNextFixtures($tournamentId, $tempTeam)
    {
        return Fixture::where('tournament_id', $tournamentId)
            ->where('temp_team_one', $tempTeam)
            ->orWhere('temp_team_two', $tempTeam)
            ->get();
    }

    public function countTournamentMatchesByRound($tournamentId, $roundType = null, $leagueGroupId = null, $isMatchFinished = null)
    {
        return Fixture::where('tournament_id', $tournamentId)
            ->when(isset($leagueGroupId), function ($q) use ($leagueGroupId) {
                $q->where('league_group_id', $leagueGroupId);
            })
            ->when(isset($isMatchFinished), function ($q) use ($isMatchFinished) {
                $q->where('is_match_finished', $isMatchFinished);
            })
            ->when(isset($roundType), function ($q) use ($roundType) {
                $q->where('round_type', $roundType);
            })
            ->count();
    }

    public function getTeamById($id)
    {
        return Team::where('id', $id)
            ->select('id', 'team_name')->first();
    }

    public function getInningsByFixerIdAndteamIdQuery($fId, $tId, $is_first_innings)
    {

        $q = Inning::where('fixture_id', $fId)->where('batting_team_id', $tId)
            ->select('id', 'batting_team_id', 'league_group_team_id', 'league_group_bowling_team_id', 'fixture_id', 'is_first_innings', 'bowling_team_id');
        if ($is_first_innings) {
            $q->where('is_first_innings', $is_first_innings);
        }

        return $q->first();
    }

    public function getInningsOfBattingTeamById($inningsId, $teamId){
        return Inning::where('id', $inningsId)->where('batting_team_id', $teamId)->first();
    }

    public function storeRanksQuery($data)
    {
        return MatchRank::create($data);
    }

    public function getTournamentById($id)
    {
        return Tournament::where('id', $id)->select('id', 'tournament_type', 'group_settings', 'league_format', 'third_position', 'is_start')->first();
    }

    public function getWinningTeamByFixtureId($id)
    {
        //    return User::limit(2)->get();

        return Fixture::where('id', $id)->select('id', 'match_winner_team_id')
            ->first();
    }

    public function getAllPlayersWithBattingInnings($teamId, $fId)
    {
        return InningBatterResult::where('team_id', $teamId)
            ->where('fixture_id', $fId)
            ->get();
    }

    public function getAllPlayersWithBollingInnings($teamId, $fId)
    {
        return InningBowlerResult::where('team_id', $teamId)
            ->where('fixture_id', $fId)
            ->get();
    }

    public function getPlayingEleven($team_id, $fId)
    {

        return PlayingEleven::join('users', 'playing_elevens.player_id', 'users.id')
            ->where('team_id', $team_id)->where('fixture_id', $fId)
            ->select('player_id', 'team_id', 'fixture_id', 'users.first_name', 'users.last_name')
            ->get();
    }

    public function getInningsByTeamIdFixtureId($team_id, $fId, $type)
    {
        $q = Inning::where('fixture_id', $fId);
        if ($type == 1) {
            $q->where('batting_team_id', $team_id);
        } else $q->where('bowling_team_id', $team_id);

        return $q->select('id', 'total_overs', 'fixture_id', 'batting_team_id', 'total_runs', 'total_wickets')
            ->first();
    }

    public function getInnigsWithFixtureQuery($id)
    {

        return Inning::where('id', $id)->with('fixture.ground')
            ->with('batting_team')
            ->with('bowling_team')
            ->first();
    }
}
