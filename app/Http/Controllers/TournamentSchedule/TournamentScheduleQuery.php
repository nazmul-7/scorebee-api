<?php

namespace App\Http\Controllers\TournamentSchedule;

use App\Models\Tournament;
use App\Models\LeagueGroup;
use App\Models\LeagueGroupTeam;
use App\Models\TournamentTeam;
use App\Models\Fixture;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Log;

class TournamentScheduleQuery
{
    public function getRoundsQuery($tournament_id)
    {
        return Tournament::select('id', 'total_groups', 'group_winners', 'third_position', 'league_format', 'group_settings', 'match_type', 'ball_type')->where('id', $tournament_id)->first();
    }

    public function updateTournamentQuery($data)
    {
        return Tournament::where('id', $data['id'])->update($data);
    }

    public function getGroupsQuery($tournament_id)
    {
        return LeagueGroup::where('tournament_id', $tournament_id)->get();
    }

    public function getTournamentFixtureCountQuery($tournament_id)
    {
        return Fixture::where('tournament_id', $tournament_id)->count();
    }

    public function getGroupDetailsQuery($id)
    {
        return LeagueGroup::where('id', $id)->first();
    }

    public function storeGroupsQuery($data)
    {
        return LeagueGroup::create($data);
    }

    public function clearGroupsQuery($tournament_id)
    {
        return LeagueGroup::where('tournament_id', $tournament_id)->delete();
    }

    public function storeMultipleGroupsQuery($data)
    {
        return LeagueGroup::insert($data);
    }

    public function updateLeagueGroup($data, $key)
    {
        return LeagueGroup::where($key, $data[$key])->update($data);
    }

    public function getGroupsTeamsQuery($group_id)
    {
        // return LeagueGroupTeam::where('league_group_id',$group_id)->get();
        return Team::whereIn('id', function ($query) use ($group_id) {
            $query->select('team_id')->from('league_group_teams')->where('league_group_id', $group_id);
        })->orderBy('id', 'desc')->get();
        // return Team::orderBy('id', 'desc')->get();
    }

    public function updateLeagueGroupTeams($groupId, $data)
    {
        $storeIds = [];
        foreach ($data as $value) {
            $return_data = LeagueGroupTeam::updateOrCreate(
                [
                    'tournament_id' => $value['tournament_id'],
                    'league_group_id' => $value['league_group_id'],
                    'team_id' => $value['team_id'],
                ]
            );
            array_push($storeIds, $return_data->id);
        }


        LeagueGroupTeam::where('league_group_id', $groupId)
            ->whereNotIN('id', $storeIds)
            ->delete();
    }

    public function getAccpetedtournamentTeamsQuery($tournament_id)
    {
        return TournamentTeam::where('tournament_id', $tournament_id)->where('status', 'ACCEPTED')->get();
    }

    public function insertGroupsTeamsQuery($data)
    {
        return LeagueGroupTeam::insert($data);
    }

    public function deleteExistingTeams($data, $key)
    {
        return LeagueGroupTeam::where($key, $data[$key])->delete();
    }

    public function tournamentTeamListQuery($id, $data)
    {
        $lastId = isset($data['last_id']) ? $data['last_id'] : '';
        $status = isset($data['status']) ? $data['status'] : '';
        $str = isset($data['str']) ? $data['str'] : '';

        $tTeam = Team::whereIn('id', function ($query) use ($id) {
            $query->select('team_id')->from('tournament_teams')->where('tournament_id', $id);
        })->with('players', 'tournament')
            ->orderBy('id', 'desc')->limit(10);
        if ($lastId) {
            $tTeam->where('id', '<', $lastId);
        }
        if ($status) {
            $tTeam->whereHas('tournament', function ($q2) use ($status) {
                $q2->where('status', $status);
            });
        } else {
            $tTeam->whereHas('tournament', function ($q2) {
                $q2->where('status', 'ACCEPTED');
            });
        }
        if ($str) {
            $tTeam->where('team_name', 'like', '%' . $str . '%');
        }
        $tournamentTeam = $tTeam->get();
        return $tournamentTeam;
    }

    public function getGlobalTeamListQuery($id, $data)
    {
        $str = isset($data['str']) ? $data['str'] : '';
        $lastId = isset($data['last_id']) ? $data['last_id'] : '';

        $tTeam = Team::whereNotIn('id', function ($query) use ($id) {
            $query->select('team_id')->from('tournament_teams')->where('tournament_id', $id);
        })->with('players')
            ->orderBy('id', 'desc')->limit(10);
        if ($lastId) {
            $tTeam->where('id', '<', $lastId);
        }
        if ($str) {
            $tTeam->where('team_name', 'like', '%' . $str . '%');
        }
        $tournamentTeam = $tTeam->get();
        return $tournamentTeam;
    }

    public function tournamentAvailableTeamListQuery($id, $data)
    {
        $str = isset($data['str']) ? "%{$data['str']}%" : null;

        return Team::with('players')
            ->whereIn('id', function ($q) use ($id) {
                $q
                    ->select('team_id')
                    ->from('tournament_teams')
                    ->where('tournament_id', $id);
            })
            ->whereNotIn('id', function ($q) use ($id) {
                $q
                    ->select('team_id')
                    ->from('league_group_teams')
                    ->where('tournament_id', $id);
            })
            ->when($str, function ($q) use ($str) {
                $q->where('team_name', 'like', $str);
            })
            ->orderBy('id', 'desc')
            ->get();
    }

    public function acceptTeamRequest($id, $obj)
    {
        return TournamentTeam::where('id', $id)->update($obj);
    }

    public function cancelTeamRequest($id, $uid)
    {
        return TournamentTeam::where('id', $id)->where('tournament_owner_id', $uid)->delete();
    }

    public function deleteTournamentTeams($data)
    {
        return TournamentTeam::where('team_id', $data['team_id'])->where('tournament_id', $data['tournament_id'])->delete();
    }

    public function sendRequestToTournamentQuery($obj)
    {
        return TournamentTeam::create($obj);
    }

    public function getTeamStatus($data)
    {
        return TournamentTeam::where('team_id', $data['team_id'])->where('tournament_id', $data['tournament_id'])->first();
    }

    public function checkSingleTeamStatus($id)
    {
        return TournamentTeam::where('id', $id)->first();
    }

    public function createFixtures($matches, $type = 'multiple')
    {
        $defaultSettings = [
            "disable_wagon_wheel_for_dot_ball" => 0,
            "disable_wagon_wheel_for_others" => 0
        ];

        if ($type == 'single') {
            $matches['settings'] = $defaultSettings;
            return Fixture::create($matches);
        }

        $matchesArr = [];

        foreach ($matches as $match) {
            $matchesArr[] = Fixture::create(array_merge($match, ['settings' => $defaultSettings]));
        }

        return $matchesArr;
    }

    public function clearAllFixtureByTournamentId($tournament_id)
    {
        return Fixture::where('tournament_id', $tournament_id)->delete();
    }

    public function getLastMatchNo($tournament_id)
    {
        return Fixture::where('tournament_id', $tournament_id)->orderBy('id', 'desc')->first();
    }
}
