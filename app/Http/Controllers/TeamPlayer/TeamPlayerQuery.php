<?php

namespace App\Http\Controllers\TeamPlayer;

use Illuminate\Support\Facades\DB;

class TeamPlayerQuery
{

    // Player to team or team to player request start
    public function checkPlayerRequestStatus($data){
        return DB::table('team_players')->where('team_id', $data['team_id'])->where('player_id', $data['player_id'])->first();
    }

    public function sendPlayerRequestQuery($obj){

    }
//    public function getPlayerStatus($data){
//        return Team::where('team_id', $data['team_id'])->where('tournament_id', $data['tournament_id'])->first();
//    }
//
//    public function acceptRequest($id, $uid, $obj){
//        return Team::where('id', $id)->where('tournament_owner_id', $uid)->update($obj);
//    }
//
//    public function cancelRequest($id, $uid){
//        return Team::where('id', $id)->where('tournament_owner_id', $uid)->delete();
//    }
//
//    public function checkSinglePlayerStatus($id){
//        return Team::where('id', $id)->first();
//    }
    // Player to team or team to player request end
}
