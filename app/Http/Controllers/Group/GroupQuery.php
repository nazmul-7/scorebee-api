<?php

namespace App\Http\Controllers\Group;
use App\Models\TournamentTeam;
use App\Models\LeagueGroup;

class GroupQuery
{
    //Group-start
    public function getGroupsByTournamentQuery($tId){
        // collection order round type
        return LeagueGroup::where('tournament_id', $tId)->get();
        
     }
    //Group-end


    //Tournament-start
    
    public function getTourTeamListQuery($id){
        return TournamentTeam::where('tournament_id',$id)->where('status','ACCEPTED')->with('team:id,team_name,logo')->get();
    }
    
    public function updateTournamentsQuery($tId, $ob){
        return Tournament::where('id', $tId)->update($ob);
    }
        
    public function deleteTournamentQuery($tId, $uId){
        $t = Tournament::where('id', $tId)->where('group_id', $uId)->delete();
        if($t){
            return [
                'msg' => 'Tournament Deleted Successfully !'
            ];
        }
    }
    //Tournament-end
    
}
