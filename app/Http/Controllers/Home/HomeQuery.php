<?php

namespace App\Http\Controllers\Home;


use App\Models\Fixture;
use App\Models\Tournament;
use Carbon\Carbon;

class HomeQuery
{
    public function getMatchesListQuery($data)
    {
        $isMatchStart = $data['is_match_start'] ?? null;
        $isMatchFinished = $data['is_match_finished'] ?? 0;
        $itemNumbers = $data['item_numbers'] ?? 10;
        $lastMatchDate = $data['last_match_date'] ?? null;

        if ($lastMatchDate){
            $lastMatchDate = date('Y-m-d', strtotime($lastMatchDate));
        }

        return Fixture::select(
            'id', 'fixture_name', 'tournament_id',
            'round_type', 'match_overs',
            'match_date', 'start_time',
            'home_team_id', 'away_team_id',
            'tournament_id', 'league_group_id',
            'toss_winner_team_id', 'team_elected_to',
            'is_match_start', 'toss_winner_team_id', 'team_elected_to',
            'is_match_finished', 'match_winner_team_id', 'match_final_result',
            'home_team_runs', 'home_team_overs', 'home_team_wickets',
            'away_team_runs', 'away_team_overs', 'away_team_wickets',
        )
            ->when($lastMatchDate, function ($query) use ($lastMatchDate) {
                $query->whereDate('match_date', '<', $lastMatchDate);
            })
            ->when($isMatchStart != null, function ($query) use ($isMatchStart) {
                $query->where('is_match_start', $isMatchStart);
            })
            ->when($isMatchFinished != null, function ($query) use ($isMatchFinished) {
                $query->where('is_match_finished', $isMatchFinished);
            })
            ->with('tournament:id,tournament_name')
            ->with('leagueGroup:id,league_group_name')
            ->with('homeTeam:id,team_name,team_logo')
            ->with('awayTeam:id,team_name,team_logo')
            ->limit($itemNumbers)
            ->orderByDesc('is_match_start')
            ->orderByDesc('match_date')
            ->orderByDesc('match_date')
            ->get();
    }


    public function getTournamentsListQuery($data)
    {
        $isStart = $data['is_start'] ?? null;
        $isFinished = $data['is_finished'] ?? 0;
        $itemNumbers = $data['item_numbers'] ?? 10;
        $lastDate = $data['last_date'] ?? null;

        if ($lastDate){
            $lastDate = date('Y-m-d', strtotime($lastDate));
        }

        return Tournament::select(
            'id', 'tournament_name', 'tournament_banner', 'city',
            'match_type', 'start_date', 'end_date',
            'is_start', 'is_finished',
        )
            ->when($isStart != null, function ($query) use ($isStart) {
                $query->where('is_start', $isStart);
            })
            ->when($isFinished != null, function ($query) use ($isFinished) {
                $query->where('is_finished', $isFinished);
            })
            ->when(!$isFinished, function ($query) use ($lastDate) {
                $query
                    ->when($lastDate, function ($query) use($lastDate){
                        $query->where('start_date', '<', $lastDate);
                    })
                    ->orderByDesc('start_date');
            })
            ->when($isFinished, function ($query) use ($lastDate) {
                $query
                    ->when($lastDate, function ($query) use($lastDate){
                        $query->where('end_date', '<', $lastDate);
                    })
                    ->orderByDesc('end_date');
            })
            ->limit($itemNumbers)
            ->orderByDesc('is_start')
            ->get();
    }


}
