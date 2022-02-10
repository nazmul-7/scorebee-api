<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Team\TeamQuery;
use App\Http\Controllers\Notification\NotificationService;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Log;

use function PHPSTORM_META\map;

class ClubService
{
    private $clubQuery;
    private $notificationService;

    private $teamQuery;

    public function __construct(ClubQuery $clubQuery, NotificationService $notificationService, TeamQuery $teamQuery)
    {
        $this->clubQuery = $clubQuery;
        $this->notificationService = $notificationService;
        $this->teamQuery = $teamQuery;
    }

    //  ======================================== Club CRUD Start ===========================================================
    public function getClubById($clubOwnerId)
    {
        $playerId = Auth::guard('sanctum')->id();

        $club = $this->clubQuery->getClubByIdQuery($clubOwnerId);

        $club->type = 'CLUB';
        $club->member_status = 'GUEST_USER';

        if ($playerId) {
            $isRequestExist = $this->clubQuery->checkRequestIsValidQuery($clubOwnerId, $playerId, 'PENDING');
            $isMemberOfClub = $this->clubQuery->checkRequestIsValidQuery($clubOwnerId, $playerId, 'ACCEPTED');

            if ($isRequestExist) {
                $club->member_status = 'PENDING_MEMBER';
            } else if ($isMemberOfClub) {
                $club->member_status = 'MEMBER_ALREADY';
            } else {
                $club->member_status = 'NEW_MEMBER';
            }
        }

        return $club;
    }
    //  ======================================== Club CRUD end =============================================================

    //  ======================================== Club to Player request Start ==============================================
    public function sentPlayerRequest($data): bool
    {
        $clubOwnerId = Auth::id();
        $isValidClubOwner = $this->clubQuery->checkValidClubOwnerQuery($clubOwnerId);
        $isValidPlayer = $this->clubQuery->checkValidPlayerQuery($data['player_id']);
        $isRequestExists = $this->clubQuery->checkRequestIsValidQuery($clubOwnerId, $data['player_id']);

        if ($isValidClubOwner and $isValidPlayer and !$isRequestExists) {
            $this->clubQuery->sentPlayerRequestQuery(array_merge($data, [
                'club_owner_id' => $clubOwnerId,
                'requested_by' => 'CLUB',
                'status' => 'PENDING'
            ]));

            $ob = [
                'from' =>  $clubOwnerId,
                'to' => $data['player_id'],
                'msg' => $isValidClubOwner['first_name'] . ' ' . $isValidClubOwner['last_name'] . ' has requested you to join the club.',
                'type' => 'club_to_player_join_request',
                'club_id' => $clubOwnerId,
            ];

            $this->notificationService->sendNotificationGlobalMethod($ob);

            return true;
        }
        return false;
    }

    public function acceptPlayerRequest($data): bool
    {
        $clubOwnerId = Auth::id();
        $isValidClubOwner = $this->clubQuery->checkValidClubOwnerQuery($clubOwnerId);
        $isRequestExists = $this->clubQuery->checkRequestIsValidQuery($clubOwnerId, $data['player_id'], $status = 'PENDING', $requestedBy = 'PLAYER');

        if ($isValidClubOwner && $isRequestExists) {
            $ob = [
                'from' =>  $clubOwnerId,
                'to' => $data['player_id'],
                'msg' => $isValidClubOwner['first_name'] . ' ' . $isValidClubOwner['last_name'] . ' has accepted your club join request.',
                'type' => 'club_to_player_join_accept',
                'club_id' => $clubOwnerId,
            ];

            $this->notificationService->sendNotificationGlobalMethod($ob);
            return $this->clubQuery->updatePlayerRequestQuery($clubOwnerId, $data['player_id'], $attributes = ['status' => 'ACCEPTED']);
        }

        return false;
    }

    public function removePlayerRequest($data): bool
    {
        $clubOwnerId = Auth::id();
        $isClubOwner = $this->clubQuery->checkValidClubOwnerQuery($clubOwnerId);
        $isRequestExists = $this->clubQuery->checkRequestIsValidQuery($clubOwnerId, $data['player_id'], $data['status']);

        if ($isClubOwner && $isRequestExists) {
            $ob = [
                'from' =>  $clubOwnerId,
                'to' => $data['player_id'],
                'msg' => $isClubOwner['first_name'] . ' ' . $isClubOwner['last_name'] . ' has canceled your club join request.',
                'type' => 'club_to_player_join_cancel',
                'club_id' => $clubOwnerId,
            ];

            if ($data['status'] == 'ACCEPTED') {
                $ob['msg'] = $isClubOwner['first_name'] . ' ' . $isClubOwner['last_name'] . ' has removed you from his club.';
                $ob['type'] = 'club_to_player_leave_request';
            }

            $this->notificationService->sendNotificationGlobalMethod($ob);
            return $this->clubQuery->removePlayerRequestQuery($clubOwnerId, $data['player_id']);
        }

        return false;
    }

    public function getPlayerRequestsList($data)
    {
        return $this->clubQuery->getPlayerRequestsListQuery($data);
    }

    public function getPlayerRequestsListV2($data)
    {
        return $this->clubQuery->getPlayerRequestsListV2Query($data);
    }

    public function searchPlayers($data)
    {
        return $this->clubQuery->searchPlayers($data);
    }
    //  ======================================== Club to Player request End ==========================================================

    //  ======================================== Club to Club challenge request Start ================================================
    public function getClubChallengeRequests()
    {
        $challengeRequests = $this->clubQuery->getClubChallengeRequestsQuery();

        return [
            'requests' => $challengeRequests->all(),
            'current_page' => $challengeRequests->currentPage(),
            'last_page' => $challengeRequests->lastPage()
        ];
    }

    public function sentClubChallengeRequest($data)
    {
        $challenger = Auth::user();
        $data['challenger_id'] = $challenger['id'];

        if ($challenger['registration_type'] == 'CLUB_OWNER' and $data['opponent_id'] != $data['challenger_id']) {
            $lastRequest = $this->clubQuery->getChallengedRequestByOpponentQuery($data);

            if (isset($lastRequest)) {
                if ($lastRequest['status'] == 'PENDING') {
                    return 'PENDING';
                } else if (!isset($lastRequest['fixture']) or (isset($lastRequest['fixture']) and !$lastRequest['fixture']['is_match_finished'])) {
                    return 'UNFINISHED';
                }
            }

            $ob = [
                'from' =>  $challenger['id'],
                'to' => $data['opponent_id'],
                'msg' => $challenger['first_name'] . ' ' . $challenger['last_name'] . ' challenged you to play an individual match.',
                'type' => 'club_to_club_challenge_request_sent',
                'club_id' => $data['opponent_id'],
            ];

            $this->notificationService->sendNotificationGlobalMethod($ob);

            $data['status'] = 'PENDING';
            $this->clubQuery->sentClubChallengeRequestQuery($data);
            return 'SEND';
        }

        return 'INVALID';
    }

    public function cancelClubChallengeRequest($data)
    {
        $authUser = Auth::user();
        $res = 'INVALID';

        $challengeRequest = $this->clubQuery->getChallengedRequestByIdQuery($data);

        if (
            isset($challengeRequest) and $challengeRequest['status'] == $data['status']
            and ($challengeRequest['challenger_id'] == $authUser['id'] or $challengeRequest['opponent_id'] == $authUser['id'])
        ) {
            $res = 'REMOVED';

            if ($data['status'] == 'PENDING') {
                $res = 'CANCELLED';
                $senderId = $challengeRequest['challenger_id'] == $authUser['id'] ? $authUser['id'] : $challengeRequest['opponent_id'];
                $receiverId = $challengeRequest['challenger_id'] != $authUser['id'] ? $authUser['id'] : $challengeRequest['opponent_id'];

                $ob = [
                    'from' =>  $senderId,
                    'to' => $receiverId,
                    'type' => 'club_to_club_challenge_request_cancel',
                    'msg' => $authUser['first_name'] . ' ' . $authUser['last_name'] . ' declined your challenged request.',
                    'club_id' => $receiverId,
                ];

                if ($senderId == $challengeRequest['challenger_id']) {
                    $ob['msg'] = $authUser['first_name'] . ' ' . $authUser['last_name'] . ' cancelled his challenged request.';
                }

                $this->notificationService->sendNotificationGlobalMethod($ob);
            }

            $this->clubQuery->cancelClubChallengeRequestQuery($challengeRequest['id']);
        }

        return $res;
    }

    public function acceptClubChallengeRequest($data)
    {
        $authUser = Auth::user();
        $res = 'INVALID';

        $challengeRequest = $this->clubQuery->getChallengedRequestByIdQuery($data);

        if (isset($challengeRequest) and $challengeRequest['status'] == 'PENDING' and $challengeRequest['opponent_id'] == $authUser['id']) {

            $ob = [
                'from' =>  $authUser['id'],
                'to' => $challengeRequest['challenger_id'],
                'msg' => $authUser['first_name'] . ' ' . $authUser['last_name'] . ' accepted your challenged request.',
                'type' => 'club_to_club_challenge_request_accept',
                'club_id' => $challengeRequest['challenger_id'],
            ];

            $this->notificationService->sendNotificationGlobalMethod($ob);
            $this->clubQuery->updateClubChallengeRequestQuery($challengeRequest['id'], ['status' => 'ACCEPTED']);
            $res = 'ACCEPTED';
        }

        return $res;
    }

    public function getTeamsListByClub($data)
    {
        return $this->teamQuery->getOwnerTeamsListQuery($data);
    }

    public function myTeams($data)
    {
        return $this->teamQuery->myTeams($data);
    }

    //  ======================================== Club to Club challenge request End ==================================================

    //  ====================================== Club matches list start =====================================================
    public function getClubMatchesListByFilter($data): array
    {
        $messyCollection = $this->clubQuery->getClubMatchesListByFilterQuery($data);
        $formattedCollection = collect();

        foreach ($messyCollection as $messyObj) {
            $formattedObj = collect();

            //          match general information
            $formattedObj->put('fixture_id', $messyObj->id);

            if ($messyObj->tournament and $messyObj->tournament->tournament_name) {
                $formattedObj->put('tournament_name', $messyObj->tournament->tournament_name);
                $formattedObj->put('tournament_city', $messyObj->tournament->city);
            }

            if (($messyObj->toss_winner_team_id == $messyObj->home_team_id and $messyObj->team_elected_to === 'BAT')
                or ($messyObj->toss_winner_team_id == $messyObj->away_team_id and $messyObj->team_elected_to === 'BOWL')
            ) {
                $formattedObj->put('batting_team_id', $messyObj->home_team_id);
                $formattedObj->put('batting_team_name', $messyObj->homeTeam->team_name);
                $formattedObj->put('batting_team_logo', $messyObj->homeTeam->team_logo);
                $formattedObj->put('bowling_team_id', $messyObj->away_team_id);
                $formattedObj->put('bowling_team_name', $messyObj->awayTeam->team_name);
                $formattedObj->put('bowling_team_logo', $messyObj->awayTeam->team_logo);
                $formattedObj->put('batting_team_runs_took', $messyObj->home_team_runs);
                $formattedObj->put('batting_team_overs_faced', $messyObj->home_team_overs);
                $formattedObj->put('batting_team_wickets_loss', $messyObj->home_team_wickets);
                $formattedObj->put('bowling_team_runs_took', $messyObj->away_team_runs);
                $formattedObj->put('bowling_team_overs_faced', $messyObj->away_team_overs);
                $formattedObj->put('bowling_team_wickets_loss', $messyObj->away_team_wickets);
            } else if (
                ($messyObj->toss_winner_team_id == $messyObj->home_team_id and $messyObj->team_elected_to === 'BOWL')
                or ($messyObj->toss_winner_team_id == $messyObj->away_team_id and $messyObj->team_elected_to === 'BAT')
            ) {
                $formattedObj->put('batting_team_id', $messyObj->away_team_id);
                $formattedObj->put('batting_team_name', $messyObj->awayTeam->team_name);
                $formattedObj->put('batting_team_logo', $messyObj->awayTeam->team_logo);
                $formattedObj->put('bowling_team_id', $messyObj->home_team_id);
                $formattedObj->put('bowling_team_name', $messyObj->homeTeam->team_name);
                $formattedObj->put('bowling_team_logo', $messyObj->homeTeam->team_logo);
                $formattedObj->put('batting_team_runs_took', $messyObj->away_team_runs);
                $formattedObj->put('batting_team_overs_faced', $messyObj->away_team_overs);
                $formattedObj->put('batting_team_wickets_loss', $messyObj->away_team_wickets);
                $formattedObj->put('bowling_team_runs_took', $messyObj->home_team_runs);
                $formattedObj->put('bowling_team_overs_faced', $messyObj->home_team_overs);
                $formattedObj->put('bowling_team_wickets_loss', $messyObj->home_team_wickets);
            }

            $formattedObj->put('match_final_result', $messyObj->match_final_result);

            //          formatting match_date and time
            $matchDateTime = date('d M, Y', strtotime($messyObj->match_date));
            $formattedObj->put('match_date', $matchDateTime);
            $formattedCollection->push($formattedObj);
        }

        return [
            'current_page' => $messyCollection->currentPage(),
            'last_page' => $messyCollection->lastPage(),
            'matches' => $formattedCollection,
        ];
    }
    //  ======================================== Club matches list end =====================================================

    //  ====================================== Club matches list by filter start ===========================================
    public function getClubMembersListByFilter($data): array
    {
        $team = isset($data['team_id']) ? $this->teamQuery->getTeamByIdQuery($data['team_id']) : null;
        $players = $this->clubQuery->getClubMembersListByFilterQuery($data);
        $formattedCollection = collect($players->all());

        $formattedCollection->map(function ($player) use ($team) {
            $player->is_captain = 0;

            if ($team and $team->captain_id == $player->id) {
                $player->is_captain = 1;
            }
        });

        return [
            'current_page' => $players->currentPage(),
            'last_page' => $players->lastPage(),
            'players' => $formattedCollection->sortByDesc('is_captain')->flatten(),
        ];
    }
    //  ====================================== Club matches list by filter end==============================================

    //  ====================================== Club stats by filter start ==================================================
    public function getClubStatsListByFilter($data)
    {
        $stats = $this->clubQuery->getClubStatsByFilterQuery($data);

        $avg = $stats->total_valid_matches_played ? number_format($stats->total_matches_won / $stats->total_valid_matches_played, 2) : "---";
        $win = $avg;
        $matches = $stats->total_matches_played ?? 0;
        $upcoming = $stats->total_upcoming_matches ?? 0;
        $won = $stats->total_matches_won;
        $lost = $stats->total_matches_lost;
        $tie = $stats->total_tied_matches ?? 0;
        $drawn = $stats->total_drawn_matches ?? 0;
        $nr = $stats->total_abandoned_matches ?? 0;
        $toss = $stats->total_tosses_won ?? 0;
        $bat_first = $stats->total_bat_first ?? 0;
        $field_first = $stats->total_field_first ?? 0;

        return [
            ['Matches' => (string)$matches],
            ['Upcoming' => (string)$upcoming],
            ['Won' => (string)$won],
            ['Lost' => (string)$lost],
            ['Tie' => (string)$tie],
            ['Drawn' => (string)$drawn],
            ['NR' => (string)$nr],
            ['Win' => (string)$win],
            ['Toss' => (string)$toss],
            ['Bat First' => (string)$bat_first],
            ['Field First' => (string)$field_first]
        ];
    }
    //  ====================================== Club stats by filter end ====================================================

    //  ====================================== Club leaderboard by filter start ============================================
    public function getClubBattingLeaderboardByFilter($data)
    {
        $players = $this->clubQuery->getClubBattingLeaderboardByFilterQueryTestV3($data);
        foreach ($players->all() as $player) {
            $player->total_innings_played = (string)$player->total_innings_played ?? "0";
            $player->total_runs_achieved = (string)$player->total_runs_achieved ?? "0";
            $player->highest_runs_scored = (string)$player->highest_runs_scored ?? "0";
            $player->total_not_outs = (string)$player->total_not_outs ?? "0";
            $player->total_fours_hit = (string)$player->total_fours_hit ?? "0";
            $player->total_sixes_hit = (string)$player->total_sixes_hit ?? "0";
            $player->total_fifties = (string)$player->total_fifties ?? "0";
            $player->total_hundreds = (string)$player->total_hundreds ?? "0";
            //            avg runs calculation
            $avg = $player->total_outs ? number_format($player->total_runs_achieved / $player->total_outs, 2) : "---";
            $player->avg_runs_achieved = (string)$avg;
            //            strike rate calculation
            $strikeRate = $player->total_balls_faced ? number_format((($player->total_runs_achieved / $player->total_balls_faced) * 100), 2) : "---";
            $player->strike_rate = (string)$strikeRate;
            unset($player->total_outs);
            unset($player->total_outs);
            unset($player->total_balls_faced);
        }

        return [
            'current_page' => $players->currentPage(),
            'last_page' => $players->lastPage(),
            'players' => $players->all(),
        ];
    }

    public function getClubBowlingLeaderboardByFilter($data)
    {
        $players = $this->clubQuery->getClubBowlingLeaderboardByFilterQueryTestV3($data);

        foreach ($players->all() as $player) {
            $player->total_innings_played = $player->total_innings_played ?? "0";
            $player->total_wickets = $player->total_wickets ?? 0;
            $player->highest_wickets = $player->highest_wickets ?? 0;
            $player->total_maiden_overs = $player->total_maiden_overs ?? 0;

            //            avg runs calculation
            $avg = $player->total_wickets ? number_format($player->total_runs_gave / $player->total_wickets, 2) : "---";
            $player->avg_runs_gave = (string)$avg;

            //            strike rate calculation
            $strikeRate = $player->total_wickets ? number_format(($player->total_balls_bowled / $player->total_wickets), 2) : "---";
            $player->strike_rate = (string)$strikeRate;

            //            economy calculation
            $oversBowled = (int)($player->total_balls_bowled / 6) + (($player->total_balls_bowled % 6) / 6);
            $economy = $oversBowled ? number_format(($player->total_runs_gave / $oversBowled), 2) : "---";
            $player->economy = $economy;
            $player->total_innings_played = (string)$player->total_innings_played;
            $player->total_maiden_overs = (string)$player->total_maiden_overs;
            $player->total_wickets = (string)$player->total_wickets;
            $player->highest_wickets = (string)$player->highest_wickets;

            unset($player->total_balls_bowled);
        }

        return [
            'current_page' => $players->currentPage(),
            'last_page' => $players->lastPage(),
            'players' => $players->all(),
        ];
    }

    public function getClubFieldingLeaderboardByFilter($data)
    {
         $players = $this->clubQuery->getClubFieldingLeaderboardByFilterQueryTestV3($data);
        // $players = $this->clubQuery->getClubFieldingLeaderboardByFilterQuery($data);

        $formattedCollection = collect($players->all());

        $formattedCollection->map(function ($player) {
            $player->total_matches = (string)$player->total_matches;
            $player->total_dismissals = (string)($player->total_catches + $player->total_run_outs + $player->total_stumped_outs + $player->total_assisted_outs);
            $player->total_catches = (string)$player->total_catches;
            $player->total_run_outs = (string)$player->total_run_outs;
        });

        return [
            'current_page' => $players->currentPage(),
            'last_page' => $players->lastPage(),
            'players' => $formattedCollection,
        ];
    }

    public function getClubFilterAttributes($data)
    {
        $teams = !isset($data['team_id']) ? $this->clubQuery->getTeamsListByClubQuery($data['club_owner_id']) : null;
        $tournaments = $this->clubQuery->getTournamentsListByClubQuery($data);

        // generating years
        $years = $this->clubQuery->getFixtureYears($data);
        $years = $years->map(function ($item) {
            return ['value' => (string)$item['year'], 'display_value' => (string)$item['year']];
        });

        $tournaments = collect($tournaments);
        $tournaments->map(function ($item) use ($tournaments) {
            $item->value = (string)$item->id;
            $item->display_value = (string)$item->tournament_name;
            unset($item->id);
            unset($item->tournament_name);
        });

        $teams = collect($teams);
        $teams->map(function ($item) use ($teams) {
            $item->value = (string)$item->id;
            $item->display_value = (string)$item->team_name;
            unset($item->id);
            unset($item->team_name);
        });

        $overs = [
            'type' => 'Overs',
            'values' => [
                ['value' => '20', 'display_value' => '20'],
                ['value' => '50', 'display_value' => '50',]
            ]
        ];

        $ballTypes = [
            'type' => 'Ball Types',
            'values' => [
                ['value' => 'LEATHER', 'display_value' => "Leather"],
                ['value' => 'TENNIS', 'display_value' => "Tennis"],
            ]
        ];

        $years = [
            'type' => 'Years',
            'values' => $years
        ];

        $innings = [
            'type' => 'Innings Type',
            'values' => [
                ['value' => 'LIMITED OVERS', 'display_value' => "Limited Overs"],
                ['value' => 'TEST', 'display_value' => "Test"],
            ]
        ];

        $tournaments = [
            'type' => 'Tournaments',
            'values' => $tournaments
        ];

        $tournamentCategories = [
            'type' => 'Tournament Category',
            'values' => [
                ['value' => 'OPEN', 'display_value' => "Open"],
                ['value' => 'CORPORATE', 'display_value' => "Corporate"],
                ['value' => 'COMMUNITY', 'display_value' => "Community"],
                ['value' => 'SCHOOL', 'display_value' => "School"],
                ['value' => 'OTHER', 'display_value' => "Other"],
                ['value' => 'BOX CRICKET', 'display_value' => "Box Cricket"],
                ['value' => 'SERIES', 'display_value' => "Series"],
            ]
        ];

        $teams = [
            'type' => 'Teams',
            'values' => $teams
        ];


        $formattedArr = array();
        $formattedArr[0] = $overs;
        $formattedArr[1] = $ballTypes;
        $formattedArr[2] = $years;
        $formattedArr[3] = $innings;
        $formattedArr[4] = $tournaments;
        $formattedArr[5] = $tournamentCategories;

        if (!isset($data['team_id'])) $formattedArr[6] = $teams;

        return $formattedArr;
    }
    //  ====================================== Club leaderboard by filter end ==============================================
}
