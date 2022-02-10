<?php

namespace App\Http\Controllers\Team;

use App\Models\ClubPlayer;
use App\Models\Delivery;
use App\Models\Fixture;
use App\Models\InningBatterResult;
use App\Models\InningBowlerResult;
use App\Models\PlayingEleven;
use App\Models\Team;
use App\Models\TeamPlayer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TeamQuery
{
// ======================================== Special Validation queries start ==============================================
    //  Checking a valid club owner
    public function checkValidClubOwnerQuery($ownerId)
    {
        return User::where('id', $ownerId)
            ->where('registration_type', 'CLUB_OWNER')
            ->first();
    }

    //  Checking team exist and owner is valid or not
    public function checkValidTeamOwnerQuery($ownerId, $teamId)
    {
        return Team::where('id', $teamId)
            ->where('owner_id', $ownerId)
            ->first();
    }

    //  Checking player is in club or not
    public function checkValidClubPlayerQuery($ownerId, $playerId)
    {
        return ClubPlayer::where('club_owner_id', $ownerId)
            ->where('player_id', $playerId)
            ->where('status', 'ACCEPTED')
            ->first();
    }

    //  Checking is player already exist in team
    public function isPlayerAlreadyExistsQuery($teamId, $playerId)
    {
        return TeamPlayer::where('team_id', $teamId)
            ->where('player_id', $playerId)
            ->first();
    }

    // getting team by id
    public function getTeamByIdQuery($teamId)
    {
        return Team::select('id', 'team_name', 'team_unique_name', 'team_short_name', 'team_banner', 'team_logo', 'city', 'owner_id', 'captain_id', 'wicket_keeper_id')
            ->where('id', $teamId)
            ->first();
    }

    // getting numbers of player
    public function getTeamPlayersNumber($teamId, $squadType = null)
    {
        return TeamPlayer::where('team_id', $teamId)
            ->when($squadType, function ($query) use ($squadType) {
                $query->where('squad_type', $squadType);
            })
            ->count();
    }

// ======================================== Special Validation queries end ==============================================

// ================================================== Team CRUD start ============================================
    public function getOwnerTeamsListQuery($data)
    {
        $lastId = $data['last_id'] ?? null;
        $limit = $data['limit'] ?? null;

        return Team::select('id', 'team_name', 'team_unique_name', 'team_short_name', 'city', 'team_logo', 'team_banner', 'captain_id')
            ->with('captain:id,first_name,last_name')
            ->where('owner_id', $data['club_owner_id'])
            ->when($lastId, function ($query) use ($lastId) {
                $query->where('id', '>', $lastId);
            })
            ->limit($limit)
            ->get();
    }

    public function myTeams($data)
    {
        $status = $data['status'] ?? null;
        $user = Auth::user();
        $uid =$user->id;
        $type = $user->registration_type;
        $lastId = $data['last_id'] ?? null;
        $limit = $data['limit'] ?? 15;

        return Team::select('id', 'team_name', 'team_unique_name', 'team_short_name', 'city', 'team_logo', 'team_banner', 'captain_id')
            ->with('captain:id,first_name,last_name')
            ->when($status === "my_teams" && $type ==="CLUB_OWNER" , function($q) use($uid){
                $q->where('owner_id', $uid);
            })
            ->when($status === "my_teams" && $type ==="PLAYER" , function($q) use($uid){
                $q->whereHas('teamPlayers', function (Builder $q2) use($uid) {
                    $q2->where('player_id', $uid);
                });
            })
            ->when($lastId, function ($query) use ($lastId) {
                $query->where('id', '<', $lastId);
            })
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function createTeamQuery($obj)
    {
        $team = Team::create($obj);

        return Team::select('id', 'team_name', 'city', 'team_logo', 'team_banner', 'captain_id')
            ->with('captain:id,first_name,last_name')
            ->where('id', $team->id)
            ->first();
    }

    public function updateTeamQuery($teamId, $ownerId, $obj)
    {
        return Team::where('id', $teamId)
            ->where('owner_id', $ownerId)
            ->update($obj);
    }

    public function deleteTeamQuery($teamId, $ownerId)
    {
        return Team::where('id', $teamId)
            ->where('owner_id', $ownerId)
            ->delete();
    }

    public function matchesPlayed($teamId){
        return Fixture::where('home_team_id', $teamId)->orWhere('away_team_id', $teamId)->count();
    }

    //single team info
//    public function getSingleTeam($teamId)
//    {
//        $team = Team::where('id', $teamId)
//            ->select('id', 'owner_id', 'captain_id', 'team_name', 'team_short_name', 'team_logo', 'team_banner', 'city')
//            ->withCount(['playerRequests as pending_players' => function ($query) {
//                $query->where('status', 'PENDING');
//            }])
//            ->first();
//
//        return $team;
//    }

// ================================================== Team CRUD End ============================================

//  ============================================== Team Players CRUD start ================================================
    public function searchClubPlayersQuery($data)
    {
        $clubOwnerId = Auth::id();
        $teamId = $data['team_id'];
        $lastId = $data['last_id'] ?? null;
        $limit = $data['limit'] ?? 10;

        return User::select('id', 'first_name', 'last_name', 'city', 'playing_role', 'profile_pic')
            ->whereHas('memberOfClubs', function ($playerId) use ($clubOwnerId) {
                $playerId
                    ->where('club_owner_id', $clubOwnerId)
                    ->where('status', 'ACCEPTED');
            })
            ->whereDoesntHave('memberOfTeams', function ($playerId) use ($teamId) {
                $playerId
                    ->where('team_id', $teamId);
            })
            ->when(isset($data['term']), function($q) use($data){
                $q
                ->where(function ($playerId) use ($data) {
                    $term = "%{$data['term']}%";
                    $playerId
                        ->where(DB::raw('CONCAT(first_name, " ", last_name)'), 'LIKE', $term)
                        ->orWhere(DB::raw('CONCAT(last_name, " ", first_name)'), 'LIKE', $term)
                        ->orWhere('username', 'LIKE', $term)
                        ->orWhere('email', 'LIKE', $term)
                        ->orWhere('phone', 'LIKE', $term);
                });
            })
            ->when($lastId, function ($playerId) use ($lastId) {
                $playerId->where('id', '>', $lastId);
            })
            ->limit($limit)
            ->get();
    }

    public function getTeamPlayersListQuery($data)
    {
        $teamId = $data['team_id'];
        $squadType = $data['squad_type'] ?? null;
        $lastId = $data['last_id'] ?? null;
//        $limit = $data['limit'];

        return User::select('id', 'first_name', 'last_name', 'city', 'playing_role', 'profile_pic')
            ->whereHas('memberOfTeams', function ($query) use ($teamId, $squadType) {
                $query
                    ->where('team_id', $teamId)
                    ->when($squadType, function ($query) use ($squadType) {
                        $query->where('squad_type', $squadType);
                    });
            })
            ->when($lastId, function ($query) use ($lastId) {
                $query->where('id', '>', $lastId);
            })
//            ->limit($limit)
            ->get();
    }

    public function addTeamPlayerQuery($attributes)
    {
        return TeamPlayer::create($attributes);
    }

    public function removeTeamPlayerQuery($teamId, $playerId)
    {
        return TeamPlayer::where('team_id', $teamId)
            ->where('player_id', $playerId)
            ->delete();
    }

    public function resetTeamCaptainOrWicketKeeper($teamOrOwnerKey, $teamOrOwnerValue, $playerTypeKey, $playerId){
        // teamOrOwnerKey => id or owner_id
        // playerTypeKey => captain_id or wicket_keeper_id
        return Team::where($teamOrOwnerKey, $teamOrOwnerValue)
            ->where($playerTypeKey, $playerId)
            ->update([$playerTypeKey => null]);
    }
//  =========================================== Team Players CRUD end ================================================

//  ======================================== Team Squads start ==================================================
    public function getTeamSquadListQuery($data)
    {
        $teamId = $data['team_id'];

        return User::select('users.id', 'first_name', 'last_name', 'city', 'playing_role', 'profile_pic', 'squad_type', 'nid_pic')
            ->addSelect([
                'captain_id' => Team::select('captain_id')
                    ->where('id', $teamId)
                    ->limit(1)
            ])
            ->addSelect([
                'wicket_keeper_id' => Team::select('wicket_keeper_id')
                    ->where('id', $teamId)
                    ->limit(1)
            ])
            ->where('team_id', $teamId)
            ->join('team_players', 'users.id', '=', 'team_players.player_id')
            ->orderBy('squad_type')
            ->get();
    }

    public function updateTeamSquadQuery($teamId, $playerId, $squadType)
    {
        return TeamPlayer::where('team_id', $teamId)
            ->where('player_id', $playerId)
            ->update(['squad_type' => $squadType]);
    }
//  ======================================== Team Squads end ====================================================

//    Team insights start
    public function getTeamTossInsightsQuery($teamId, $data = null)
    {
        $matchOvers = $data['match_overs'] ?? null;
        $ballType = $data['ball_type'] ?? null;
        $year = $data['year'] ?? null;
        $matchType = $data['match_type'] ?? null;
        $tournamentId = $data['tournament_id'] ?? null;
        $tournamentCategory = $data['tournament_category'] ?? null;

        return Fixture::where(function ($query) use ($teamId) {
            $query->where('home_team_id', $teamId)
                ->orWhere('away_team_id', $teamId);
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
            ->when($tournamentCategory, function ($query) use ($tournamentCategory, $teamId) {
                $query->whereIn('tournament_id', function ($query) use ($tournamentCategory, $teamId) {
                    $query->select('id')->from('tournaments')->where('tournament_category', $tournamentCategory)->whereIn('id', function ($query) use ($teamId) {
                        $query->select('tournament_id')->from('tournament_teams')->where('team_id', $teamId);
                    });
                });
            })
            ->select(DB::raw(
                "SUM(CASE WHEN toss_winner_team_id = $teamId THEN 1 ELSE 0 END) AS total_toss_wins,
                SUM(CASE WHEN toss_winner_team_id != $teamId THEN 1 ELSE 0 END) AS total_toss_losses,
                SUM(CASE WHEN toss_winner_team_id = $teamId AND team_elected_to = 'BAT' THEN 1 ELSE 0 END) AS total_toss_won_bat_first,
                SUM(CASE WHEN toss_winner_team_id = $teamId AND team_elected_to = 'BOWL' THEN 1 ELSE 0 END) AS total_toss_won_bowl_first,
                SUM(CASE WHEN toss_winner_team_id != $teamId AND team_elected_to = 'BAT' THEN 1 ELSE 0 END) AS total_toss_lost_bat_first,
                SUM(CASE WHEN toss_winner_team_id != $teamId AND team_elected_to = 'BOWL' THEN 1 ELSE 0 END) AS total_toss_lost_bowl_first,
                (AVG(CASE WHEN is_match_finished = 1 AND (toss_winner_team_id = $teamId AND match_winner_team_id = $teamId) THEN 1 ELSE 0 END) * 100) AS total_toss_won_match_wins_percentage,
                (AVG(CASE WHEN is_match_finished = 1 AND (toss_winner_team_id != $teamId AND match_winner_team_id = $teamId) THEN 1 ELSE 0 END) * 100) AS total_toss_lost_match_wins_percentage"
            ))
            ->first();
    }

    public function getTeamOverallInsightsQuery($teamId, $data = null)
    {
        $matchOvers = $data['match_overs'] ?? null;
        $ballType = $data['ball_type'] ?? null;
        $year = $data['year'] ?? null;
        $matchType = $data['match_type'] ?? null;
        $tournamentId = $data['tournament_id'] ?? null;
        $tournamentCategory = $data['tournament_category'] ?? null;

        return Fixture::where(function ($query) use ($teamId) {
            $query->where('home_team_id', $teamId)
                ->orWhere('away_team_id', $teamId);
        })->where('is_match_finished', 1)
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
            ->when($tournamentCategory, function ($query) use ($tournamentCategory, $teamId) {
                $query->whereIn('tournament_id', function ($query) use ($tournamentCategory, $teamId) {
                    $query->select('id')->from('tournaments')->where('tournament_category', $tournamentCategory)->whereIn('id', function ($query) use ($teamId) {
                        $query->select('tournament_id')->from('tournament_teams')->where('team_id', $teamId);
                    });
                });
            })
            ->select(DB::raw(
                "MAX(CASE WHEN home_team_id = $teamId THEN home_team_runs ELSE away_team_runs END) AS highest_runs_score,
                    AVG(CASE WHEN home_team_id = $teamId THEN home_team_runs ELSE away_team_runs END) AS avg_runs_score,
                    MIN(CASE WHEN (match_winner_team_id = $teamId AND ((toss_winner_team_id = $teamId AND team_elected_to = 'BAT') OR (toss_winner_team_id != $teamId AND team_elected_to = 'BOWL'))) THEN
                    (CASE WHEN home_team_id = match_winner_team_id THEN home_team_runs ELSE away_team_runs END) END) AS lowest_defended_runs,
                    AVG(CASE WHEN (match_winner_team_id = $teamId AND ((toss_winner_team_id = $teamId AND team_elected_to = 'BAT') OR (toss_winner_team_id != $teamId AND team_elected_to = 'BOWL'))) THEN
                    (CASE WHEN home_team_id = match_winner_team_id THEN home_team_runs ELSE away_team_runs END) END) AS avg_defended_runs,
                    MAX(CASE WHEN (match_winner_team_id = $teamId AND ((toss_winner_team_id = $teamId AND team_elected_to = 'BOWL') OR (toss_winner_team_id != $teamId AND team_elected_to = 'BAT'))) THEN
                    (CASE WHEN home_team_id = match_winner_team_id THEN home_team_runs ELSE away_team_runs END) END) AS highest_chased_runs,
                    MAX(CASE WHEN home_team_id != $teamId THEN home_team_runs ELSE away_team_runs END) AS highest_runs_given",
            ))
            ->first();
    }

}
