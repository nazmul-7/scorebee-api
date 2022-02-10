<?php

namespace App\Http\Controllers\Team;
// use App\Http\Controllers\Notification\NotificationQuery;
use App\Http\Controllers\FileHandler\FileHandlerService;
use App\Http\Controllers\Notification\NotificationService;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;
use App\globalMethod\imageUploads;

class TeamService
{
    private $teamQuery;
    private $notificationQuery;
    private $fileHandlerService;

    public function __construct(TeamQuery $teamQuery,NotificationService $notificationQuery, FileHandlerService $fileHandlerService)
    {
        $this->teamQuery = $teamQuery;
        $this->notificationQuery = $notificationQuery;
        $this->fileHandlerService = $fileHandlerService;
    }

//  ============================================ Team CRUD start =============================================
    public function getTeamById($teamId){
        $team = $this->teamQuery->getTeamByIdQuery($teamId);
        $formattedData = array();
        $formattedData['id'] = $team->id;
        $formattedData['name'] = $team->team_name;
        $formattedData['banner'] = $team->team_banner;
        $formattedData['logo'] = $team->team_logo;
        $formattedData['city'] = $team->city;
        $formattedData['country'] = "";
        $formattedData['member_status'] = "";
        $formattedData['pending_requests'] = 0;
        $formattedData['type'] = 'TEAM';
        return $formattedData;
    }

    public function getOwnerTeamsList($data)
    {
        $data['club_owner_id'] = Auth::id();
        return $this->teamQuery->getOwnerTeamsListQuery($data);
    }

    public function createTeam($data)
    {
        $isClubOwner = $this->teamQuery->checkValidClubOwnerQuery(Auth::id());
        $isClubOwner = true;

        if ($isClubOwner) {
            if (isset($data['team_banner'])) {
                $data['team_banner'] = $this->fileHandlerService->imageUploader($data['team_banner']);
            }

            if (isset($data['team_logo'])) {
                $data['team_logo'] = $this->fileHandlerService->imageUploader($data['team_logo']);
            }

            $data['owner_id'] = Auth::id();

            return $this->teamQuery->createTeamQuery($data);
        }

        return false;
    }

    public function updateTeam($data)
    {
        $teamId = $data['team_id'];

        if (isset($data['team_banner']) || isset($data['team_logo'])) {
            $baseURL = str_replace('api/', '', env('APP_URL'));
            $team = $this->teamQuery->getTeamByIdQuery($data['team_id']);

            if (isset($data['team_banner'])) {
                $this->fileHandlerService->imageRemover($team->team_banner, 'default_team_banner.png');
                $data['team_banner'] = $this->fileHandlerService->imageUploader($data['team_banner']);
            }
            if (isset($data['team_logo'])) {
                $this->fileHandlerService->imageRemover($team->team_logo, 'default_team_logo.png');
                $data['team_logo'] = $this->fileHandlerService->imageUploader($data['team_logo']);
            }
        }

        unset($data['team_id']);
        $isUpdated = $this->teamQuery->updateTeamQuery($teamId, Auth::id(), $data);
        //        return $isUpdated;
        if ($isUpdated) {
            return $this->teamQuery->getTeamByIdQuery($teamId);
        }

        return response()->json([
            'messages' => 'You cannot perform that action.'
        ], 402);
    }

    public function deleteTeam($data)
    {
        $baseURL = env('APP_URL');
        $team = $this->teamQuery->getTeamByIdQuery($data['team_id']);

        $matchesPlayed = $this->teamQuery->matchesPlayed($data['team_id']);
        // Log::channel('slack')->info('testing', ['d' => $matchesPlayed]);
        if($matchesPlayed){
            return 'CANT_DELETE';
        }

        if ($team->team_banner) {
            $this->fileHandlerService->imageRemover($team->team_banner, 'default_team_banner.png');
        }

        if ($team->team_logo) {
            $this->fileHandlerService->imageRemover($team->team_logo, 'default_team_logo.png');
        }

        $isDelete = $this->teamQuery->deleteTeamQuery($team->id, Auth::id());
        if($isDelete){
            return 'DELETED';
        }

        return 'INVALID';
    }


// ============================================= Team CRUD End =========================================================

//  ======================================== Team Players CRUD Start ===================================================
    public function searchClubPlayers($data)
    {
        return $this->teamQuery->searchClubPlayersQuery($data);
    }

    public function getTeamPlayersList($data)
    {
        $team = $this->teamQuery->getTeamByIdQuery($data['team_id']);
        $players = $this->teamQuery->getTeamPlayersListQuery($data);

        $players->map(function($item) use($team){
            $item['is_captain'] = $team['captain_id'] == $item['id'] ? 1 : 0;
            $item['is_wicket_keeper'] = $team['wicket_keeper_id'] == $item['id'] ? 1 : 0;
        });

        return $players;
    }

    public function addTeamPlayer($data): bool
    {
        $ownerId = Auth::id();
        $isTeamOwner = $this->teamQuery->checkValidTeamOwnerQuery($ownerId, $data['team_id']);
        $isValidClubPlayer = $this->teamQuery->checkValidClubPlayerQuery($ownerId, $data['player_id']);
        $isPlayerAlreadyExists = $this->teamQuery->isPlayerAlreadyExistsQuery($data['team_id'], $data['player_id']);
        $teamPlayersNumber = $this->teamQuery->getTeamPlayersNumber($data['team_id']);
        $data['club_player_id'] = $isValidClubPlayer['id'];

        if ($isTeamOwner and $isValidClubPlayer and !$isPlayerAlreadyExists and $teamPlayersNumber < 25) {

            $this->teamQuery->addTeamPlayerQuery($data);

            $ob=[
                'from' => $ownerId,
                'to' =>$data['player_id'],
                'msg'=>'You are added to the '.$isTeamOwner['team_name'],
                'type'=>'team_added_player',
                'team_id'=>$isTeamOwner['id'],
            ];

            $this->notificationQuery->sendNotificationGlobalMethod($ob);

            return true;
        }
        return false;
    }

    public function updateTeamPlayer($data): bool
    {
        $ownerId = Auth::id();
        $isTeamOwner = $this->teamQuery->checkValidTeamOwnerQuery($ownerId, $data['team_id']);
        $isPlayerAlreadyExists = $this->teamQuery->isPlayerAlreadyExistsQuery($data['team_id'], $data['player_id']);

        $attributes = [];
        if ($isTeamOwner and $isPlayerAlreadyExists) {
            if ($data['player_role'] == 'CAPTAIN') {
                $attributes = [ 'captain_id' => $data['player_id'] ];
            } else if ($data['player_role'] == 'WICKET_KEEPER') {
                $attributes = [ 'wicket_keeper_id' => $data['player_id'] ];
            }

            return $this->teamQuery->updateTeamQuery($data['team_id'], $ownerId, $attributes);
        }

        return false;
    }

    public function removeTeamPlayer($data): bool
    {
        $ownerId = Auth::id();
        $isTeamOwner = $this->teamQuery->checkValidTeamOwnerQuery($ownerId, $data['team_id']);
        $isPlayerAlreadyExists = $this->teamQuery->isPlayerAlreadyExistsQuery($data['team_id'], $data['player_id']);

        if ($isTeamOwner && $isPlayerAlreadyExists) {
            $ob=[
                'from' => $ownerId,
                'to' =>$data['player_id'],
                'msg'=>'You are removed from '. $isTeamOwner['team_name'],
                'type'=>'team_remove_player',
                'team_id'=>$isTeamOwner['id'],
            ];

            $this->notificationQuery->sendNotificationGlobalMethod($ob);
            $this->teamQuery->resetTeamCaptainOrWicketKeeper('id', $data['team_id'], 'captain_id', $data['player_id']);
            $this->teamQuery->resetTeamCaptainOrWicketKeeper('id', $data['team_id'], 'wicket_keeper_id', $data['player_id']);
            return $this->teamQuery->removeTeamPlayerQuery($data['team_id'], $data['player_id']);
        }

        return false;
    }
//  ======================================== Team Players CRUD end =====================================================

//  ======================================== Team Squads start =========================================================
    public function getTeamSquadList($data)
    {
        $players = $this->teamQuery->getTeamSquadListQuery($data);
        $isDeletable = 1;
        $matchesPlayed = $this->teamQuery->matchesPlayed($data['team_id']);
        if($matchesPlayed){
            $isDeletable = 0;
        }
        return [
            'is_deletable' => $isDeletable,
            'players' => $players
        ];
    }

    public function updateTeamSquad($data): array
    {
        $res = [
            'status_code' => 402,
            'message' => "You cannot perform that action."
        ];

        $ownerId = Auth::id();
        $isTeamOwner = $this->teamQuery->checkValidTeamOwnerQuery($ownerId, $data['team_id']);
        $isPlayerAlreadyExists = $this->teamQuery->isPlayerAlreadyExistsQuery($data['team_id'], $data['player_id']);

        if ($data['squad_type'] == 'MAIN') {
            $teamMainElevensPlayersNumber = $this->teamQuery->getTeamPlayersNumber($data['team_id'], $data['squad_type']);

            if ($teamMainElevensPlayersNumber >= 11) {
                $res['message'] = "You can't add more than 11 players in you Main XI.";
                return $res;
            }
        } else if ($data['squad_type'] == 'EXTRA') {
            $teamExtraPlayersNumber = $this->teamQuery->getTeamPlayersNumber($data['team_id'], $data['squad_type']);
            if ($teamExtraPlayersNumber >= 3) {
                $res['message'] = "You can't add more than 3 players in you Main XI";
                return $res;
            }
        }

        if ($isTeamOwner and $isPlayerAlreadyExists) {
            if($data['squad_type'] != 'MAIN'){
                $this->teamQuery->resetTeamCaptainOrWicketKeeper('id', $data['team_id'], 'captain_id', $data['player_id']);
                $this->teamQuery->resetTeamCaptainOrWicketKeeper('id', $data['team_id'], 'wicket_keeper_id', $data['player_id']);
            }

            $this->teamQuery->updateTeamSquadQuery($data['team_id'], $data['player_id'], $data['squad_type']);
            $res['status_code'] = 200;
            if ($data['squad_type'] == 'BENCH') {
                $res['message'] = "Player removed from squad successfully.";
            } else {
                $res['message'] = "Player added to Squad successfully.";
            }

            return $res;
        }

        return $res;
    }
//  ======================================== Team Squads end ===========================================================

    public function getTeamTossInsights($teamId, $data)
    {
        return $this->teamQuery->getTeamTossInsightsQuery($teamId, $data);
    }

    public function getTeamOverallInsights($teamId, $data)
    {
        return $this->teamQuery->getTeamOverallInsightsQuery($teamId, $data);
    }
}
