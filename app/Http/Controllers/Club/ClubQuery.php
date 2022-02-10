<?php

namespace App\Http\Controllers\Club;


use App\Models\ClubPlayer;
use App\Models\Fixture;
use App\Models\IndividualClubChallenge;
use App\Models\Inning;
use App\Models\InningBatterResult;
use App\Models\InningBowlerResult;
use App\Models\PlayingEleven;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClubQuery
{
    // ======================================== Special Validation queries start ==============================================
    public function getClubByIdQuery($clubOwnerId)
    {
        return User::select('id', \DB::raw("CONCAT(first_name, ' ', last_name) AS name"), 'city', 'country', 'cover as banner', 'profile_pic as logo')
            ->withCount(['clubMembers AS pending_requests' => function ($q) {
                $q->where('status', 'PENDING');
            }])
            ->where('id', $clubOwnerId)
            ->first();
    }

    public function getTeamsListByClubQuery($clubOwnerId)
    {
        return Team::select('id', 'team_name')->where('owner_id', $clubOwnerId)->get();
    }


    public function getTournamentsListByClubQuery($data)
    {
        $clubOwnerId = $data['club_owner_id'];
        $teamId = $data['team_id'] ?? null;

        return Tournament::select('id', 'tournament_name')
            ->whereHas('teams', function ($q) use ($clubOwnerId, $teamId) {
                $q->when(!$teamId, function ($q) use ($clubOwnerId) {
                    $q->where('owner_id', $clubOwnerId);
                })
                    ->when($teamId, function ($q) use ($teamId) {
                        $q->where('teams.id', $teamId);
                    });
            })
            ->get();
    }
    // ======================================== Special Validation queries start ==============================================

    // ======================================== Special Validation queries start ===========================================
    //  Checking club owner is valid or not
    public function checkValidClubOwnerQuery($clubOwnerId)
    {
        return User::where('id', $clubOwnerId)
            ->where('registration_type', 'CLUB_OWNER')
            ->first();
    }

    //  Checking player is valid or not
    public function checkValidPlayerQuery($playerId)
    {
        return User::where('id', $playerId)
            ->where('registration_type', 'PLAYER')
            ->first();
    }

    //  Checking is request already exist, accepted and send by whom ?
    public function checkRequestIsValidQuery($clubOwnerId, $playerId, $status = null, $requestedBy = null)
    {
        return ClubPlayer::where('club_owner_id', $clubOwnerId)
            ->where('player_id', $playerId)
            ->when($status, function ($q) use ($status) {
                return $q->where('status', '=', $status);
            })
            ->when($requestedBy, function ($q) use ($requestedBy) {
                return $q->where('requested_by', '=', $requestedBy);
            })
            ->first();
    }
    // ======================================== Special Validation queries end ==============================================

    //  ==========================================Club to Player request start ================================================
    public function sentPlayerRequestQuery($attributes)
    {
        return ClubPlayer::create($attributes);
    }

    public function updatePlayerRequestQuery($clubOwnerId, $playerId, $attributes)
    {
        return ClubPlayer::where('club_owner_id', $clubOwnerId)
            ->where('player_id', $playerId)
            ->update($attributes);
    }

    public function removePlayerRequestQuery($clubOwnerId, $playerId)
    {
        return ClubPlayer::where('club_owner_id', $clubOwnerId)
            ->where('player_id', $playerId)
            ->delete();
    }

    public function getPlayerRequestsListQuery($data)
    {
        $clubOwnerId = Auth::id();;
        $status = $data['status'];
        $lastId = $data['last_id'] ?? null;
        $limit = $data['limit'] ?? 10;

        return ClubPlayer::select('id', 'player_id', 'status', 'requested_by')
            ->with('player:id,first_name,last_name,city,playing_role,profile_pic')
            ->where('club_owner_id', $clubOwnerId)
            ->where('status', $status)
            ->when($lastId, function ($query) use ($lastId) {
                $query->where('id', '>', $lastId);
            })
            ->limit($limit)
            ->get();
    }

    public function getPlayerRequestsListV2Query($data)
    {
        return $data;
        $clubOwnerId = Auth::id();;
        $status = $data['status'];
        $term = "%{$data['term']}%";
        $lastId = $data['last_id'];
        $limit = $data['limit'] ?? 10;

        return ClubPlayer::join('users', 'club_players.id', '=', 'users.id')
            ->where('club_owner_id', $clubOwnerId)
            ->where('status', $status)
            ->when($lastId, function ($query) use ($lastId) {
                $query->where('club_players.id', '>', $lastId);
            })
            ->limit($limit)
            ->get();
    }

    public function searchPlayers($data)
    {
        $clubOwnerId = Auth::id();
        $lastId = $data['last_id'] ?? null;
        $limit = $data['limit'] ?? 10;

        return User::select('id', 'first_name', 'last_name', 'city', 'playing_role', 'profile_pic')
            ->whereDoesntHave('memberOfClubs', function ($query) use ($clubOwnerId) {
                $query->where('club_owner_id', $clubOwnerId);
            })
            ->where('registration_type', 'PLAYER')
            ->when(isset($data['term']), function($q) use($data){
                $q->where(function ($query) use ($data) {
                    $term = "%{$data['term']}%";
                    $query
                        ->where(DB::raw('CONCAT(first_name, " ", last_name)'), 'LIKE', $term)
                        ->orWhere(DB::raw('CONCAT(last_name, " ", first_name)'), 'LIKE', $term)
                        ->orWhere('username', 'LIKE', $term)
                        ->orWhere('email', 'LIKE', $term)
                        ->orWhere('phone', 'LIKE', $term);
                });
            })
            ->when($lastId, function ($query) use ($lastId) {
                $query->where('id', '>', $lastId);
            })
            ->limit($limit)
            ->get();
    }
    //  ============================================== Club to Player request end ==========================================
    //  ============================================== Club to Club challenge request start ==========================================
    public function getChallengedRequestByOpponentQuery($data)
    {
        return IndividualClubChallenge::with('fixture')
            ->where(function ($q) use ($data) {
                $q
                    ->where('challenger_id', $data['challenger_id'])
                    ->where('opponent_id', $data['opponent_id']);
            })
            ->orWhere(function ($q) use ($data) {
                $q
                    ->where('challenger_id', $data['opponent_id'])
                    ->where('opponent_id', $data['challenger_id']);
            })
            ->latest('id')
            ->first();
    }

    public function getChallengedRequestByIdQuery($data)
    {
        return IndividualClubChallenge::where('id', $data['challenge_request_id'])->first();
    }

    public function sentClubChallengeRequestQuery($data)
    {
        return IndividualClubChallenge::create($data);
    }

    public function getClubChallengeRequestsQuery()
    {
        return IndividualClubChallenge::select('id', 'challenger_id', 'opponent_id', 'status', 'fixture_id')
            ->whereNull('fixture_id')
            ->with('challenger:id,first_name,last_name,profile_pic')
            ->with('opponent:id,first_name,last_name,profile_pic')
            ->where(function($q){
                $q->where('challenger_id', \auth()->id())
                ->orWhere('opponent_id', \auth()->id());
            })
            ->paginate(10);
    }

    public function cancelClubChallengeRequestQuery($requestId)
    {
        return IndividualClubChallenge::where('id', $requestId)->delete();
    }

    public function updateClubChallengeRequestQuery($requestId, $data)
    {
        return IndividualClubChallenge::where('id', $requestId)->update($data);
    }

    //  ============================================== Club to Club challenge request end ==========================================

    //  ============================================== Club matches list start =============================================
    public function getClubMatchesListByFilterQuery($data)
    {
        $ownerId = $data['club_owner_id'];
        $teamId = $data['team_id'] ?? null;
        $matchOvers = $data['match_overs'] ?? null;
        $ballType = $data['ball_type'] ?? null;
        $year = $data['year'] ?? null;
        $matchType = $data['match_type'] ?? null;
        $tournamentId = $data['tournament_id'] ?? null;
        $tournamentCategory = $data['tournament_category'] ?? null;
        $limit = $data['limit'] ?? 10;

        return Fixture::select(
            'id',
            'tournament_id',
            'match_date',
            'toss_winner_team_id',
            'team_elected_to',
            'match_final_result',
            'home_team_id',
            'home_team_runs',
            'home_team_overs',
            'home_team_wickets',
            'away_team_id',
            'away_team_runs',
            'away_team_overs',
            'away_team_wickets',
        )
            ->where('is_match_start', 1)
            ->where('is_match_finished', 1)
            ->when($teamId, function ($query) use ($teamId) {
                $query->where(function ($query) use ($teamId) {
                    $query
                        ->where('home_team_id', $teamId)
                        ->orWhere('away_team_id', $teamId);
                });
            })
            ->when(!$teamId, function ($query) use ($ownerId) {
                $query->where(function ($query) use ($ownerId) {
                    $query
                        ->wherehas('homeTeam', function ($query) use ($ownerId) {
                            $query
                                ->where('owner_id', $ownerId);
                        })
                        ->orWherehas('awayTeam', function ($query) use ($ownerId) {
                            $query
                                ->where('owner_id', $ownerId);
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
            ->with('tournament:id,tournament_name,city')
            ->orderByDesc('match_date')
            ->paginate($limit);
    }

    public function getFixtureYears($data)
    {
        $ownerId = $data['club_owner_id'];
        $teamId = $data['team_id'] ?? null;

        return Fixture::select(DB::raw('DISTINCT YEAR(match_date) AS year'))
            ->where('match_date', '!=', "1111-11-11")
            ->whereNotNull('match_date')
            ->orderByDesc('year')
            ->when($teamId, function ($query) use ($teamId) {
                $query->where(function ($query) use ($teamId) {
                    $query
                        ->where('home_team_id', $teamId)
                        ->orWhere('away_team_id', $teamId);
                });
            })
            ->when(!$teamId, function ($query) use ($ownerId) {
                $query->where(function ($query) use ($ownerId) {
                    $query
                        ->whereHas('homeTeam', function ($query) use ($ownerId) {
                            $query
                                ->where('owner_id', $ownerId);
                        })
                        ->orWhereHas('awayTeam', function ($query) use ($ownerId) {
                            $query
                                ->where('owner_id', $ownerId);
                        });
                });
            })
            ->get();
    }
    //  ============================================== Club matches list end ===============================================

    //  ====================================== Club matches list by filter start ===========================================
    public function getClubMembersListByFilterQuery($data)
    {
        $attributes = [
            'club_owner_id' => $data['club_owner_id'] ?? null,
            'team_id' => $data['team_id'] ?? null,
            'limit' => $data['limit'] ?? 10,
        ];

        return User::select(
            'id',
            \DB::raw("CONCAT(first_name, ' ', last_name) AS name"),
            'city',
            'playing_role',
            'profile_pic'
        )
            ->when(!$attributes['team_id'], function ($q) use ($attributes) {
                $q
                    ->whereHas('memberOfClubs', function ($q) use ($attributes) {
                        $q
                            ->where('club_owner_id', $attributes['club_owner_id'])
                            ->where('status', 'ACCEPTED');
                    })
                    ->orderBy('name');
            })
            ->when($attributes['team_id'], function ($q) use ($attributes) {
                $q
                    ->whereHas('memberOfTeams', function ($q) use ($attributes) {
                        $q
                            ->where('team_id', $attributes['team_id']);
                    });
            })
            ->paginate($attributes['limit']);
    }
    //  ====================================== Club matches list by filter end =============================================

    //  ====================================== Club stats by filter end ====================================================
    public function getClubStatsByFilterQuery($data)
    {
        $attributes = [
            'club_owner_id' => $data['club_owner_id'],
            'team_id' => $data['team_id'] ?? null,
            'match_overs' => $data['match_overs'] ?? null,
            'ball_type' => $data['ball_type'] ?? null,
            'year' => $data['year'] ?? null,
            'match_type' => $data['match_type'] ?? null,
            'tournament_id' => $data['tournament_id'] ?? null,
            'tournament_category' => $data['tournament_category'] ?? null,
        ];

        return Fixture::select(DB::raw(
            "SUM(CASE WHEN (is_match_finished = 1) THEN 1 ELSE 0 END) AS total_matches_played,
                SUM(CASE WHEN (is_match_finished = 1 AND is_match_no_result = 0) THEN 1 ELSE 0 END) AS total_valid_matches_played,
                SUM(CASE WHEN (is_match_start = 0 ) THEN 1 ELSE 0 END) AS total_upcoming_matches,
                SUM(CASE WHEN (is_match_start = 0 ) THEN 1 ELSE 0 END) AS total_upcoming_matches,
                SUM(CASE WHEN (is_match_draw = 1 ) THEN 1 ELSE 0 END) AS total_tied_matches,
                SUM(CASE WHEN (is_test_match_draw = 1 ) THEN 1 ELSE 0 END) AS total_drawn_matches,
                SUM(CASE WHEN (is_match_no_result = 1 ) THEN 1 ELSE 0 END) AS total_abandoned_matches"
        ))
            ->addSelect([
                'total_matches_won' => Fixture::select(\DB::raw('COUNT(*)'))
                    ->when(!$attributes['team_id'], function ($q) use ($attributes) {
                        $q->whereHas('winnerTeam', function ($q) use ($attributes) {
                            $q->where('owner_id', $attributes['club_owner_id']);
                        });
                    })
                    ->when($attributes['team_id'], function ($q) use ($attributes) {
                        $q->where('match_winner_team_id', $attributes['team_id']);
                    })
                    ->when($attributes['match_overs'], function ($q) use ($attributes) {
                        $q->where('match_overs', $attributes['match_overs']);
                    })
                    ->when($attributes['ball_type'], function ($q) use ($attributes) {
                        $q->where('ball_type', $attributes['ball_type']);
                    })
                    ->when($attributes['year'], function ($q) use ($attributes) {
                        $q->whereYear('match_date', $attributes['year']);
                    })
                    ->when($attributes['match_type'], function ($q) use ($attributes) {
                        $q->where('match_type', $attributes['match_type']);
                    })
                    ->when($attributes['tournament_id'], function ($q) use ($attributes) {
                        $q->where('tournament_id', $attributes['tournament_id']);
                    })
                    ->when($attributes['tournament_category'], function ($q) use ($attributes) {
                        $q->whereHas('tournament', function ($q) use ($attributes) {
                            $q->where('tournament_category', $attributes['tournament_category']);
                        });
                    })
            ])
            ->addSelect([
                'total_matches_lost' => Fixture::select(\DB::raw('COUNT(*)'))
                    ->when(!$attributes['team_id'], function ($q) use ($attributes) {
                        $q->whereHas('loserTeam', function ($q) use ($attributes) {
                            $q->where('owner_id', $attributes['club_owner_id']);
                        });
                    })
                    ->when($attributes['team_id'], function ($q) use ($attributes) {
                        $q->where('match_loser_team_id', $attributes['team_id']);
                    })
                    ->when($attributes['match_overs'], function ($q) use ($attributes) {
                        $q->where('match_overs', $attributes['match_overs']);
                    })
                    ->when($attributes['ball_type'], function ($q) use ($attributes) {
                        $q->where('ball_type', $attributes['ball_type']);
                    })
                    ->when($attributes['year'], function ($q) use ($attributes) {
                        $q->whereYear('match_date', $attributes['year']);
                    })
                    ->when($attributes['match_type'], function ($q) use ($attributes) {
                        $q->where('match_type', $attributes['match_type']);
                    })
                    ->when($attributes['tournament_id'], function ($q) use ($attributes) {
                        $q->where('tournament_id', $attributes['tournament_id']);
                    })
                    ->when($attributes['tournament_category'], function ($q) use ($attributes) {
                        $q->whereHas('tournament', function ($q) use ($attributes) {
                            $q->where('tournament_category', $attributes['tournament_category']);
                        });
                    })
            ])
            ->addSelect([
                'total_tosses_won' => Fixture::select(\DB::raw('COUNT(*)'))
                    ->when(!$attributes['team_id'], function ($q) use ($attributes) {
                        $q->whereHas('tossWinnerTeam', function ($q) use ($attributes) {
                            $q->where('owner_id', $attributes['club_owner_id']);
                        });
                    })
                    ->when($attributes['team_id'], function ($q) use ($attributes) {
                        $q->where('toss_winner_team_id', $attributes['team_id']);
                    })
                    ->when($attributes['match_overs'], function ($q) use ($attributes) {
                        $q->where('match_overs', $attributes['match_overs']);
                    })
                    ->when($attributes['ball_type'], function ($q) use ($attributes) {
                        $q->where('ball_type', $attributes['ball_type']);
                    })
                    ->when($attributes['year'], function ($q) use ($attributes) {
                        $q->whereYear('match_date', $attributes['year']);
                    })
                    ->when($attributes['match_type'], function ($q) use ($attributes) {
                        $q->where('match_type', $attributes['match_type']);
                    })
                    ->when($attributes['tournament_id'], function ($q) use ($attributes) {
                        $q->where('tournament_id', $attributes['tournament_id']);
                    })
                    ->when($attributes['tournament_category'], function ($q) use ($attributes) {
                        $q->whereHas('tournament', function ($q) use ($attributes) {
                            $q->where('tournament_category', $attributes['tournament_category']);
                        });
                    })
            ])
            ->addSelect([
                'total_bat_first' => Inning::select(\DB::raw('COUNT(*)'))
                    ->whereColumn('fixture_id', 'fixtures.id')
                    ->where('is_first_innings', 1)
                    ->when(!$attributes['team_id'], function ($q) use ($attributes) {
                        $q->whereHas('batting_team', function ($q) use ($attributes) {
                            $q->where('owner_id', $attributes['club_owner_id']);
                        });
                    })
                    ->when($attributes['team_id'], function ($q) use ($attributes) {
                        $q->where('batting_team_id', $attributes['team_id']);
                    })
            ])
            ->addSelect([
                'total_field_first' => Inning::select(\DB::raw('COUNT(*)'))
                    ->whereColumn('fixture_id', 'fixtures.id')
                    ->where('is_first_innings', 1)
                    ->when(!$attributes['team_id'], function ($q) use ($attributes) {
                        $q->whereHas('bowling_team', function ($q) use ($attributes) {
                            $q->where('owner_id', $attributes['club_owner_id']);
                        });
                    })
                    ->when($attributes['team_id'], function ($q) use ($attributes) {
                        $q->where('bowling_team_id', $attributes['team_id']);
                    })
            ])
            ->where('is_match_start', 1)
            ->where('is_match_finished', 1)
            ->when($attributes['team_id'], function ($q) use ($attributes) {
                $q->where(function ($q) use ($attributes) {
                    $q->where('home_team_id', $attributes['team_id'])
                        ->orWhere('away_team_id', $attributes['team_id']);
                });
            })
            ->when(!$attributes['team_id'], function ($q) use ($attributes) {
                $q->where(function ($q) use ($attributes) {
                    $q
                        ->wherehas('homeTeam', function ($q) use ($attributes) {
                            $q->where('owner_id', $attributes['club_owner_id']);
                        })
                        ->orWherehas('awayTeam', function ($q) use ($attributes) {
                            $q->where('owner_id', $attributes['club_owner_id']);
                        });
                });
            })
            ->when($attributes['match_overs'], function ($q) use ($attributes) {
                $q->where('match_overs', $attributes['match_overs']);
            })
            ->when($attributes['ball_type'], function ($q) use ($attributes) {
                $q->where('ball_type', $attributes['ball_type']);
            })
            ->when($attributes['year'], function ($q) use ($attributes) {
                $q->whereYear('match_date', $attributes['year']);
            })
            ->when($attributes['match_type'], function ($q) use ($attributes) {
                $q->where('match_type', $attributes['match_type']);
            })
            ->when($attributes['tournament_id'], function ($q) use ($attributes) {
                $q->where('tournament_id', $attributes['tournament_id']);
            })
            ->when($attributes['tournament_category'], function ($q) use ($attributes) {
                $q->whereHas('tournament', function ($q) use ($attributes) {
                    $q->where('tournament_category', $attributes['tournament_category']);
                });
            })
            ->first();
    }
    //  ====================================== Club stats by filter end=====================================================

    //  ====================================== Club leaderboard by filter start ============================================
    public function getClubBattingLeaderboardByFilterQuery($data)
    {
        $attributes = [
            'club_owner_id' => $data['club_owner_id'],
            'team_id' => $data['team_id'] ?? null,
            'match_overs' => $data['match_overs'] ?? null,
            'ball_type' => $data['ball_type'] ?? null,
            'year' => $data['year'] ?? null,
            'match_type' => $data['match_type'] ?? null,
            'tournament_id' => $data['tournament_id'] ?? null,
            'tournament_category' => $data['tournament_category'] ?? null,
            'limit' => $data['limit'] ?? 10,
        ];

        $isFilterEnabled = $attributes['match_overs'] || $attributes['ball_type'] || $attributes['year'] ||
            $attributes['match_type'] || $attributes['tournament_id'] || $attributes['tournament_category'];

        return InningBatterResult::select(
            'batter_id',
            'users.first_name',
            'users.last_name',
            'users.profile_pic',
            \DB::raw(
                "COUNT(DISTINCT inning_id) AS total_innings_played,
                SUM(runs_achieved) AS total_runs_achieved,
                SUM(balls_faced) AS total_balls_faced,
                MAX(runs_achieved) AS highest_runs_scored,
                SUM(CASE WHEN is_out = 1 THEN 1 ELSE 0 END) AS total_outs,
                SUM(CASE WHEN is_out = 0 THEN 1 ELSE 0 END) AS total_not_outs,
                SUM(fours) AS total_fours_hit,
                SUM(sixes) AS total_sixes_hit,
                SUM(CASE WHEN runs_achieved >= 50 THEN 1 ELSE 0 END) AS total_fifties,
                SUM(CASE WHEN runs_achieved >= 100 THEN 1 ELSE 0 END) AS total_hundreds"
            )
        )
            ->leftJoin('users', 'inning_batter_results.batter_id', '=', 'users.id')
            ->when(!$attributes['team_id'], function ($q) use ($attributes) {
                $q->whereHas('team', function ($q) use ($attributes) {
                    $q->where('owner_id', $attributes['club_owner_id']);
                });
            })
            ->when($attributes['team_id'], function ($q) use ($attributes) {
                $q->where('team_id', $attributes['team_id']);
            })
            ->when($isFilterEnabled, function ($query) use ($attributes) {
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
            })
            ->groupBy('batter_id')
            ->orderByDesc('total_runs_achieved')
            ->orderBy('total_balls_faced')
            ->paginate($attributes['limit']);
    }

    public function getClubBattingLeaderboardByFilterQueryTestV2($data)
    {
        $attributes = [
            'club_owner_id' => $data['club_owner_id'],
            'team_id' => $data['team_id'] ?? null,
            'match_overs' => $data['match_overs'] ?? null,
            'ball_type' => $data['ball_type'] ?? null,
            'year' => $data['year'] ?? null,
            'match_type' => $data['match_type'] ?? null,
            'tournament_id' => $data['tournament_id'] ?? null,
            'tournament_category' => $data['tournament_category'] ?? null,
            'limit' => $data['limit'] ?? 10,
        ];

        $isFilterEnabled = $attributes['match_overs'] || $attributes['ball_type'] || $attributes['year'] ||
            $attributes['match_type'] || $attributes['tournament_id'] || $attributes['tournament_category'];

        return ClubPlayer::select(
            'player_id AS batter_id',
            'users.first_name',
            'users.last_name',
            'users.profile_pic',
            \DB::raw(
                "COUNT(DISTINCT inning_id) AS total_innings_played,
                SUM(runs_achieved) AS total_runs_achieved,
                SUM(balls_faced) AS total_balls_faced,
                MAX(runs_achieved) AS highest_runs_scored,
                SUM(CASE WHEN is_out = 1 THEN 1 ELSE 0 END) AS total_outs,
                SUM(CASE WHEN is_out = 0 THEN 1 ELSE 0 END) AS total_not_outs,
                SUM(fours) AS total_fours_hit,
                SUM(sixes) AS total_sixes_hit,
                SUM(CASE WHEN runs_achieved >= 50 THEN 1 ELSE 0 END) AS total_fifties,
                SUM(CASE WHEN runs_achieved >= 100 THEN 1 ELSE 0 END) AS total_hundreds"
            )
        )
            ->leftJoin('users', 'club_players.player_id', '=', 'users.id')
            ->leftJoin('inning_batter_results', function ($j) use ($attributes) {
                $j
                    ->on('inning_batter_results.batter_id', '=', 'club_players.player_id')
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
                    });
            })
            ->when(!$attributes['team_id'], function ($q) use ($attributes) {
                $q
                    ->where('club_owner_id', $attributes['club_owner_id'])
                    ->where('status', 'ACCEPTED');
            })
            ->when($attributes['team_id'], function ($q) use ($attributes) {
                $q->whereIn('player_id', function ($q) use ($attributes) {
                    $q
                        ->select('player_id')
                        ->from('team_players')
                        ->where('team_id', $attributes['team_id']);
                });
            })
            ->when($isFilterEnabled, function ($query) use ($attributes) {
                $query
                    ->leftJoin('fixtures', 'inning_batter_results.fixture_id', '=', 'fixtures.id')
                    ->when($attributes['match_overs'], function ($query) use ($attributes) {
                        $query->where('fixtures.match_overs', $attributes['match_overs']);
                    })
                    ->when($attributes['ball_type'], function ($query) use ($attributes) {
                        $query->where('fixtures.ball_type', $attributes['ball_type']);
                    })
                    ->when($attributes['year'], function ($query) use ($attributes) {
                        $query->whereYear('fixtures.match_date', $attributes['year']);
                    })
                    ->when($attributes['match_type'], function ($query) use ($attributes) {
                        $query->where('fixtures.match_type', $attributes['match_type']);
                    })
                    ->when($attributes['tournament_id'], function ($query) use ($attributes) {
                        $query->where('fixtures.tournament_id', $attributes['tournament_id']);
                    })
                    ->when($attributes['tournament_category'], function ($query) use ($attributes) {
                        $query->whereIn('fixtures.tournament_id', function ($query) use ($attributes) {
                            $query
                                ->select('id')
                                ->from('tournaments')
                                ->where('tournament_category', $attributes['tournament_category']);
                        });
                    });
            })
            ->groupBy('player_id')
            ->orderByDesc('total_runs_achieved')
            ->orderBy('total_balls_faced')
            ->paginate($attributes['limit']);
    }

    public function getClubBattingLeaderboardByFilterQueryTestV3($data)
    {
        $attributes = [
            'club_owner_id' => $data['club_owner_id'],
            'team_id' => $data['team_id'] ?? null,
            'match_overs' => $data['match_overs'] ?? null,
            'ball_type' => $data['ball_type'] ?? null,
            'year' => $data['year'] ?? null,
            'match_type' => $data['match_type'] ?? null,
            'tournament_id' => $data['tournament_id'] ?? null,
            'tournament_category' => $data['tournament_category'] ?? null,
            'limit' => $data['limit'] ?? 10,
            'is_filter_enabled' => 0,
            'stats_type' => 'BATTING'
        ];

        $attributes['is_filter_enabled'] = ($attributes['match_overs'] || $attributes['ball_type'] || $attributes['year'] ||
            $attributes['match_type'] || $attributes['tournament_id'] || $attributes['tournament_category']);

        return User::select(
            'id AS batter_id',
            'first_name',
            'last_name',
            'profile_pic',
        )
            ->clubTeamFilter($attributes)
            ->withCount(['inningsBattingResults as total_innings_played' => function ($q) use ($attributes) {
                $q->clubFilter($attributes);
            }])
            ->withSum(['inningsBattingResults as total_runs_achieved' => function ($q) use ($attributes) {
                $q->clubFilter($attributes);
            }], 'runs_achieved')
            ->withSum(['inningsBattingResults as total_balls_faced' => function ($q) use ($attributes) {
                $q->clubFilter($attributes);
            }], 'balls_faced')
            ->withMax(['inningsBattingResults as highest_runs_scored' => function ($q) use ($attributes) {
                $q->clubFilter($attributes);
            }], 'runs_achieved')
            ->withCount(['inningsBattingResults as total_outs' => function ($q) use ($attributes) {
                $q->where('is_out', 1)->clubFilter($attributes);
            }])
            ->withCount(['inningsBattingResults as total_not_outs' => function ($q) use ($attributes) {
                $q->where('is_out', 0)->clubFilter($attributes);
            }])
            ->withSum(['inningsBattingResults as total_fours_hit' => function ($q) use ($attributes) {
                $q->clubFilter($attributes);
            }], 'fours')
            ->withSum(['inningsBattingResults as total_sixes_hit' => function ($q) use ($attributes) {
                $q->clubFilter($attributes);
            }], 'sixes')
            ->withCount(['inningsBattingResults as total_fifties' => function ($q) use ($attributes) {
                $q->where('runs_achieved', '>=', 50)->clubFilter($attributes);
            }])
            ->withCount(['inningsBattingResults as total_hundreds' => function ($q) use ($attributes) {
                $q->where('runs_achieved', '>=', 100)->clubFilter($attributes);
            }])
            ->orderByDesc('total_runs_achieved')
            ->orderBy('total_balls_faced')
            ->paginate($attributes['limit']);
    }

    public function getClubBowlingLeaderboardByFilterQuery($data)
    {
        $attributes = [
            'club_owner_id' => $data['club_owner_id'],
            'team_id' => $data['team_id'] ?? null,
            'match_overs' => $data['match_overs'] ?? null,
            'ball_type' => $data['ball_type'] ?? null,
            'year' => $data['year'] ?? null,
            'match_type' => $data['match_type'] ?? null,
            'tournament_id' => $data['tournament_id'] ?? null,
            'tournament_category' => $data['tournament_category'] ?? null,
            'limit' => $data['limit'] ?? 10,
        ];

        $isFilterEnabled = $attributes['match_overs'] || $attributes['ball_type'] || $attributes['year'] ||
            $attributes['match_type'] || $attributes['tournament_id'] || $attributes['tournament_category'];

        return ClubPlayer::select(
            'player_id AS bowler_id',
            'users.first_name',
            'users.last_name',
            'users.profile_pic',
            \DB::raw(
                "
            COUNT(DISTINCT inning_id) AS total_innings_played,
            SUM(wickets) AS total_wickets,
            SUM(maiden_overs) AS total_maiden_overs,
            MAX(wickets) AS highest_wickets,
            SUM(legal_balls_bowled) AS total_balls_bowled,
            SUM(runs_gave) AS total_runs_gave"
            )
        )
            ->leftJoin('users', 'club_players.player_id', '=', 'users.id')
            ->leftJoin('inning_bowler_results', function ($j) use ($attributes) {
                $j
                    ->on('inning_bowler_results.bowler_id', '=', 'club_players.player_id')
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
                    });
            })
            ->when(!$attributes['team_id'], function ($q) use ($attributes) {
                $q
                    ->where('club_owner_id', $attributes['club_owner_id'])
                    ->where('status', 'ACCEPTED');
            })
            ->when($attributes['team_id'], function ($q) use ($attributes) {
                $q->whereIn('player_id', function ($q) use ($attributes) {
                    $q
                        ->select('player_id')
                        ->from('team_players')
                        ->where('team_id', $attributes['team_id']);
                });
            })
            ->when($isFilterEnabled, function ($query) use ($attributes) {
                $query
                    ->leftJoin('fixtures', 'inning_bowler_results.fixture_id', '=', 'fixtures.id')
                    ->when($attributes['match_overs'], function ($query) use ($attributes) {
                        $query->where('fixtures.match_overs', $attributes['match_overs']);
                    })
                    ->when($attributes['ball_type'], function ($query) use ($attributes) {
                        $query->where('fixtures.ball_type', $attributes['ball_type']);
                    })
                    ->when($attributes['year'], function ($query) use ($attributes) {
                        $query->whereYear('fixtures.match_date', $attributes['year']);
                    })
                    ->when($attributes['match_type'], function ($query) use ($attributes) {
                        $query->where('fixtures.match_type', $attributes['match_type']);
                    })
                    ->when($attributes['tournament_id'], function ($query) use ($attributes) {
                        $query->where('fixtures.tournament_id', $attributes['tournament_id']);
                    })
                    ->when($attributes['tournament_category'], function ($query) use ($attributes) {
                        $query->whereIn('fixtures.tournament_id', function ($query) use ($attributes) {
                            $query
                                ->select('id')
                                ->from('tournaments')
                                ->where('tournament_category', $attributes['tournament_category']);
                        });
                    });
            })
            ->groupBy('player_id')
            ->orderByDesc('total_wickets')
            ->orderBy('total_balls_bowled')
            ->paginate($attributes['limit']);
    }

    public function getClubBowlingLeaderboardByFilterQueryTestV3($data)
    {
        $attributes = [
            'club_owner_id' => $data['club_owner_id'],
            'team_id' => $data['team_id'] ?? null,
            'match_overs' => $data['match_overs'] ?? null,
            'ball_type' => $data['ball_type'] ?? null,
            'year' => $data['year'] ?? null,
            'match_type' => $data['match_type'] ?? null,
            'tournament_id' => $data['tournament_id'] ?? null,
            'tournament_category' => $data['tournament_category'] ?? null,
            'limit' => $data['limit'] ?? 10,
            'is_filter_enabled' => 0,
            'stats_type' => 'BOWLING'
        ];

        $attributes['is_filter_enabled'] = ($attributes['match_overs'] || $attributes['ball_type'] || $attributes['year'] ||
            $attributes['match_type'] || $attributes['tournament_id'] || $attributes['tournament_category']);

        return User::select(
            'id AS bowler_id',
            'first_name',
            'last_name',
            'profile_pic',
        )
            ->clubTeamFilter($attributes)
            ->withCount(['inningsBowlingResults as total_innings_played' => function ($q) use ($attributes) {
                $q->clubFilter($attributes);
            }])
            ->withSum(['inningsBowlingResults as total_wickets' => function ($q) use ($attributes) {
                $q->clubFilter($attributes);
            }], 'wickets')
            ->withSum(['inningsBowlingResults as total_maiden_overs' => function ($q) use ($attributes) {
                $q->clubFilter($attributes);
            }], 'maiden_overs')
            ->withMax(['inningsBowlingResults as highest_wickets' => function ($q) use ($attributes) {
                $q->clubFilter($attributes);
            }], 'wickets')
            ->withSum(['inningsBowlingResults as total_balls_bowled' => function ($q) use ($attributes) {
                $q->clubFilter($attributes);
            }], 'legal_balls_bowled')
            ->withSum(['inningsBowlingResults as total_runs_gave' => function ($q) use ($attributes) {
                $q->clubFilter($attributes);
            }], 'runs_gave')
            ->orderByDesc('total_wickets')
            ->orderBy('total_balls_bowled')
            ->paginate($attributes['limit']);
    }

    public function getClubFieldingLeaderboardByFilterQuery($data)
    {
        $attributes = [
            'club_owner_id' => $data['club_owner_id'],
            'team_id' => $data['team_id'] ?? null,
            'match_overs' => $data['match_overs'] ?? null,
            'ball_type' => $data['ball_type'] ?? null,
            'year' => $data['year'] ?? null,
            'match_type' => $data['match_type'] ?? null,
            'tournament_id' => $data['tournament_id'] ?? null,
            'tournament_category' => $data['tournament_category'] ?? null,
            'limit' => $data['limit'] ?? 10,
        ];

        $isFilterEnabled = $attributes['match_overs'] || $attributes['ball_type'] || $attributes['year'] ||
            $attributes['match_type'] || $attributes['tournament_id'] || $attributes['tournament_category'];

        return ClubPlayer::select(
            'club_players.player_id',
            'users.first_name',
            'users.last_name',
            'users.profile_pic',
            \DB::raw("COUNT(DISTINCT playing_elevens.fixture_id) AS total_matches,
                SUM(CASE WHEN caught_by = club_players.player_id OR assist_by = club_players.player_id THEN 1 WHEN stumped_by = club_players.player_id OR run_out_by = club_players.player_id THEN 1 ELSE 0 END) AS total_dismissals,
                SUM(CASE WHEN caught_by = club_players.player_id THEN 1 ELSE 0 END) AS total_catches,
                SUM(CASE WHEN run_out_by = club_players.player_id THEN 1 ELSE 0 END) AS total_run_outs")
        )
            ->leftJoin('users', 'club_players.player_id', '=', 'users.id')
            ->leftJoin('inning_batter_results', function ($join) use ($attributes) {
                $join
                    ->on('playing_elevens.player_id', '=', 'inning_batter_results.caught_by')
                    ->orOn('playing_elevens.player_id', '=', 'inning_batter_results.assist_by')
                    ->orOn('playing_elevens.player_id', '=', 'inning_batter_results.stumped_by')
                    ->orOn('playing_elevens.player_id', '=', 'inning_batter_results.run_out_by')
                    ->when(!$attributes['team_id'], function ($q) use ($attributes) {
                        $q->whereIn('inning_batter_results.team_id', function ($q) use ($attributes) {
                            $q
                                ->select('id')
                                ->from('teams')
                                ->where('owner_id', $attributes['club_owner_id']);
                        });
                    })
                    ->when($attributes['team_id'], function ($q) use ($attributes) {
                        $q->where('inning_batter_results.team_id', $attributes['team_id']);
                    })
                    ->where('inning_batter_results.is_out', 1);
            })
            ->when(!$attributes['team_id'], function ($q) use ($attributes) {
                $q
                    ->where('club_owner_id', $attributes['club_owner_id'])
                    ->where('status', 'ACCEPTED');
            })
            ->when($attributes['team_id'], function ($q) use ($attributes) {
                $q->whereIn('club_players.player_id', function ($q) use ($attributes) {
                    $q
                        ->select('player_id')
                        ->from('team_players')
                        ->where('team_id', $attributes['team_id']);
                });
            })
            ->when($isFilterEnabled, function ($query) use ($attributes) {
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
            })
            ->groupBy('club_players.player_id')
            ->groupBy('playing_elevens.fixture_id')
            ->orderByDesc('total_dismissals')
            ->orderBy('total_matches')
            ->paginate($attributes['limit']);
    }

    public function getClubFieldingLeaderboardByFilterQueryTestV3($data)
    {
        $attributes = [
            'club_owner_id' => $data['club_owner_id'],
            'team_id' => $data['team_id'] ?? null,
            'match_overs' => $data['match_overs'] ?? null,
            'ball_type' => $data['ball_type'] ?? null,
            'year' => $data['year'] ?? null,
            'match_type' => $data['match_type'] ?? null,
            'tournament_id' => $data['tournament_id'] ?? null,
            'tournament_category' => $data['tournament_category'] ?? null,
            'limit' => $data['limit'] ?? 10,
            'is_filter_enabled' => 0,
            'stats_type' => 'FIELDING'
        ];

        $data['is_filter_enabled'] = $attributes['match_overs'] || $attributes['ball_type'] || $attributes['year'] ||
            $attributes['match_type'] || $attributes['tournament_id'] || $attributes['tournament_category'];

        return User::select(
            'id AS player_id',
            'first_name',
            'last_name',
            'profile_pic',
        )
            ->clubTeamFilter($attributes)
            ->withCount(['matchesPlayed as total_matches' => function ($q) use ($attributes) {
                $q->clubFilter($attributes);
            }])
            ->withCount(['inningsCaughtOutResults as total_catches' => function ($q) use ($attributes) {
                $q->clubFilter($attributes);
            }])
            ->withCount(['inningsRunOutResults as total_run_outs' => function ($q) use ($attributes) {
                $q->clubFilter($attributes);
            }])
            ->withCount(['inningsAssistedOutResults as total_assisted_outs' => function ($q) use ($attributes) {
                $q->clubFilter($attributes);
            }])
            ->withCount(['inningsStumpedOutResults as total_stumped_outs' => function ($q) use ($attributes) {
                $q->clubFilter($attributes);
            }])
            ->orderByRaw('(total_catches + total_run_outs + total_assisted_outs + total_stumped_outs) DESC')
            ->orderBy('total_matches')
            ->paginate($attributes['limit']);
    }
    //  ====================================== Club leaderboard by filter end ==============================================
}
