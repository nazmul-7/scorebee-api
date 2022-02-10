<?php

namespace App\Http\Controllers\Filter;

use Illuminate\Support\Facades\Auth;
use phpDocumentor\Reflection\Types\Collection;

class FilterService
{
    private $filterQuery;
    public function __construct(FilterQuery $filterQuery)
    {
        $this->filterQuery = $filterQuery;
    }

    // ======================================== Player-filtering-start ==========================

    public function playerFilteringFromFixture($data){
        return $this->filterQuery->playerFilteringFromFixture($data);
    }
    public function playerFilteringFromTournament($data){
        return $this->filterQuery->playerFilteringFromTournament($data);
    }
    public function playerFilteringFromTeam($data){
        return $this->filterQuery->playerFilteringFromTeam($data);
    }

    // ======================================== Player-filtering-end ==========================


    // ======================================== Tournament-filter-start ==========================
    public function tournamentYears($data){
        return $this->filterQuery->tournamentYears($data);
    }
    // ======================================== Tournament-filter-start ==========================


    // ======================================== LeaderBoard-filter-start ==========================
    public function filteringFromFixture($data){
        $data['uid'] = null ;
        $data['user_type'] = null ;
        if (Auth::guard('sanctum')->check()) {
            $user = Auth::guard('sanctum')->user();
            $data['uid'] = $user->id;
            $data['user_type'] = $user->registration_type;
        }
        return $this->filterQuery->filteringFromFixture($data);
    }

    public function filteringFromTournaments($data){
        $data['uid'] = null ;
        $data['user_type'] = null ;
        if (Auth::guard('sanctum')->check()) {
            $user = Auth::guard('sanctum')->user();
            $data['uid'] = $user->id;
            $data['user_type'] = $user->registration_type;
        }
        return $this->filterQuery->filteringFromTournaments($data);
    }

    public function filteringFromTeams($data){

        $data['uid'] = null ;
        $data['user_type'] = null ;
        
        if (Auth::guard('sanctum')->check()) {
            $user = Auth::guard('sanctum')->user();
            $data['uid'] = $user->id;
            $data['user_type'] = $user->registration_type;
        }

        return $this->filterQuery->filteringFromTeams($data);
    }

    // ======================================== LeaderBoard-filter-start ==========================


}
