<?php

namespace App\Http\Controllers\Tournament;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;
use Carbon\Carbon;


class TournamentService
{
    private $tournamentQuery;

    public function __construct(TournamentQuery $tournamentQuery)
    {
        $this->tournamentQuery = $tournamentQuery;
    }


    //Tournamenst-start
    public function getTournamentById($tournamentId)
    {
        $tournament = $this->tournamentQuery->getTournamentByIdQuery($tournamentId);
        $tournament->start_date = $tournament->start_date ? date('d M, Y', strtotime($tournament->start_date)) : '';
        $tournament->end_date = $tournament->end_date ? date('d M, Y', strtotime($tournament->end_date)) : '';
        return $tournament;

    }

    public function getTournaments($data)
    {
        $data['user_id'] = Auth::id();
        return $this->tournamentQuery->getTournamentsQuery($data);
    }

    public function getAllTournaments($data)
    {

        if (Auth::guard('sanctum')->check()) {
            $data['uid'] = Auth::guard('sanctum')->user()->id;
        }
        $tournament = $this->tournamentQuery->getAllTournamentsQuery($data)->toArray();

        $formatedTournaments = [];

        if (is_array($tournament)) {
            foreach ($tournament as $key => $value) {
                $value['start_date'] = date('j M Y', strtotime($value['start_date']));
                $value['end_date'] = date('j M Y', strtotime($value['end_date']));
                array_push($formatedTournaments, $value);
            }
        }

        return $formatedTournaments;

    }

    public function getAllTournamentsV2($data){
        $limit = 20;

        $tournaments = $this->tournamentQuery->getAllTournamentsV2Query('ONGOING', $limit);
        $limit -= $tournaments->count();

        if($limit){
            $upcomingTournaments = $this->tournamentQuery->getAllTournamentsV2Query('UPCOMING', $limit);
            $tournaments = $tournaments->merge($upcomingTournaments);
            $limit -= $upcomingTournaments->count();
        }

        if($limit){
            $recentTournaments = $this->tournamentQuery->getAllTournamentsV2Query('RECENT', $limit);
            $tournaments = $tournaments->merge($recentTournaments);
        }

        foreach ($tournaments as $tournament) {
            // $tournament['start_date'] = date('j M Y', strtotime($tournament['start_date']));
            // $tournament['end_date'] = date('j M Y', strtotime($tournament['end_date']));

            $tournament['status'] = 'UPCOMING';
            if($tournament->is_start and !$tournament->is_finished){
                $tournament['status'] = 'ONGOING';
            } else if($tournament->is_start and $tournament->is_finished){
                $tournament['status'] = 'RECENT';
            }

            unset($tournament['is_start']);
            unset($tournament['is_finished']);
        }

        return $tournaments;

    }


    public function createTournaments($data)
    {

        $data['organizer_id'] = Auth::id();

        $data['wagon_settings'] = json_encode([
            "disable_wagon_wheel_for_dot_ball" => 0,
            "disable_wagon_wheel_for_others" => 0
        ]);

        if (isset($data['tournament_banner']) && $data['tournament_banner']) {
            $data['tournament_banner'] = $this->uploadImage('tournament_banner', $data['tournament_banner']);
        }

        if (isset($data['tournament_logo']) && $data['tournament_logo']) {
            $data['tournament_logo'] = $this->uploadImage('tournament_logo', $data['tournament_logo']);
        }

        return $this->tournamentQuery->createTournamentsQuery($data);
    }


    public function updateTournaments($data)
    {

        $tId = $data['id'];
        unset($data['id']);
        $check = $this->tournamentQuery->getSingleTournament($tId);

        $baseURL = env('APP_URL');

        if (isset($data['tournament_banner']) && $data['tournament_banner']) {

            $bannerPath = str_replace($baseURL, '', $check->tournament_banner);
            $isDefaultBannerPath = str_contains($bannerPath, 'default_team_banner.webp');
            if ($bannerPath and !$isDefaultBannerPath) {
                unlink(public_path($bannerPath));
            }
            $data['tournament_banner'] = $this->uploadImage('tournament_banner', $data['tournament_banner']);
        }

        if (isset($data['tournament_logo']) && $data['tournament_logo']) {
            $logoPath = str_replace($baseURL, '', $check->tournament_logo);
            $isDefaultLogoPath = str_contains($logoPath, 'default_team_logo.webp');
            if ($logoPath and !$isDefaultLogoPath) {
                unlink(public_path($logoPath));
            }
            $data['tournament_logo'] = $this->uploadImage('tournament_logo', $data['tournament_logo']);
        }
        return $this->tournamentQuery->updateTournamentsQuery($tId, $data);
    }

    public function uploadImage($imgName, $imgFile): string
    {
        $baseURL = env('APP_URL');
        $imgName = $imgName . '_' . hexdec(uniqid()) . '.webp';
        Image::make($imgFile)->save('uploads/' . $imgName);
        return $baseURL . 'uploads/' . $imgName;
    }

    public function deleteTournament($data)
    {
        $tId = $data['tournament_id'];
        return $this->tournamentQuery->deleteTournamentQuery($tId, Auth::id());
    }

    public function tournamentStats($data)
    {
        return $this->tournamentQuery->tournamentStats($data);
    }

    //Tournaments-end

    //Tournaments-settings
    public function tournamentSettings($data)
    {
        $id = $data['tournament_id'];
        $type = $data['tournament_type'];

        if (isset($type) && $type == "SUPER LEAGUE") {
            $round_one = [
                "round_type" => $data['tournament_type'],
                "face_off" => $data['first_round_face_off'],
                "type" => "LEAGUE",
            ];

            $round_two = [
                "round_type" => $data['second_round'],
                "face_off" => $data['second_round_face_off'] == "KNOCK OUT" ? "KNOCK OUT" : "LEAGUE",
                "type" => "LEAGUE",
            ];

            if (isset($data['third_round']) && $data['third_round'] != "") {
                $round_three = [
                    "round_type" => $data['third_round'],
                    "face_off" => 1,
                    "type" => "KNOCK OUT",
                ];
            }

            $matchSetting = array($round_one, $round_two, $round_three);

            $obj = [
                "group_settings" => json_encode($matchSetting),
                "tournament_type" => $data['tournament_type'],
            ];
        }

        if (isset($type) && $type == "IPL SYSTEM") {
            $round_one = [
                "round_type" => $data['tournament_type'],
                "face_off" => $data['face_off'],
                "type" => "LEAGUE",
            ];

            $matchSetting = array($round_one);

            $obj = [
                "group_settings" => json_encode($matchSetting),
                "tournament_type" => $data['tournament_type'],
            ];
        }

        if (isset($type) && $type == "LEAGUE MATCHES") {
            $round_one = [
                "round_type" => $data['tournament_type'],
                "face_off" => 1,
                "type" => "LEAGUE",
            ];

            $matchSetting = array($round_one);

            $obj = [
                "group_settings" => json_encode($matchSetting),
                "tournament_type" => $data['tournament_type'],
                "total_groups" => $data['total_groups'],
                "group_winners" => $data['group_winners'],
                "third_position" => $data['third_position'],
            ];
        }

        return $this->tournamentQuery->tournamentSettingsQuery($id, $obj);
    }
    //Tournaments-settings

    //Ground-start

    public function addGroundInTournament($data)
    {
        return $this->tournamentQuery->addGroundInTournamentQuery($data);
    }

    public function tournamentGroundLists($data)
    {
        return $this->tournamentQuery->tournamentGroundListsQuery($data);
    }


    //Ground-end

    //Tournament-fixture

    public function tournamentFixture($id, $data)
    {
        return $this->tournamentQuery->tournamentFixtureQuery($id, $data);
    }

    //Tournament-fixture


    //Tournament-team-start

    public function tournamentTeamList($id, $data)
    {
        $teamList = $this->tournamentQuery->tournamentTeamListQuery($id, $data);
        return $teamList;
        foreach ($teamList as $key => $value) {
            $value['captain'] = $value->team && $value->team->captain ? $value->team->captain->first_name . ' ' . $value->team->captain->last_name : '';
            unset($value->team->captain);
        }
        return $teamList;
    }

    public function getGlobalTeamList($id, $data)
    {
        // $teamList = $this->tournamentQuery->getGlobalTeamListQuery($id, $data);
        // return $teamList;
        // foreach($teamList as $key => $value){
        //     $value['captain'] =$value->team && $value->team->captain ?  $value->team->captain->first_name .' '.$value->team->captain->last_name:'';
        //     unset($value->team->captain);
        // }
        // return $teamList;
    }

    public function tournamentPointsTable($data)
    {
        $league_group_id = isset($data['league_group_id']) ? $data['league_group_id'] : 0;
        $last_id = isset($data['last_id']) ? $data['last_id'] : 0;
        $limit = isset($data['limit']) ? $data['limit'] : 0;
        $team_limit = isset($data['team_limit']) ? $data['team_limit'] : 0;

        $id = $data['tournament_id'];
        $check = $this->tournamentQuery->getSingleTournament($id);
        if($check && $check->tournament_type == "KNOCK OUT"){
            return [];
        }
        $tournament = $this->tournamentQuery->tournamentPointsTableQuery($id, $league_group_id, $last_id, $limit);

        foreach ($tournament as $t) {

            $arr_teams = array();

            foreach ($t->group_teams as $gt) {
                if ($gt->home_team_total_overs && $gt->away_team_total_overs && $gt->home_team_total_runs && $gt->away_team_total_runs) {
                    $total_overs_faced = $gt->home_team_total_overs + floor($gt->home_team_total_balls / 6) . '.' . ($gt->home_team_total_balls % 6);
                    $total_overs_bowled = $gt->away_team_total_overs + floor($gt->away_team_total_balls / 6) . '.' . ($gt->away_team_total_balls % 6);
                    $NrrFormate = number_format((float)($gt->home_team_total_runs / $total_overs_faced) - ($gt->away_team_total_runs / $total_overs_bowled), 3, '.', '');
                    if ($NrrFormate > 0) {
                        $NRR = '+' . $NrrFormate;
                    } else {
                        $NRR = $NrrFormate;
                    }
                } else {
                    $NRR = 0;
                }

                $obj = [
                    'id' => $gt->id,
                    'team_id' => $gt->team_id,
                    'team_name' => $gt->teams->team_name,
                    'team_short_name' => $gt->teams->team_short_name,
                    'total_matches' => (int)$gt->total_matches,
                    'total_won' => (int)$gt->total_won,
                    'total_loss' => (int)$gt->total_loss,
                    'total_tied' => (int)$gt->total_tied,
                    'total_points' => (int)$gt->total_points,
                    'NR' => (int)$gt->NR,
                    'NRR' => $NRR,
                ];

                array_push($arr_teams, $obj);
            }
            unset($t->group_teams);
            unset($t->teams);


            usort($arr_teams, function($a, $b) {
                if($a['total_points'] ===  $b['total_points'] ){
                    return $a['NRR'] > $b['NRR'] ? -1 : 1;
                } else {
                    return $a['total_points'] > $b['total_points'] ? -1 : 1;
                }
            });


            $t->teams_results = $arr_teams;

            if($team_limit){
                $t->teams_results =array_slice($arr_teams, 0, $team_limit);
            }

        }


        return $tournament;

    }

    public function tournamentDetails($id)
    {
        $tour = $this->tournamentQuery->tournamentDetailsQuery($id);
        $tour->organizer_name = $tour && $tour->organizer ? $tour->organizer->first_name . ' ' . $tour->organizer->last_name : '';
        $tour->start_date = $tour && $tour->start_date ? date('d/m/Y', strtotime($tour->start_date)) : null;
        $tour->end_date = $tour && $tour->end_date ? date('d/m/Y', strtotime($tour->end_date)) : null;
        $tour->wagon_settings = json_decode($tour->wagon_settings, true);
        unset($tour->organizer);
        return $tour;
    }

    public function singletournamentDetails($data)
    {
        $tour = $this->tournamentQuery->singletournamentDetails($data['tournament_id']);
        $tour->tournament_category = ucfirst(strtolower($tour->tournament_category));
        $tour->ball_type = ucfirst(strtolower($tour->ball_type));
        return $tour;
    }

    public function tournamentScore($id)
    {
        $score = $this->tournamentQuery->tournamentScoreQuery($id);
        return $score;
    }

    //Tournament-team-end


    //Round-start

    public function addRound($data)
    {
        return $this->tournamentQuery->addRoundQuery($data, Auth::id());
    }

    //Round-end


    //Team-start

    public function addTeam($data)
    {
        if ($banner = $data['banner']) {
            $bannerName = 'banner-' . hexdec(uniqid()) . '.webp';
            Image::make($banner)->save('uploads/' . $bannerName);
            $data['banner'] = env('APP_URL') . '/uploads/' . $bannerName;
        }
        if ($logo = $data['logo']) {
            $logoName = 'logo-' . hexdec(uniqid()) . '.webp';
            Image::make($logo)->save('uploads/' . $logoName);
            $data['logo'] = env('APP_URL') . '/uploads/' . $logoName;
        }

        $data['user_id'] = Auth::id();
        return $this->tournamentQuery->addTeamQuery($data);
    }


    public function editTeam($data)
    {
        $id = $data['team_id'];
        unset($data['team_id']);
        return $this->tournamentQuery->editTeamQuery($id, Auth::id(), $data);
    }

    public function deleteTeam($data)
    {
        $id = $data['team_id'];
        return $this->tournamentQuery->deleteTeamQuery($id, Auth::id());
    }




    //Team-end


    //tournament-start
    public function addTournament($data)
    {
        // return $this->tournamentQuery->addTournamentQuery($data);
    }

    public function removeTournament($data)
    {
        $tId = $data['team_id'];
        // return $this->tournamentQuery->removeTournamentQuery($tId);
    }
    //tournament-end

    //Group-start

    // public function addGroup($data){
    //     return $this->tournamentQuery->addGroupQuery($data);
    // }
    // public function editGroup($data){
    //     $gId = $data['group_id'];
    //     unset($data['group_id']);
    //     return $this->tournamentQuery->editGroupQuery($gId, $data);
    // }

    // public function removeGroup($data){
    //     $gId = $data['group_id'];
    //     return $this->tournamentQuery->removeGroupQuery($gId);
    // }

    //Group-end


    //Group Team -start

    // public function addTeamsInGroup($data){
    //     return $this->tournamentQuery->addTeamsInGroupQuery($data);
    // }

    // public function editTeamsInGroup($data){
    //     $gId = $data['gteam_id'];
    //     unset($data['gteam_id']);
    //     return $this->tournamentQuery->editTeamsInGroupQuery($gId, $data);
    // }

    // public function removeTeamsInGroup($data){
    //     $gId = $data['gteam_id'];
    //     return $this->tournamentQuery->removeTeamsInGroupQuery($gId);
    // }

    //Group Team -end

    public function drawTournamentGroupStage($data)
    {


        // $gId = $data['gteam_id'];
        $tournament_id = $data['tournament_id'];
        $tournament_info = $this->tournamentQuery->getSingleTournament($tournament_id);
        // $tournament_info['group_settings'] = json_decode($tournament_info['group_settings'], true);
        $all_groups = $this->tournamentQuery->getLeagueGroupsByTournamentId($tournament_id);
        foreach ($all_groups as $g) {
            $this->makeGroupDraw($g, $tournament_info);
        }

        return $this->makeKnockoutDraw($tournament_info);

    }


    // Draw Helper

    public function makeGroupDraw($group, $tournament_info)
    {

        $league_group_id = $group['id'];
        $tournament_id = $tournament_info['id'];
        $teams = $this->tournamentQuery->getTeamsIdByLeagueGroups($league_group_id);
        $teams = $teams->shuffle();
        $teams = $teams->toArray();

        // Log::channel('slack')->info('league_group_id', ['data' => $league_group_id]);
        // Log::channel('slack')->info('tournament_id', ['data' => $tournament_id]);

        $faceOff = 1;
        // $faceOff = $tournament_info['group_settings'][0]['face_off'];
        if (count($teams) % 2 != 0) {
            array_push($teams, 0);

        }
        $leng = sizeof($teams);
        $lastHalf = $leng - 1;
        $totalRoundPerFace = $leng - 1;
        $fixtures = [];
        $matches = [];
        for ($f = 0; $f < $faceOff; $f++) {
            for ($round = 0; $round < $totalRoundPerFace; $round++) {
                // array_push($fixtures,$fix);
                $r = ($round + ($totalRoundPerFace * $f));
                $fixtures[$r] = [];
                $ob = [
                    'round' => $r + 1,
                    'matches' => []
                ];
                $fixtures[$r] = $ob;

                for ($i = 0; $i < $leng / 2; $i++) {


                    $fix = '';

                    $homeTeam = $teams[$i];
                    $awayTeam = $teams[$lastHalf - $i];

                    if ($f % 2 == 1) {
                        $homeTeam = $teams[$lastHalf - $i];
                        $awayTeam = $teams[$i];
                    }
                    $fix = "$homeTeam VS $awayTeam";
                    if ($homeTeam == 0 || $awayTeam == 0) {
                        continue;
                    }

                    $matchesOb = [
                        'tournament_id' => $tournament_id,
                        'league_group_id' => $league_group_id,
                        'group_round' => $r + 1,
                        // 'round_type'=>'GROUP',
                        'fixture_type' => 'GROUP',
                        'match_type' => $tournament_info['match_type'],
                        'home_team_id' => $homeTeam,
                        'away_team_id' => $awayTeam,
                        // 'created_at'=>Carbon::now(),
                        // 'updated_at'=>Carbon::now(),
                    ];

                    array_push($fixtures[$r]['matches'], $fix);
                    array_push($matches, $matchesOb);

                }
                // return $teams;
                array_splice($teams, 1, 0, $teams[$leng - 1]);
                /*now pop up the last element*/
                array_pop($teams);


            }
            // return $fixtures;
        }
        // $teams= shuffle($teams);
        $fixtures_length = sizeof($matches);


        return $this->tournamentQuery->createFixtures($matches, 'multiple');
        // return $this->tournamentQuery->xyz();

        return $matches;

    }

    public function makeKnockoutDraw($tournament_info)
    {
        // if ($tournament_info['group_settings'][0]['round_type'] == 'IPL') return $this->makeIPLKnockoutDraw($tournament_info);
        //  return $this->makeIPLKnockoutDraw($tournament_info);
        $totalKnockoutTeams = $tournament_info['total_groups'] * $tournament_info['group_winners'];
        $tournament_id = $tournament_info['id'];
        $totalGroup = $tournament_info['total_groups'];
        // $KTeams=[];
        // for($i=1;$i<=$tournament_info['total_groups'];$i++){
        //     for($j=1;$j<=$tournament_info['group_winners'];$j++){

        //     }
        // }
        $KTeams = [];
        // generate Team
        for ($i = 1; $i <= $totalKnockoutTeams; $i++) {
            $s = '';
            if ($tournament_info['total_groups'] <= $totalKnockoutTeams) {
                if ($totalGroup > 0) {
                    $s = "G-$i";
                    $totalGroup--;
                } else {
                    $s = "R-$i";
                }
            } else {
                $s = "R-$i";
            }
            array_push($KTeams, $s);

        }

        $length = sizeof($KTeams);
        $f = false;
        $jj = -1;
        while ($length != 1) {
            $round = $length;
            $h = $length / 2;
            $t = [];
            if ($f == false) {

                for ($i = 0; $i < $h; $i += 1) {
                    $s = "";
                    $j = ($length - $i) - 1;
                    $s = $i . "-" . $j;
                    $data = [
                        'tournament_id' => $tournament_id,
                        'knockout_round' => $round,
                        // 'round_type'=>'KNOCKOUT',
                        'fixture_type' => 'KNOCKOUT',
                        'match_type' => $tournament_info['match_type'],
                        'temp_team_one' => $KTeams[$i],
                        'temp_team_two' => $KTeams[$j],

                    ];
                    $this->tournamentQuery->createFixtures($data, 'single');
                }
            } else {
                for ($i = 0; $i < $length; $i += 2) {


                    $s = "";
                    $j = $i + 1;
                    $s = $i . "-" . $j;
                    $data = [
                        'tournament_id' => $tournament_id,
                        'knockout_round' => $round,
                        'fixture_type' => 'KNOCKOUT',
                        'match_type' => $tournament_info['match_type'],
                        'temp_team_one' => $KTeams[$i]['id'],
                        'temp_team_two' => $KTeams[$j]['id'],

                    ];
                    $this->tournamentQuery->createFixtures($data, 'single');


                }
            }

            $kk = $this->tournamentQuery->getKnockoutMatchesByRound($tournament_id, $round);
            $kk = $kk->toArray();
            $KTeams = $kk;
            $f = true;
            $length = $length / 2;
        }
        $knockoutFixtureData = $this->tournamentQuery->getKnockoutMatches($tournament_id);
        // $knockoutFixtureData->groupBy('round');
        return $knockoutFixtureData;
        // return Knockoutfixture::where('tournament_id',$tournament_id)->where('fixtureType','BracketA')->get();

    }

    // public function makeIPLKnockoutDraw($tournament_info,$round,$KTeams)
    // {
    //     $tournament_id = $tournament_info['id'];
    //     $data = [
    //         'tournament_id' => $tournament_id,
    //         'knockout_round' => $round,
    //         'fixture_type' => 'KNOCKOUT',
    //         'match_type' => $tournament_info['match_type'],
    //         'temp_team_one' => $KTeams[$i]['id'],
    //         'temp_team_two' => $KTeams[$j]['id'],

    //     ];
    //     $this->tournamentQuery->createFixtures($data, 'single');
    //     $data = [
    //         'tournament_id' => $tournament_id,
    //         'knockout_round' => $round,
    //         'fixture_type' => 'KNOCKOUT',
    //         'match_type' => $tournament_info['match_type'],
    //         'temp_team_one' => $KTeams[$i]['id'],
    //         'temp_team_two' => $KTeams[$j]['id'],

    //     ];
    //     $this->tournamentQuery->createFixtures($data, 'single');

    // }

    //    ongoing, upcoming and recent tournaments list start
    public function getTournamentsList($type, $isPrivate = 0)
    {
        $userType = null;
        if ($isPrivate and auth('sanctum')->check()) {
            $userType = auth('sanctum')->user()->registration_type;
        }
        $tournaments = $this->tournamentQuery->getTournamentsListQuery($type, $userType);
        foreach ($tournaments as $tournament) {
            $tournament->start_date = date('d M Y', strtotime($tournament->start_date));
            $tournament->end_date = date('d M Y', strtotime($tournament->end_date));
        }

        return $tournaments;
    }

}
