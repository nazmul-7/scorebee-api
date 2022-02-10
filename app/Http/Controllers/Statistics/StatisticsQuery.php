<?php

namespace App\Http\Controllers\Statistics;

use App\Models\Inning;
use App\Models\Fixture;
use App\Models\Delivery;
use App\Models\Over;
use App\Models\InningBatterResult;
use App\Models\InningBowlerResult;
use App\Models\Notification;


use App\Models\User;
use DB;

class StatisticsQuery
{

    public function playerRunVsPercentageStathQuery($id)
    {
        return User::where('id', $id)->with('batting_by_deliveries')->first();
    }
    public function getUserById($id)
    {
        return User::where('id', $id)->select('id', 'first_name', 'last_name', 'batting_style', 'bowling_style', 'playing_role', 'profile_pic', 'gender')->first();
    }
    public function getInningBatterResultQuery($id, $value = null)
    {
        return InningBatterResult::select('id', 'batter_id', 'inning_id', 'team_id', 'runs_achieved', 'fixture_id')
            ->where('batter_id', $id)
            ->whereHas('fixers')
            ->when($value == 30, function($q){
                $q->whereBetween('runs_achieved', [30, 49]);
            })
            ->when($value == 50, function($q){
                $q->whereBetween('runs_achieved', [50, 99]);
            })
            ->when($value == 100, function($q){
                $q->where('runs_achieved', '>', 100);
            })
            ->count();
    }
    public function getInningsWithDeleverQuery($id)
    {
        return InningBatterResult::where('batter_id', $id)->select('id', 'batter_id', 'inning_id', 'team_id', 'runs_achieved', 'fixture_id', 'position', 'overs_faced', 'balls_faced')
            // ->with(['delevery' => function($q) use ($id){
            //         $q->where('batter_id', $id);
            //     }])
            ->get();
    }
    public function getPlayerRunsByEveryDeleveryQuery($id)
    {
        return Delivery::where('batter_id', $id)->select('id', 'batter_id', 'bowler_id', 'runs','run_type','boundary_type')
        ->where(function ($q) {
            $q
            ->where('ball_type', '=', 'LEGAL')
            ->orWhere('ball_type', '=', 'NB');
        })
        ->whereNull('run_type')
        ->orderBy('id', 'desc')
        ->limit(150)->get();
    }

    public function getPlayerRunsByEveryDeleveryV2Query($id)
    {
        return Delivery::where('batter_id', $id)
        ->where('ball_type', '!=', 'IB')
        ->whereNull('run_type')
        ->select('id', 'batter_id', 'bowler_id', 'runs','run_type','boundary_type')
        ->get();
    }
    public function playerOverWithDeleveryQuery($id)
    {
        return Over::join('deliveries', 'overs.id', '=', 'deliveries.over_id')
            ->where('overs.bowler_id', $id)->select('overs.id', 'overs.bowler_id', 'overs.over_number', 'runs', 'overs.inning_id', 'deliveries.wicket_by')
            ->orderBy('overs.id', 'desc')->get();
    }
    public function playerOverWithLegalDeleveryQuery($id)
    {
        return Over::with(['oversDelivery' => function ($q) use ($id) {
            $q
                // ->where('ball_type','!=' ,'NB')
                // ->where('ball_type','!=' ,'NB')
                ->select('id', 'ball_type', 'over_id', 'runs', 'bowler_id', 'boundary_type', 'wicket_by', 'wicket_type', 'run_type')
                ->orderBy('id', 'asc');
        }])
            ->where('bowler_id', $id)
            ->get();
    }

    public function bowlerDeliveries($bowlerId){
        return Delivery::where('bowler_id', $bowlerId)
        ->where('ball_type', '!=', 'DB')
        ->get();
    }

    public function playerOverWithLegalDeleveryWithBatterQuery($id)
    {
        return Delivery::join('users', 'deliveries.batter_id', '=', 'users.id')
            ->where('deliveries.bowler_id', $id)
            ->select('deliveries.id', 'bowler_id', 'batter_id', 'runs', 'inning_id', 'wicket_by', 'users.batting_style')
            ->get();
    }
    public function getFirstOrSecondInnings($id, $type)
    {
        return InningBowlerResult::join('innings', 'inning_bowler_results.inning_id', '=', 'innings.id')->where('innings.is_first_innings', $type)->where('inning_bowler_results.bowler_id', $id)->select('wickets', 'bowler_id', 'inning_bowler_results.id', 'inning_id')->get();
    }
    public function batting_position_wise_wicketQuery($id)
    {
        return Delivery::join('inning_batter_results', 'deliveries.batter_id', '=', 'inning_batter_results.batter_id')
            ->where('deliveries.bowler_id', $id)
            ->whereNotNull('deliveries.wicket_by')
            ->select('deliveries.id', 'bowler_id', 'deliveries.wicket_by', 'inning_batter_results.position', 'deliveries.inning_id')
            ->get();
    }

    public function getInningsbyBollwerId($id)
    {
        return InningBowlerResult::where('bowler_id', $id)
            ->select('id', 'bowler_id', 'wickets')
            ->get();
    }
    public function getMatchesByBowllerId($id, $type)
    {
        if ($type == 1) {
            return InningBowlerResult::join('fixtures', 'inning_bowler_results.fixture_id', '=', 'fixtures.id')
                ->where('inning_bowler_results.bowler_id', $id)
                ->where(function ($query) {
                    $query->whereNotNull('fixtures.match_winner_team_id');
                })
                ->select('inning_bowler_results.id', 'bowler_id', 'inning_bowler_results.team_id', 'wickets', 'match_winner_team_id', 'match_loser_team_id')
                ->get();
        }
        return InningBowlerResult::join('fixtures', 'inning_bowler_results.fixture_id', '=', 'fixtures.id')
            ->where('inning_bowler_results.bowler_id', $id)
            ->where(function ($query) {
                $query->whereNotNull('fixtures.match_loser_team_id');
            })
            ->select('inning_bowler_results.id', 'bowler_id', 'inning_bowler_results.team_id', 'wickets', 'match_winner_team_id', 'match_loser_team_id')
            ->get();
    }

    public function getBowlerDeliveriesStatisticsQuery($bowlerId){
        return Delivery::select(DB::raw(
            "COUNT(id) AS total_deliveries,
            SUM(CASE WHEN ball_type = 'LEGAL' AND runs = 0 AND extras = 0 THEN 1 ELSE 0 END) AS total_dots,
            SUM(CASE WHEN run_type IS NULL AND runs = 1 THEN 1 ELSE 0 END) AS total_ones,
            SUM(CASE WHEN run_type IS NULL AND runs = 2 THEN 1 ELSE 0 END) AS total_twos,
            SUM(CASE WHEN run_type IS NULL AND runs = 3 THEN 1 ELSE 0 END) AS total_threes,
            SUM(CASE WHEN run_type IS NULL AND runs = 4 THEN 1 ELSE 0 END) AS total_fours,
            SUM(CASE WHEN run_type IS NULL AND runs = 6 THEN 1 ELSE 0 END) AS total_sixes,
            SUM(CASE WHEN run_type IS NULL THEN (runs + extras) ELSE 0 END) AS total_runs,
            SUM(CASE WHEN run_type IS NULL AND boundary_type IS NOT NULL THEN runs ELSE 0 END) AS total_boundary_runs"
        ))
        ->where('bowler_id', $bowlerId)
        ->first();


    }
    public function getBowllerEveryDeleveryQuery($id, $type, $boundary_type, $isSumOrCount)
    {
        $q = Delivery::where('bowler_id', $id)->select('id', 'bowler_id', 'runs', 'boundary_type', 'ball_type')
            ->where('ball_type', '!=', 'WD')
            ->where('ball_type', '!=', 'DB');
        if ($type > -1) {
            $q->where('runs', $type);
        }
        if ($boundary_type == 6) {
            $q->where(function ($query) {
                $query->where('boundary_type', 'SIX');
                $query->where('boundary_type', 'FOUR');
            });
        }
        if ($isSumOrCount == 'sum') {
            return $q->sum('runs');
        } else if ($isSumOrCount == 'sum_extra') {
            $q->where('ball_type', 'NB');
        }

        return $q->count();
        // return $q->get();

    }
    public function getBowllerEveryDeleveryByTypeQuery($id, $type)
    {
        $q = Delivery::where('bowler_id', $id)->select('id', 'bowler_id', 'runs', 'boundary_type', 'ball_type', 'over_id')
            ->where('bowler_id', $id);
        if ($type == 'any') {
            return $q->distinct()
                ->count('over_id');
        } else {
            $q->where('ball_type', $type);
        }
        return $q->count();
    }
}
