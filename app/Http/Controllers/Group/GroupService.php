<?php

namespace App\Http\Controllers\Group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class GroupService
{
    private $groupQuery;

    public function __construct(GroupQuery $groupQuery)
    {
        $this->groupQuery = $groupQuery;
    }



    //Group-start

    public function getGroupsByTournament($tId){
        return $this->groupQuery->getGroupsByTournamentQuery($tId);
        // $grouped = $allcollections;
        // $grouped = $allcollections->groupBy('round_type');
    //    return $grouped->all();
    }
    //Group-end


    //Tournamenst-start

    public function getTourTeamList($id){
        return $this->groupQuery->getTourTeamListQuery($id);
    }

    public function updateTournaments($data){
        $data['group_id'] = Auth::id();

        $tId = $data['tournament_id'];
        unset($data['tournament_id']);

        return $this->groupQuery->updateTournamentsQuery($tId, $data);
    }

    public function deleteTournament($data){
        $tId = $data['tournament_id'];
        return $this->groupQuery->deleteTournamentQuery($tId, Auth::id());
    }
    //Tournamenst-end




}
