<?php

namespace App\Http\Controllers\TournamentSchedule;

use App\Http\Controllers\Notification\NotificationService;
use App\Http\Controllers\Team\TeamQuery;
use App\Http\Controllers\Universal\UniversalService;
use App\Http\Controllers\Tournament\TournamentQuery;
use Illuminate\Support\Facades\Auth;
use Log;

class TournamentScheduleService
{
    private $tournamentScheduleQuery;
    private $universalService;
    private $tournamentQuery;
    private $teamQuery;
    private $notificationService;

    public function __construct(TournamentScheduleQuery $tournamentScheduleQuery, UniversalService $universalService, TournamentQuery $tournamentQuery, TeamQuery $teamQuery, NotificationService $notificationService)
    {
        $this->tournamentScheduleQuery = $tournamentScheduleQuery;
        $this->universalService = $universalService;
        $this->tournamentQuery = $tournamentQuery;
        $this->teamQuery = $teamQuery;
        $this->notificationService = $notificationService;
    }

    public function getRounds($tournament_id)
    {
        return $tournament_info = $this->tournamentScheduleQuery->getRoundsQuery($tournament_id);
        // return $tournament_info['group_settings'];
    }


    public function storeRounds($data)
    {
        // $data['user_id'] = Auth::id();
        // return $data;
        // return $this->tournamentScheduleQuery->getRoundstQuery($data['id']);

        $tournament = $this->tournamentQuery->getTournamentByIdQuery($data['id']);
        if ($data['league_format'] == 'IPL') {
            $settings = [
                [
                    "tournament_id" => $data['id'],
                    "round_type" => "IPL",
                    "face_off" => $data['group_settings']['mainLeague']['face_off'],
                    "type" => "LEAGUE"
                ],
                [
                    "tournament_id" => $data['id'],
                    "round_type" => "PLAY OFF",
                    "face_off" => 1,
                    "type" => "KNOCK OUT"
                ],
                [
                    "tournament_id" => $data['id'],
                    "round_type" => "FINAL",
                    "face_off" => 1,
                    "type" => "KNOCK OUT"
                ]
            ];
            $data['group_settings'] = $settings;
            $data['tournament_type'] = 'IPL SYSTEM';
            $data['group_winners'] = 4;
        } else if ($data['league_format'] == "KNOCK OUT") {
            $settings = [];
            $totalKnockoutTeams = $data['total_groups'] * $data['group_winners'];
            $length = $totalKnockoutTeams;
            // return $totalKnockoutTeams;
            while ($length > 1) {
                $round = $length;
                $roundName = $this->universalService->getRoundName($length);
                $ob = [
                    "tournament_id" => $data['id'],
                    "round_type" => $roundName,
                    "face_off" => 1,
                    "type" => "KNOCK OUT"
                ];

                array_push($settings, $ob);

                if ($roundName == 'SEMI-FINAL' and $data['third_position'] == 'YES') {
                    $ob = [
                        "tournament_id" => $data['id'],
                        "round_type" => 'THIRD PLACE',
                        "face_off" => 1,
                        "type" => "KNOCK OUT"
                    ];
                    array_push($settings, $ob);
                }

                $length = $length / 2;
            }

            $data['group_settings'] = $settings;
            $data['tournament_type'] = 'KNOCK OUT';
        } else if ($data['league_format'] == 'GROUP LEAGUE') {

            $settings = [
                [
                    "tournament_id" => $data['id'],
                    "round_type" => "GROUP LEAGUE",
                    "face_off" => $data['group_settings']['mainLeague']['face_off'],
                    "type" => "LEAGUE"
                ]
            ];
            $totalKnockoutTeams = $data['total_groups'] * $data['group_winners'];
            $length = $totalKnockoutTeams;
            while ($length != 1) {
                $round = $length;
                $roundName = $this->universalService->getRoundName($length);
                $ob = [
                    "tournament_id" => $data['id'],
                    "round_type" => $roundName,
                    "face_off" => 1,
                    "type" => "KNOCK OUT"
                ];
                array_push($settings, $ob);

                if ($roundName == 'SEMI-FINAL' and $data['third_position'] == 'YES') {
                    $ob = [
                        "tournament_id" => $data['id'],
                        "round_type" => 'THIRD PLACE',
                        "face_off" => 1,
                        "type" => "KNOCK OUT"
                    ];
                    array_push($settings, $ob);
                }

                $length = $length / 2;
            }

            $data['group_settings'] = $settings;
            $data['tournament_type'] = 'LEAGUE MATCHES';
        } else if ($data['league_format'] == 'SUPER LEAGUE') {
            $flag = true;
            $settings = [
                [
                    "tournament_id" => $data['id'],
                    "round_type" => 'SUPER LEAGUE',
                    "face_off" => $data['group_settings']['mainLeague']['face_off'],
                    "type" => "LEAGUE"
                ]
            ];

            if (isset($data['group_settings']['round10'])) {
                $ob = [
                    "tournament_id" => $data['id'],
                    "round_type" => 'ROUND OF 10',
                    "face_off" => $data['group_settings']['round10']['face_off'],
                    "type" => "LEAGUE"
                ];

                array_push($settings, $ob);
                $data['group_winners'] = 10;
                $flag = false;
            }
            if (isset($data['group_settings']['round6'])) {
                $ob = [
                    "tournament_id" => $data['id'],
                    "round_type" => 'ROUND OF 6',
                    "face_off" => $data['group_settings']['round6']['face_off'],
                    "type" => "LEAGUE"
                ];

                array_push($settings, $ob);
                if ($flag == true) {
                    $data['group_winners'] = 6;
                    $flag = false;
                }
            }
            if (isset($data['group_settings']['quarter'])) {
                $ob = [
                    "tournament_id" => $data['id'],
                    "round_type" => 'QUARTER-FINAL',
                    "face_off" => 1,
                    "type" => "KNOCK OUT"
                ];

                if ($flag == true) {
                    $data['group_winners'] = 8;
                    $flag = false;
                }

                array_push($settings, $ob);
                $ob = [
                    "tournament_id" => $data['id'],
                    "round_type" => 'SEMI-FINAL',
                    "face_off" => 1,
                    "type" => "KNOCK OUT"
                ];

                array_push($settings, $ob);

                if ($data['third_position'] == 'YES') {
                    $ob = [
                        "tournament_id" => $data['id'],
                        "round_type" => 'THIRD PLACE',
                        "face_off" => 1,
                        "type" => "KNOCK OUT"
                    ];
                    array_push($settings, $ob);
                }

                $ob = [
                    "tournament_id" => $data['id'],
                    "round_type" => 'FINAL',
                    "face_off" => 1,
                    "type" => "KNOCK OUT"
                ];

                array_push($settings, $ob);
            } else if (isset($data['group_settings']['semi'])) {

                $ob = [
                    "tournament_id" => $data['id'],
                    "round_type" => 'SEMI-FINAL',
                    "face_off" => 1,
                    "type" => "KNOCK OUT"
                ];

                if ($flag == true) {
                    $data['group_winners'] = 4;
                    $flag = false;
                }

                array_push($settings, $ob);

                if ($data['third_position'] == 'YES') {
                    $ob = [
                        "tournament_id" => $data['id'],
                        "round_type" => 'THIRD PLACE',
                        "face_off" => 1,
                        "type" => "KNOCK OUT"
                    ];
                    array_push($settings, $ob);
                }

                $ob = [
                    "tournament_id" => $data['id'],
                    "round_type" => 'FINAL',
                    "face_off" => 1,
                    "type" => "KNOCK OUT"
                ];

                array_push($settings, $ob);

            } else if (isset($data['group_settings']['final'])) {
                $ob = [
                    "tournament_id" => $data['id'],
                    "round_type" => 'FINAL',
                    "face_off" => 1,
                    "type" => "KNOCK OUT"
                ];

                if ($flag == true) {
                    $data['group_winners'] = 2;
                    $flag = false;
                }

                array_push($settings, $ob);
            }

            $data['group_settings'] = $settings;
            $data['tournament_type'] = 'SUPER LEAGUE';
        }

        $data['group_settings'] = json_encode($data['group_settings']);
        // if(){
        //     return response()->json([
        //         'message' => collect($validator->errors()->all())
        //     ], 422);
        // }

        $this->tournamentScheduleQuery->updateTournamentQuery($data);
        return $tournament_info = $this->tournamentScheduleQuery->getRoundsQuery($data['id']);
    }

    public function resetRounds($data)
    {
        $data['league_format'] = null;
        $data['group_settings'] = null;
        $data['group_winners'] = 0;
        $data['total_groups'] = 0;
        $data['third_position'] = 'NO';
        $data['is_start'] = 0;
        $data['is_finished'] = 0;
        $this->tournamentScheduleQuery->updateTournamentQuery($data);
        $this->tournamentScheduleQuery->clearGroupsQuery($data['id']);
        $this->tournamentScheduleQuery->clearAllFixtureByTournamentId($data['id']);
    }

    public function getGroups($tournament_id)
    {
        $groups = $this->tournamentScheduleQuery->getGroupsQuery($tournament_id);
        if (sizeof($groups) == 0) {
            return [
                'groups' => [],
                'isAllGroupComplete' => 0,
                'isAllGroupDrawComplete' => 0,
            ];
        }
        $isAllGroupComplete = 1;
        foreach ($groups as $value) {
            if ($value['is_complete'] == 0) {
                $isAllGroupComplete = 0;
                break;
            }
        }
        $isAllGroupDrawComplete = 0;
        if ($isAllGroupComplete == 1) {
            $isAllGroupDrawComplete = $this->tournamentScheduleQuery->getTournamentFixtureCountQuery($tournament_id) == 0 ? 0 : 1;
        }
        return [
            'groups' => $groups,
            'isAllGroupComplete' => $isAllGroupComplete,
            'isAllGroupDrawComplete' => $isAllGroupDrawComplete,
        ];
    }

    public function getGroupListWithTeam($tournament_id)
    {
        $groups = $this->tournamentScheduleQuery->getGroupsQuery($tournament_id);
        $allGroupsWithTeams = [];
        foreach ($groups as $value) {
            $data = $this->getGroupDetails($value['id']);
            array_push($allGroupsWithTeams, $data);
        }
        return $allGroupsWithTeams;
    }

    public function getGroupDetails($group_id)
    {
        $groups = $this->tournamentScheduleQuery->getGroupDetailsQuery($group_id);
        $groupsTeams = $this->tournamentScheduleQuery->getGroupsTeamsQuery($group_id);

        return [
            'groups' => $groups,
            'groupsTeams' => $groupsTeams,
        ];
    }

    public function addGroupsTeamsAndInfo($data)
    {
        $tournament_info = $this->tournamentScheduleQuery->getRoundsQuery($data['tournament_id']);

        $tournament_id = $data['tournament_id'];

        $ob = [
            'id' => $data['id'],
            'league_group_name' => $data['league_group_name']
        ];
        if (isset($data['teams'])) {
            $teams = $data['teams'];
            if ($tournament_info['group_winners'] <= sizeof($teams)) {
                $ob['is_complete'] = 1;
            } else {
                $ob['is_complete'] = 0;
            }
            $this->tournamentScheduleQuery->updateLeagueGroup($ob, 'id');
            $groupTeams = [];
            foreach ($teams as $value) {
                $ob = [
                    'tournament_id' => $tournament_id,
                    'league_group_id' => $data['id'],
                    'team_id' => $value['id'],
                ];

                array_push($groupTeams, $ob);
            }
            $this->tournamentScheduleQuery->updateLeagueGroupTeams($data['id'], $groupTeams);
        }
        return $this->getGroupDetails($data['id']);
    }

    public function storeGroups($data)
    {
        $tournament_info = $this->tournamentScheduleQuery->getRoundsQuery($data['tournament_id']);
        $this->tournamentScheduleQuery->clearGroupsQuery($data['tournament_id']);
        if ($tournament_info['league_format'] == 'GROUP LEAGUE') {
            $allGroups = [];

            for ($i = 1; $i <= $tournament_info['total_groups']; $i++) {

                $ob = [
                    'tournament_id' => $data['tournament_id'],
                    'league_group_name' => "Group $i",
                    'round_type' => "LEAGUE"
                ];

                array_push($allGroups, $ob);
            }

            $this->tournamentScheduleQuery->storeMultipleGroupsQuery($allGroups);
        } else {
            $ob = [
                'tournament_id' => $data['tournament_id'],
                'league_group_name' => $tournament_info['group_settings'][0]['round_type'],
                'round_type' => $tournament_info['group_settings'][0]['type'],
            ];

            $this->tournamentScheduleQuery->storeGroupsQuery($ob);
        }

        return $this->getGroups($data['tournament_id']);
    }

    public function tournamentTeamList($id, $data)
    {
        $teamList = $this->tournamentScheduleQuery->tournamentTeamListQuery($id, $data);
        return $teamList;
    }

    public function getGlobalTeamList($id, $data)
    {
        $teamList = $this->tournamentScheduleQuery->getGlobalTeamListQuery($id, $data);
        return $teamList;
    }

    public function tournamentAvailableTeamList($id, $data)
    {
        $teamList = $this->tournamentScheduleQuery->tournamentAvailableTeamListQuery($id, $data);
        return $teamList;
    }

    public function sendRequestToTeam($data)
    {
        // Send Notification Invitation to Teams
        $tournament = $this->tournamentQuery->getTournamentByIdQuery($data['tournament_id']);
        $team = $this->teamQuery->getTeamByIdQuery($data['team_id']);
        // Code goes here.........................
        $ob = [
            'from' => $team->organizer_id,
            'to' => $team->owner_id,
            'msg' => $tournament->tournament_name . ' invites ' . $team->team_name . ' to participate to their tournament.',
            'type' => 'tournament_to_club_invitation_request',
            'tournament_id' => $data['tournament_id'],
            'team_id' => $data['team_id'],
        ];

        $this->notificationService->sendNotificationGlobalMethod($ob);

        return response()->json(['message' => 'Request sent successfully!'], 200);
    }

    public function sendRequestToTournament($data)
    {
        $checkStatus = $this->tournamentScheduleQuery->getTeamStatus($data);
        if ($checkStatus) {
            return response()->json(['message' => 'Request already sent for this team!'], 401);
        }

        $tournament = $this->tournamentQuery->getSingleTournament($data['tournament_id']);
        $team = $this->teamQuery->getTeamByIdQuery($data['team_id']);

        $obj = [
            'team_id' => $data['team_id'],
            'tournament_id' => $data['tournament_id'],
            'requested_by' => 'TEAM',
            'status' => 'PENDING',
            'tournament_owner_id' => $tournament->organizer_id,
        ];

        $notifyObj = [
            'from' => $team->owner_id,
            'to' => $tournament->organizer_id,
            'msg' => $team->team_name . ' wants to participate in your ' . $tournament->tournament_name . ' tournament.',
            'type' => 'club_to_tournament_participate_request',
            'tournament_id' => $tournament->id,
            'team_id' => $team->id,
            'club_id' => $team->owner_id,
        ];

        $this->notificationService->sendNotificationGlobalMethod($notifyObj);

        return $this->tournamentScheduleQuery->sendRequestToTournamentQuery($obj);


        // if ($checkStatus->status == "PENDING") {  //cancel-request
        //     return $this->tournamentQuery->deleteTournamentTeams($data);
        // }

    }

    public function acceptOrCancelTeamRequest($data)
    {
        // return $data;
        $status = $data['status'];
        $user_id = Auth::id();
        $checkStatus = $this->tournamentScheduleQuery->checkSingleTeamStatus($data['id']);
        if (!$checkStatus) {
            return response()->json(['message' => 'Request invalid!'], 401);
        }
        if ($checkStatus->status != 'PENDING' and $status != 'REMOVE') {
            return response()->json(['message' => 'Team Already Added!'], 401);
        }

        $tournament = $this->tournamentQuery->getSingleTournament($checkStatus->tournament_id);
        $team = $this->teamQuery->getTeamByIdQuery($checkStatus->team_id);

        // Log::channel('slack')->info($tournament);
        // Log::channel('slack')->info($team);

        if ($data['status'] == 'ACCEPTED') {

            $obj = [
                "status" => 'ACCEPTED',
            ];

            $notifyObj = [
                'from' => $tournament->organizer_id,
                'to' => $team->owner_id,
                'msg' => "{$tournament->tournament_name} tournament organizer accepted your ({$team->team_name})team request.",
                'type' => 'tournament_to_club_accept_request',
                'tournament_id' => $tournament->id,
                'team_id' => $team->id,
                'club_id' => $team->owner_id,
            ];

            $this->notificationService->sendNotificationGlobalMethod($notifyObj);

            return $this->tournamentScheduleQuery->acceptTeamRequest($data['id'], $obj);
        } else if ($data['status'] == "REJECT" or $data['status'] == "REMOVE") {
            $notifyObj = [
                'from' => $tournament->organizer_id,
                'to' => $team->owner_id,
                'msg' => "{$tournament->tournament_name} tournament organizer removed your ({$team->team_name})team from the tournament.",
                'type' => 'tournament_to_club_team_remove',
                'tournament_id' => $tournament->id,
                'team_id' => $team->id,
                'club_id' => $team->owner_id,
            ];

            if ($data['status'] == "REJECT") {
                $notifyObj = [
                    'from' => $tournament->organizer_id,
                    'to' => $team->owner_id,
                    'msg' => "{$tournament->tournament_name} tournament organizer rejected your ({$team->team_name})team request.",
                    'type' => 'tournament_to_club_reject_request',
                    'tournament_id' => $tournament->id,
                    'team_id' => $team->id,
                    'club_id' => $team->owner_id,
                ];
            }


            $this->notificationService->sendNotificationGlobalMethod($notifyObj);
            return $this->tournamentScheduleQuery->deleteTournamentTeams($data);
        }
    }


    public function autoGroupCompleteTournamentDraw($data)
    {
        $tournament_id = $data['tournament_id'];
        $tournament_info = $this->tournamentScheduleQuery->getRoundsQuery($tournament_id);
        // Check is Tournament round is ok
        if (!$tournament_info['group_settings']) {
            return response()->json([
                'message' => 'Rounds not ready!'
            ], 401);
        }
        $this->tournamentScheduleQuery->clearGroupsQuery($tournament_id);
        $this->storeGroups($data);
        $groups = $this->tournamentScheduleQuery->getGroupsQuery($tournament_id);

        $totalKnockoutTeams = $tournament_info['total_groups'] * $tournament_info['group_winners'];
        $getAccpetedtournamentTeams = $this->tournamentScheduleQuery->getAccpetedtournamentTeamsQuery($tournament_id);


        // Check is Tournament teams is ok
        if (sizeof($getAccpetedtournamentTeams) < $totalKnockoutTeams) {
            return response()->json([
                'message' => "$totalKnockoutTeams Teams needed to start the tournament!"
            ], 401);
        }


        // Check is Tournament Groups is ok
        $totalGroups = sizeof($groups);
        if ($totalGroups == 0) {
            return response()->json([
                'message' => 'Groups not ready!'
            ], 401);
        }
        $GroupsTeams = [];

        // Delete Teams into Groups
        $deleteGroupOb = [
            'tournament_id' => $tournament_id
        ];
        $this->tournamentScheduleQuery->deleteExistingTeams($deleteGroupOb, 'tournament_id');
        // Insert Teams into Groups
        if ($totalGroups == 1) {
            foreach ($getAccpetedtournamentTeams as $value) {
                $ob = [
                    'tournament_id' => $tournament_id,
                    'league_group_id' => $groups[0]['id'],
                    'team_id' => $value['team_id'],
                ];

                array_push($GroupsTeams, $ob);
            }
        } else {
            $gruoupsIndexArray = [];
            for ($i = 0; $i < $totalGroups; $i++) {
                array_push($gruoupsIndexArray, $i);
            }
            $teampGroupsIndexArray = $gruoupsIndexArray;
            shuffle($teampGroupsIndexArray);
            foreach ($getAccpetedtournamentTeams as $value) {
                if (sizeof($teampGroupsIndexArray) > 0) {
                    $ob = [
                        'tournament_id' => $tournament_id,
                        'league_group_id' => $groups[$teampGroupsIndexArray[0]]['id'],
                        'team_id' => $value['team_id'],
                    ];
                    array_push($GroupsTeams, $ob);

                    array_shift($teampGroupsIndexArray);
                } else {
                    $teampGroupsIndexArray = $gruoupsIndexArray;
                    shuffle($teampGroupsIndexArray);
                    $ob = [
                        'tournament_id' => $tournament_id,
                        'league_group_id' => $groups[$teampGroupsIndexArray[0]]['id'],
                        'team_id' => $value['team_id'],
                    ];
                    array_push($GroupsTeams, $ob);

                    array_shift($teampGroupsIndexArray);
                }
            }
        }
        // $collection = collect();
        // foreach($GroupsTeams as $item){
        //     $collection->push((object)$item);

        // }
        // $sorted = $collection->groupBy('league_group_id');
        $tournamentOb = [
            'tournament_id' => $tournament_id,
            'is_complete' => 1
        ];
        $this->tournamentScheduleQuery->insertGroupsTeamsQuery($GroupsTeams);
        $this->tournamentScheduleQuery->updateLeagueGroup($tournamentOb, 'tournament_id');

        // return 'done';

        return $this->makeTournamentDraw($data);
    }

    public function makeTournamentDraw($data)
    {
        $tournament_id = $data['tournament_id'];
        $tournament_info = $this->tournamentScheduleQuery->getRoundsQuery($tournament_id);
        //        \Log::channel('slack')->info('tournament', ['data' => $tournament_info]);
        $groups = $this->tournamentScheduleQuery->getGroupsQuery($tournament_id);
        //        \Log::channel('slack')->info('groups', ['data' => $groups]);
        if ($tournament_info['league_format'] == 'GROUP LEAGUE') {
            $this->makeGroupLeagueDrawTest($groups, $tournament_info);
            $this->makeGroupLeagueKnockoutDraw($tournament_info);
        } else if ($tournament_info['league_format'] == 'IPL') {
            // $this->makeGroupLeagueDraw($groups, $tournament_info);
            $this->makeGroupLeagueDrawTest($groups, $tournament_info);
            $this->makeIPLLeagueKnockoutDraw($tournament_info);
        } else if ($tournament_info['league_format'] == 'SUPER LEAGUE') {
            //    Log::channel('slack')->info('hello');
            $this->makeSuperLeagueDraw($groups, $tournament_info);
            //    $this->makeIPLLeagueKnockoutDraw($tournament_info);
        } else if ($tournament_info['league_format'] == 'KNOCK OUT') {
            $this->makeOnlyKnockoutDraw($groups->first(), $tournament_info);
        }

        return $this->tournamentQuery->getKnockoutMatches($tournament_id);
    }

    public function makeGroupLeagueDrawTest($groups, $tournamentInfo)
    {
        $matches = [];
        $n = 1;
        foreach ($groups as $group) {
            $faceOff = $tournamentInfo['group_settings'][0]['face_off'];
            $teams = $this->tournamentQuery->getTeamsIdByLeagueGroups($group['id']);
            $teams = $teams->shuffle();
            $teams = $teams->toArray();
            $totalTeams = sizeof($teams);
            while ($faceOff--) {
                for ($i = 0; $i < ($totalTeams - 1); $i++) {
                    $j = $i + 1;
                    for ($j; $j < $totalTeams; $j++) {
                        $matches[] = [
                            'tournament_id' => $tournamentInfo['id'],
                            'league_group_id' => $group['id'],
                            'group_round' => 1,
                            'round_type' => $tournamentInfo['group_settings'][0]['round_type'],
                            'match_no' => $n++,
                            'fixture_type' => 'GROUP',
                            'match_type' => $tournamentInfo['match_type'],
                            'ball_type' => $tournamentInfo['ball_type'],
                            'home_team_id' => $teams[$i],
                            'away_team_id' => $teams[$j],
                            'created_at' =>  now(),
                            'updated_at' =>  now(),
                        ];
                    }
                }
            }
        }

        $this->tournamentScheduleQuery->clearAllFixtureByTournamentId($tournamentInfo['id']);
        // Log::channel('slack')->info('d', ['d' => $matches]);
        return $this->tournamentScheduleQuery->createFixtures($matches);
    }

    public function makeGroupLeagueDraw($groups, $tournament_info)
    {
        //        \Log::channel('slack')->info('$groups', ['data' => $groups]);
        //        \Log::channel('slack')->info('$tournament_info', ['data' => $tournament_info]);
        $allGroupsMatches = [];
        $fixtures = [];
        foreach ($groups as $group) {

            $league_group_id = $group['id'];
            $tournament_id = $tournament_info['id'];
            $teams = $this->tournamentQuery->getTeamsIdByLeagueGroups($league_group_id);
            $teams = $teams->shuffle();
            $teams = $teams->toArray();

            // $faceOff = 1;
            $faceOff = $tournament_info['group_settings'][0]['face_off'];
            if (count($teams) % 2 != 0) {
                array_push($teams, 0);
            }
            // Log::channel('slack')->info('faceOff', ['d' => $faceOff]);
            // Log::channel('slack')->info('teams', ['d' => $teams]);
            $leng = sizeof($teams);
            $lastHalf = $leng - 1;
            $totalRoundPerFace = $leng - 1;
            // $fixtures = [];
            for ($f = 0; $f < $faceOff; $f++) {
                for ($round = 0; $round < $totalRoundPerFace; $round++) {
                    // array_push($fixtures,$fix);
                    $r = ($round + ($totalRoundPerFace * $f));
                    if (!isset($fixtures[$r])) {

                        $fixtures[$r] = [];
                        $ob = [
                            'round' => $r + 1,
                            'matches' => []
                        ];
                        $fixtures[$r] = $ob;
                    }

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
                            'round_type' => $tournament_info['group_settings'][0]['round_type'],
                            'fixture_type' => 'GROUP',
                            'match_type' => $tournament_info['match_type'],
                            'ball_type' => $tournament_info['ball_type'],
                            'home_team_id' => $homeTeam,
                            'away_team_id' => $awayTeam,
                            // 'created_at'=>Carbon::now(),
                            // 'updated_at'=>Carbon::now(),
                        ];

                        array_push($fixtures[$r]['matches'], $matchesOb);
                        array_push($allGroupsMatches, $matchesOb);
                    }
                    // return $teams;
                    array_splice($teams, 1, 0, $teams[$leng - 1]);
                    /*now pop up the last element*/
                    array_pop($teams);
                }
            }
        }
        // Log::channel('slack')->info('fixtures', ['d' => $fixtures]);
        $fixtures_length = sizeof($allGroupsMatches);
        $matchNo = 1;
        $serialMaches = [];
        $this->tournamentScheduleQuery->clearAllFixtureByTournamentId($tournament_id);
        foreach ($fixtures as $round) {
            foreach ($round['matches'] as $match) {
                $match['match_no'] = $matchNo;
                $match['group_round'] = $round['round'];
                $mm = $this->tournamentScheduleQuery->createFixtures($match, 'single');
                array_push($serialMaches, $mm);
                $matchNo++;
            }
        }


        // return $this->tournamentQuery->createFixtures($allGroupsMatches, 'multiple');
        // return $this->tournamentQuery->xyz();

        return $serialMaches;
    }

    public function makeGroupLeagueKnockoutDraw($tournament_info)
    {

        // if ($tournament_info['group_settings'][0]['round_type'] == 'IPL') return $this->makeIPLKnockoutDraw($tournament_info);
        //  return $this->makeIPLKnockoutDraw($tournament_info);
        $totalKnockoutTeams = $tournament_info['total_groups'] * $tournament_info['group_winners'];
        $tournament_id = $tournament_info['id'];
        $totalGroups = $tournament_info['total_groups'];
        $lastMatch = $this->tournamentScheduleQuery->getLastMatchNo($tournament_info['id']);
        // Log::channel('slack')->info('testing', ['d' => $lastMatch]);
        $match_no = $lastMatch['match_no'] ?? 0;
        $match_no++;

        // generating temp team name
        $KOTeams = [];
        $KOTeamsName = [];
        $laps = $tournament_info['group_winners'];
        $groups = $this->tournamentQuery->getGroupsByTournament($tournament_id);

        for ($i = 1; $i <= $laps; $i++) {
            foreach ($groups as $index => $group) {
                $generatedTempTeamName = "{$group->name}-{$i}";
                $generatedTempTeam = "G-{$group->id}-{$i}";
                $KOTeamsName[] = $generatedTempTeamName;
                $KOTeams[] = $generatedTempTeam;
            }
        }

        $KOTeamlength = sizeof($KOTeams);
        $flag = false;
        $jIndex = -1;
        while ($KOTeamlength > 1) {
            $round = $KOTeamlength;

            $nextRound = $KOTeamlength / 2;
            $tempTeams = [];
            if ($flag == false) {

                for ($i = 0; $i < $nextRound; $i += 1) {
                    $generatedTempTeam = "";
                    $jIndex = ($KOTeamlength - $i) - 1;
                    $generatedTempTeam = $i . "-" . $jIndex;
                    $data = [
                        'tournament_id' => $tournament_id,
                        'knockout_round' => $round,
                        'round_type' => $this->universalService->getRoundName($KOTeamlength),
                        'fixture_type' => 'KNOCKOUT',
                        'match_no' => $match_no,
                        'match_type' => $tournament_info['match_type'],
                        'ball_type' => $tournament_info['ball_type'],
                        'temp_team_one' => $KOTeams[$i],
                        'temp_team_two' => $KOTeams[$jIndex],
                        'temp_team_one_name' => $KOTeamsName[$i],
                        'temp_team_two_name' => $KOTeamsName[$jIndex],
                    ];
                    $this->tournamentScheduleQuery->createFixtures($data, 'single');
                    $match_no++;
                }
            } else {
                for ($i = 0; $i < $KOTeamlength; $i += 2) {


                    $generatedTempTeam = "";
                    $jIndex = $i + 1;
                    $generatedTempTeam = $i . "-" . $jIndex;

                    $tempTeamOneName = '';
                    $tempTeamTwoName = '';

                    static $tempOne = 1;
                    static $tempTwo = 1;

                    if ($KOTeams[$i]['knockout_round'] > 8) {
                        $tempTeamOneName = "R{$KOTeams[$i]['knockout_round']}-M{$KOTeams[$i]['match_no']}-W";
                        $tempTeamTwoName = "R{$KOTeams[$jIndex]['knockout_round']}-M{$KOTeams[$jIndex]['match_no']}-W";
                    } else if ($KOTeams[$i]['knockout_round'] === 8) {
                        $tempTeamOneName = "QF-M{$KOTeams[$i]['match_no']}-W";
                        $tempTeamTwoName = "QF-M{$KOTeams[$jIndex]['match_no']}-W";
                    } else if ($KOTeams[$i]['knockout_round'] === 4) {
                        $tempTeamOneName = "SF-M{$KOTeams[$i]['match_no']}-W";
                        $tempTeamTwoName = "SF-M{$KOTeams[$jIndex]['match_no']}-W";
                    }

                    if ($KOTeams[$i]['knockout_round'] === 4 AND $tournament_info['third_position'] == 'YES') {
                        // Log::channel('slack')->info('Third Place');
                        $thirdPlaceMatch = [
                            'temp_team_one' => "{$KOTeams[$i]['id']}-L",
                            'temp_team_two' => "{$KOTeams[$jIndex]['id']}-L",
                            'temp_team_one_name' => "SF-M{$KOTeams[$i]['match_no']}-L",
                            'temp_team_two_name' => "SF-M{$KOTeams[$jIndex]['match_no']}-L",
                            'match_no' => $match_no++,
                            'knockout_round' => 3,
                            'tournament_id' => $tournament_info['id'],
                            'round_type' => 'THIRD PLACE',
                            'fixture_type' => 'KNOCKOUT',
                            'match_type' => $tournament_info['match_type'],
                            'ball_type' => $tournament_info['ball_type'],
                        ];

                        $this->tournamentScheduleQuery->createFixtures($thirdPlaceMatch, 'single');
                    }

                    $data = [
                        'tournament_id' => $tournament_id,
                        'knockout_round' => $round,
                        'match_no' => $match_no,
                        'fixture_type' => 'KNOCKOUT',
                        'round_type' => $this->universalService->getRoundName($KOTeamlength),
                        'match_type' => $tournament_info['match_type'],
                        'ball_type' => $tournament_info['ball_type'],
                        'temp_team_one' => "{$KOTeams[$i]['id']}-W",
                        'temp_team_two' => "{$KOTeams[$jIndex]['id']}-W",
                        'temp_team_one_name' => $tempTeamOneName,
                        'temp_team_two_name' => $tempTeamTwoName,
                    ];

                    $this->tournamentScheduleQuery->createFixtures($data, 'single');


                    $match_no++;
                }
            }

            $nextRoundMaches = $this->tournamentQuery->getKnockoutMatchesByRound($tournament_id, $round);
            $nextRoundMaches = $nextRoundMaches->toArray();
            $KOTeams = $nextRoundMaches;
            $flag = true;
            $KOTeamlength;
            $KOTeamlength = $KOTeamlength / 2;
        }

        return "done";

        // $knockoutFixtureData = $this->tournamentQuery->getKnockoutMatches($tournament_id);
        // $knockoutFixtureData->groupBy('round');
        // return $knockoutFixtureData;
        // return Knockoutfixture::where('tournament_id',$tournament_id)->where('fixtureType','BracketA')->get();

    }

    public function makeIPLLeagueKnockoutDraw($tournament_info)
    {
        $tournament_id = $tournament_info['id'];
        $tounament_settings = $tournament_info['group_settings'];
        $first_index = 0;
        $lastMatch = $this->tournamentScheduleQuery->getLastMatchNo($tournament_info['id']);
        $match_no = $lastMatch['match_no'];
        $match_no++;
        $group = $this->tournamentQuery->getGroupsByTournament($tournament_id)->first();

        $data = [
            'tournament_id' => $tournament_id,
            'knockout_round' => 4,
            // 'round_name' => 'Qua',
            'round_type' => 'PLAY OFF',
            'fixture_type' => 'KNOCKOUT',
            'match_no' => $match_no,
            'match_type' => $tournament_info['match_type'],
            'ball_type' => $tournament_info['ball_type'],
            'temp_team_one' => "G-{$group->id}-1",
            'temp_team_two' => "G-{$group->id}-2",
            'temp_team_one_name' => 'LP1',
            'temp_team_two_name' => 'LP2',

        ];

        $play_off_1 = $this->tournamentScheduleQuery->createFixtures($data, 'single');
        $match_no++;
        $data = [
            'tournament_id' => $tournament_id,
            'knockout_round' => 4,
            // 'round_name' => 'Qua',
            'round_type' => 'PLAY OFF',
            'fixture_type' => 'KNOCKOUT',
            'match_no' => $match_no,
            'match_type' => $tournament_info['match_type'],
            'ball_type' => $tournament_info['ball_type'],
            'temp_team_one' => "G-{$group->id}-3",
            'temp_team_two' => "G-{$group->id}-4",
            'temp_team_one_name' => 'LP3',
            'temp_team_two_name' => 'LP4',

        ];
        $play_off_2 = $this->tournamentScheduleQuery->createFixtures($data, 'single');
        $match_no++;
        $data = [
            'tournament_id' => $tournament_id,
            'knockout_round' => 3,
            // 'round_name' => 'Qua',
            'round_type' => 'PLAY OFF',
            'fixture_type' => 'KNOCKOUT',
            'match_no' => $match_no,
            'match_type' => $tournament_info['match_type'],
            'ball_type' => $tournament_info['ball_type'],
            'temp_team_one' => $play_off_1['id'] . "-L",
            'temp_team_two' => $play_off_2['id'] . "-W",
            'temp_team_one_name' => 'PO1-L',
            'temp_team_two_name' => 'PO2-W',

        ];
        $play_off_3 = $this->tournamentScheduleQuery->createFixtures($data, 'single');
        $match_no++;
        $data = [
            'tournament_id' => $tournament_id,
            'knockout_round' => 2,
            // 'round_name' => 'Qua',
            'round_type' => 'FINAL',
            'fixture_type' => 'KNOCKOUT',
            'match_no' => $match_no,
            'match_type' => $tournament_info['match_type'],
            'ball_type' => $tournament_info['ball_type'],
            'temp_team_one' => $play_off_1['id'] . "-W",
            'temp_team_two' => $play_off_3['id'] . "-W",
            'temp_team_one_name' => 'PO1-W',
            'temp_team_two_name' => 'PO3-W',

        ];
        $final = $this->tournamentScheduleQuery->createFixtures($data, 'single');
        $match_no++;
    }


    public function makeOnlyKnockoutDraw($group, $tournament_info)
    {
        $tournamentId = $tournament_info['id'];
        $totalTeams = $tournament_info['group_winners'];
        $this->tournamentScheduleQuery->clearAllFixtureByTournamentId($tournamentId);
        $teams = $this->tournamentQuery->getTeamsIdByLeagueGroups($group->id);
        $matchNo = 1;
        $teams = $teams->shuffle();
        $tr = log($totalTeams, 2);

        // Log::channel('slack')->info('teams', ['data' => $teams]);
        for ($cr = $tr; $cr >= 1; $cr--) {
            $generatedMatches = [];
            // generate knockout matches
            $tt = pow(2, $cr);
            $roundType = $tournament_info['group_settings'][$tr - $cr]['round_type'];

            for ($m = 0; $m < $tt; $m += 2) {

                if ($tt == $totalTeams) {
                    $a = [
                        'home_team_id' => $teams[$m],
                        'away_team_id' => $teams[$m + 1],
                    ];

                } else {

                    $tempTeamOneName = $tempTeamTwoName = '';

                    if ($tt > 4) {
                        $tempTeamOneName = "R{$teams[$m]['knockout_round']}-M{$teams[$m]['match_no']}-W";
                        $tempTeamTwoName = "R{$teams[$m + 1]['knockout_round']}-M{$teams[$m + 1]['match_no']}-W";
                    } else if ($tt == 4) {
                        $tempTeamOneName = "QF-M{$teams[$m]['match_no']}-W";
                        $tempTeamTwoName = "QF-M{$teams[$m + 1]['match_no']}-W";
                    } else if ($tt == 2) {
                        $tempTeamOneName = "SF-M{$teams[$m]['match_no']}-W";
                        $tempTeamTwoName = "SF-M{$teams[$m + 1]['match_no']}-W";
                    }

                    $a = [
                        'temp_team_one' => "{$teams[$m]['id']}-W",
                        'temp_team_two' => "{$teams[$m + 1]['id']}-W",
                        'temp_team_one_name' => $tempTeamOneName,
                        'temp_team_two_name' => $tempTeamTwoName,
                    ];
                }

                if ($tt == 2 AND $tournament_info['third_position'] == 'YES') {
                    // Log::channel('slack')->info('Third Place');
                    $generatedMatches[] = [
                        'temp_team_one' => "{$teams[$m]['id']}-L",
                        'temp_team_two' => "{$teams[$m + 1]['id']}-L",
                        'temp_team_one_name' => "SF-M{$teams[$m]['match_no']}-L",
                        'temp_team_two_name' => "SF-M{$teams[$m + 1]['match_no']}-L",
                        'match_no' => $matchNo++,
                        'knockout_round' => 3,
                        'tournament_id' => $tournamentId,
                        'round_type' => 'THIRD PLACE',
                        'fixture_type' => 'KNOCKOUT',
                        'match_type' => $tournament_info['match_type'],
                        'ball_type' => $tournament_info['ball_type'],
                    ];

                    $roundType = 'FINAL';
                }


                $b = [
                    'match_no' => $matchNo++,
                    'knockout_round' => $tt,
                    'tournament_id' => $tournamentId,
                    'round_type' => $roundType,
                    'fixture_type' => 'KNOCKOUT',
                    'match_type' => $tournament_info['match_type'],
                    'ball_type' => $tournament_info['ball_type'],
                ];

                $generatedMatches[] = array_merge($a, $b);
            }

            $this->tournamentScheduleQuery->createFixtures($generatedMatches);
            $teams = $this->tournamentQuery->getKnockoutMatchesByRound($tournamentId, $tt);
            // Log::channel('slack')->info('teams', ['data' => $teams]);
            $teams = $teams->toArray();
        }

        $this->tournamentScheduleQuery->clearGroupsQuery($tournamentId);

        return "done";
    }


    public function makeSuperLeagueDraw($groups, $tournamentInfo)
    {
        // Log::channel('slack')->info('test');
        $previousGroup = $previousFixtureType = null;

        foreach ($tournamentInfo['group_settings'] as $index => $settings) {
            $previousRoundType = $settings['round_type'];
            $previousFixtureType = $settings['type'];

            if ($settings['round_type'] == 'SUPER LEAGUE') {
                // Log::channel('slack')->info('1st');
                $this->makeGroupLeagueDrawTest($groups, $tournamentInfo);
                $previousGroup = $groups->first();
            } else if ($settings['round_type'] == 'ROUND OF 10' or $settings['round_type'] == 'ROUND OF 6') {
                // Log::channel('slack')->info('second');
                $previousGroup = $this->makeSpecialGroupDraw($previousGroup, $tournamentInfo, $index);
            } else if ($settings['type'] == 'KNOCK OUT') {
                // Log::channel('slack')->info('KNOCKOUT');
                $this->makeSuperLeagueKnockoutDraw($previousGroup, $tournamentInfo, $index);
                break;
            }
        }

        return "done";
    }

    public function makeSuperLeagueKnockoutDraw($previousGroup, $tournamentInfo, $currentRoundIndex)
    {
        $totalTeams = 8;
        if ($tournamentInfo['group_settings'][$currentRoundIndex]['round_type'] == 'SEMI-FINAL') {
            $totalTeams = 4;
        } else if ($tournamentInfo['group_settings'][$currentRoundIndex]['round_type'] == 'FINAL') {
            $totalTeams = 2;
        }

        $teams = range(1, $totalTeams);
        shuffle($teams);
        $tr = log($totalTeams, 2);
        $lastMatch = $this->tournamentScheduleQuery->getLastMatchNo($tournamentInfo['id']);
        $matchNo = $lastMatch['match_no'];

        // Log::channel('slack')->info('teams', ['data' => $teams]);
        for ($cr = $tr; $cr >= 1; $cr--) {
            $generatedMatches = [];
            // generate knockout matches
            $tt = pow(2, $cr);
            $roundType = $tournamentInfo['group_settings'][($tr - $cr + $currentRoundIndex)]['round_type'];

            for ($m = 0; $m < $tt; $m += 2) {
                if ($tt == $totalTeams) {
                    $a = [
                        'temp_team_one' => "G-{$previousGroup['id']}-{$teams[$m]}",
                        'temp_team_two' => "G-{$previousGroup['id']}-{$teams[$m + 1]}",
                        'temp_team_one_name' => "{$previousGroup['league_group_name']}-{$teams[$m]}",
                        'temp_team_two_name' => "{$previousGroup['league_group_name']}-{$teams[$m + 1]}",
                    ];
                } else {
                    $tempTeamOneName = '';
                    $tempTeamTwoName = '';

                    if ($tt == 4) {
                        $tempTeamOneName = "QF-M{$teams[$m]['match_no']}-W";
                        $tempTeamTwoName = "QF-M{$teams[$m + 1]['match_no']}-W";
                    } else if ($tt == 2) {
                        $tempTeamOneName = "SF-M{$teams[$m]['match_no']}-W";
                        $tempTeamTwoName = "SF-M{$teams[$m + 1]['match_no']}-W";
                    }

                    $a = [
                        'temp_team_one' => "{$teams[$m]['id']}-W",
                        'temp_team_two' => "{$teams[$m + 1]['id']}-W",
                        'temp_team_one_name' => $tempTeamOneName,
                        'temp_team_two_name' => $tempTeamTwoName,
                    ];
                }

                if ($tt == 2 AND $tournamentInfo['third_position'] == 'YES') {
                    // Log::channel('slack')->info('Third Place');
                    $generatedMatches[] = [
                        'temp_team_one' => "{$teams[$m]['id']}-L",
                        'temp_team_two' => "{$teams[$m + 1]['id']}-L",
                        'temp_team_one_name' => "SF-M{$teams[$m]['match_no']}-L",
                        'temp_team_two_name' => "SF-M{$teams[$m + 1]['match_no']}-L",
                        'match_no' => ++$matchNo,
                        'knockout_round' => 3,
                        'tournament_id' => $tournamentInfo['id'],
                        'round_type' => 'THIRD PLACE',
                        'fixture_type' => 'KNOCKOUT',
                        'match_type' => $tournamentInfo['match_type'],
                        'ball_type' => $tournamentInfo['ball_type'],
                    ];

                    $roundType = 'FINAL';
                }

                $b = [
                    'match_no' => ++$matchNo,
                    'knockout_round' => $tt,
                    'tournament_id' => $tournamentInfo['id'],
                    'round_type' => $roundType,
                    'fixture_type' => 'KNOCKOUT',
                    'match_type' => $tournamentInfo['match_type'],
                    'ball_type' => $tournamentInfo['ball_type'],
                ];

                $generatedMatches[] = array_merge($a, $b);
            }

            $this->tournamentScheduleQuery->createFixtures($generatedMatches);
            $previousRoundMatches = $this->tournamentQuery->getKnockoutMatchesByRound($tournamentInfo['id'], $tt);
            $teams = $previousRoundMatches->toArray();
        }

        return "done";
    }


    public function makeSpecialGroupDraw($previousGroup, $tournamentInfo, $currentRoundIndex)
    {
        // Log::channel('slack')->info('third');
        $matches = [];
        $lastMatch = $this->tournamentScheduleQuery->getLastMatchNo($tournamentInfo['id']);
        $n = $lastMatch['match_no'];
        $faceOff = $tournamentInfo['group_settings'][$currentRoundIndex]['face_off'];
        $totalTeams = ($tournamentInfo['group_settings'][$currentRoundIndex]['round_type'] == 'ROUND OF 10') ? 10 : 6;
        $teams = range(1, $totalTeams);

        $data = [
            'tournament_id' => $tournamentInfo['id'],
            'league_group_name' => $tournamentInfo['group_settings'][$currentRoundIndex]['round_type'],
            'round_type' => $tournamentInfo['group_settings'][$currentRoundIndex]['type'],
        ];

        $group = $this->tournamentScheduleQuery->storeGroupsQuery($data);

        while ($faceOff--) {
            shuffle($teams);

            for ($i = 0; $i < ($totalTeams - 1); $i++) {
                $j = $i + 1;
                for ($j; $j < $totalTeams; $j++) {
                    $matches[] = [
                        'tournament_id' => $tournamentInfo['id'],
                        'league_group_id' => $group['id'],
                        'group_round' => ($currentRoundIndex + 1),
                        'round_type' => $tournamentInfo['group_settings'][$currentRoundIndex]['round_type'],
                        'match_no' => ++$n,
                        'fixture_type' => 'GROUP',
                        'match_type' => $tournamentInfo['match_type'],
                        'ball_type' => $tournamentInfo['ball_type'],
                        'temp_team_one' => "G-{$previousGroup['id']}-{$teams[$i]}",
                        'temp_team_two' => "G-{$previousGroup['id']}-{$teams[$j]}",
                        'temp_team_one_name' => "{$previousGroup['league_group_name']}-{$teams[$i]}",
                        'temp_team_two_name' => "{$previousGroup['league_group_name']}-{$teams[$j]}",
                        'created_at' =>  now(),
                        'updated_at' =>  now(),
                    ];
                }
            }
        }

        $this->tournamentScheduleQuery->createFixtures($matches);
        return $group;
    }
}
