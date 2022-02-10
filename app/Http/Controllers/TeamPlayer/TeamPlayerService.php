<?php

namespace App\Http\Controllers\TeamPlayer;

class TeamPlayerService
{
    private $teamPlayerQuery;

    public function __construct(TeamPlayerQuery $teamPlayerQuery)
    {
        $this->teamPlayerQuery = $teamPlayerQuery;
    }

    //    Player to team or team to player request start
    public function sendPlayerRequest($data){
        $checkStatus = $this->teamPlayerQuery->checkPlayerRequestStatus($data);

        if(!$checkStatus){
            $authUser = auth()->user()->id;

            $obj = [
                'team_id' => $data['team_id'],
                'player_id' => $data['player_id'],
                'requested_by' =>  $data['player_id'],
            ];

            return $this->teamPlayerQuery->sendPlayerRequestQuery($obj);
        }
    }

    public function acceptPlayerRequest($data){

    }

    public function cancelPlayerRequest($data){

    }
    //    Player to team or team to player request end

}
