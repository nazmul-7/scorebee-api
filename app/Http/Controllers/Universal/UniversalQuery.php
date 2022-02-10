<?php

namespace App\Http\Controllers\Universal;


use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UniversalQuery
{
    public function getUsersSearchResultsQuery($data, $type)
    {
        $term = "%{$data['term']}%";
        $limit = $data['limit'] ?? 10;

        return User::select(
            "id AS user_id", \DB::raw("CONCAT(first_name, ' ', last_name) AS name"),
            'profile_pic AS logo', 'city', 'registration_type AS type')
            ->addSelect(\DB::raw("'' AS owner_id"))
            ->where('registration_type', $type)
            ->where(function ($q) use($term){
                $q
                    ->where(DB::raw('CONCAT(first_name, " ", last_name)'), 'LIKE', $term)
                    ->orwhere(DB::raw('CONCAT(last_name, " ", first_name)'), 'LIKE', $term)
                    ->orWhere('username', 'LIKE', $term)
                    ->orWhere('email', 'LIKE', $term)
                    ->orWhere('phone', 'LIKE', $term)
                    ->orWhere('city', 'LIKE', $term);
            })
            ->limit($limit)
            ->get();

    }

    public function getTeamsSearchResultsQuery($data)
    {
        $term = "%{$data['term']}%";
        $limit = $data['limit'] ?? 10;

        return Team::select('id', 'team_name AS name', 'team_logo as logo', 'city', 'owner_id')
            ->addSelect(\DB::raw("'TEAM' AS type"))
            ->where('team_name', 'LIKE', $term)
            ->orWhere('team_short_name', 'LIKE', $term)
            ->orWhere('team_unique_name', 'LIKE', $term)
            ->orWhere('city', 'LIKE', $term)
            ->limit($limit)
            ->get();
    }

    public function getTournamentsSearchResultsQuery($data)
    {
        $term = "%{$data['term']}%";
        $limit = $data['limit'] ?? 10;

        return Tournament::select('id', 'tournament_name as name', 'tournament_logo AS logo', 'city', 'organizer_id AS owner_id')
            ->addSelect(\DB::raw("'TOURNAMENT' AS type"))
            ->where('tournament_name', 'LIKE', $term)
            ->orWhere('organizer_name', 'LIKE', $term)
            ->orWhere('organizer_phone', 'LIKE', $term)
            ->orWhere('tournament_category', 'LIKE', $term)
            ->orWhere('tournament_type', 'LIKE', $term)
            ->orWhere('city', 'LIKE', $term)
            ->limit($limit)
            ->get();
    }

}
