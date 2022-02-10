<?php

namespace App\Http\Controllers\Player;

use App\Http\Resources\UserResource;
use App\Models\AwardLike;
use App\Models\ClubPlayer;
use App\Models\Delivery;
use App\Models\Fixture;
use App\Models\Inning;
use App\Models\InningBatterResult;
use App\Models\InningBowlerResult;
use App\Models\Over;
use App\Models\PlayingEleven;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Log;

class PlayerQuery
{


    public function updateUserInfoQuery($id, $obj)
    {
        $user = User::where('id', $id)->update($obj);
        if($user){
          $u = User::where('id', $id)->first();
          $u->social_accounts = json_decode($u->social_accounts);
          return $u;
        }
    }

    public function updateUser($column, $value, $obj){
        return User::where($column, $value)->update($obj);
    }

    public function checkVerifyCode($data){
        $email = isset($data['email']) ? $data['email'] : '';
        $code = isset($data['verify_code']) ? $data['verify_code'] : '';
        return User::where('email', $email)->where('forgot_code', $code)->first();
    }

    public function singleUser($column, $value){
        return User::where($column, $value)->first();
    }

    //    Club to player requests start

    public function getPendingRequestsNumber($playerId){
        return ClubPlayer::where('player_id', $playerId)->where('status', 'PENDING')->count();
    }

    public function getClubRequestsListQuery($data)
    {
        $playerId = $data['player_id'];
        $status = $data['status'];

        return User::select('users.id as club_owner_id', DB::raw("CONCAT(first_name, ' ', last_name) AS name"), 'profile_pic', 'city','requested_by', 'status')
            ->join('club_players', 'users.id', '=', 'club_players.club_owner_id')
            ->where('club_players.player_id', $playerId)
            ->where('club_players.status', $status)
            ->get();
    }

    //    Club to player requests end

    public function playerBattingStatsQuery($data)
    {
        $lmt = 'LIMITED OVERS';
        $test = 'TEST MATCH';
        $id = $data['player_id'];
        $year = $data['year'] ?? null ;
        $inning = $data['inning'] ?? null ;
        $ball_type = $data['ball_type'] ?? null ;
        $over = $data['over'] ?? null ;
        $tournament = $data['tournament'] ?? null ;
        $category = $data['category'] ?? null ;
        $team = $data['team'] ?? null ;

        $raw = "EXISTS(SELECT * FROM fixtures as F WHERE fixture_id = F.id AND team_id";

        $inningsBatter = InningBatterResult::selectRaw("
            COUNT(DISTINCT CASE WHEN match_type = '$lmt' THEN id END) AS limited_match_innings,
            COUNT(DISTINCT CASE WHEN match_type = '$test' THEN id END) AS test_match_innings,
            COUNT(DISTINCT CASE WHEN (match_type = '$lmt' AND is_out = 0) THEN id END) AS not_out_in_limited,
            COUNT(DISTINCT CASE WHEN (match_type = '$test' AND is_out = 0) THEN id END) AS not_out_in_test,
            COUNT(DISTINCT CASE WHEN (match_type = '$lmt' AND runs_achieved = 0 AND is_out = 1) THEN id END) AS ducks_in_limited,
            COUNT(DISTINCT CASE WHEN (match_type = '$test' AND runs_achieved = 0 AND is_out = 1) THEN id END) AS ducks_in_test,
            COUNT(DISTINCT CASE WHEN (match_type = '$lmt' AND runs_achieved > 99) THEN id END) AS limited_match_hundred,
            COUNT(DISTINCT CASE WHEN (match_type = '$test' AND runs_achieved > 99) THEN id END) AS test_match_hundred,
            COUNT(DISTINCT CASE WHEN (match_type = '$lmt' AND runs_achieved BETWEEN 50 AND 99) THEN id END) AS limited_match_fifty,
            COUNT(DISTINCT CASE WHEN (match_type = '$test' AND runs_achieved BETWEEN 50 AND 99) THEN id END) AS test_match_fifty,
            COUNT(DISTINCT CASE WHEN (match_type = '$lmt' AND runs_achieved BETWEEN 30 AND 49) THEN id END) AS limited_match_thirty,
            COUNT(DISTINCT CASE WHEN (match_type = '$test' AND runs_achieved BETWEEN 30 AND 49) THEN id END) AS test_match_thirty,
            COUNT(DISTINCT CASE WHEN (match_type = '$lmt' AND is_out = 1) THEN id END) AS be_out_in_limited,
            COUNT(DISTINCT CASE WHEN (match_type = '$test' AND is_out = 1) THEN id END) AS be_out_in_test,
            MAX(CASE WHEN match_type = '$lmt' THEN runs_achieved ELSE 0 END) AS highest_in_limited,
            MAX(CASE WHEN match_type = '$test' THEN runs_achieved ELSE 0 END) AS highest_in_test,
            SUM(CASE WHEN match_type = '$lmt' THEN balls_faced ELSE 0 END)  as faced_limited_ball,
            SUM(CASE WHEN match_type = '$test' THEN balls_faced ELSE 0 END)  as faced_test_ball,
            SUM(CASE WHEN match_type = '$lmt' THEN runs_achieved ELSE 0 END)  as limited_match_run,
            SUM(CASE WHEN match_type = '$test' THEN runs_achieved ELSE 0 END)  as test_match_run,
            SUM(CASE WHEN match_type = '$lmt' THEN sixes ELSE 0 END)  as total_limited_match_sixes,
            SUM(CASE WHEN match_type = '$test' THEN sixes ELSE 0 END)  as total_test_sixes,
            SUM(CASE WHEN match_type = '$lmt' THEN fours ELSE 0 END)  as total_limited_match_fours,
            SUM(CASE WHEN match_type = '$test' THEN fours ELSE 0 END)  as total_test_fours,
            COUNT(CASE WHEN (match_type = '$lmt' AND $raw = F.match_winner_team_id)) THEN 1 END) AS limited_match_won,
            COUNT(CASE WHEN (match_type = '$lmt' AND $raw = F.match_loser_team_id)) THEN 1 END) AS limited_match_loss,
            COUNT(CASE WHEN (match_type = '$test' AND $raw = F.match_winner_team_id)) THEN 1 END) AS test_match_won,
            COUNT(CASE WHEN (match_type = '$test' AND $raw = F.match_loser_team_id)) THEN 1 END) AS test_match_loss
            ")
          ->when($year, function($q) use ($year){
            $q->whereRaw("Year(created_at) = $year");
          })
          ->when($inning, function($q) use ($inning){
            $q->where('match_type', $inning);
          })
          ->when($ball_type, function($q) use ($ball_type){
             $q->whereIn('fixture_id', function ($query) use ($ball_type) {
                 $query->select('id')->from('fixtures')
                 ->where('ball_type', $ball_type);
             });
          })
          ->when($over, function($q) use ($over){
             $q->whereIn('fixture_id', function ($query) use ($over) {
                 $query->select('id')->from('fixtures')
                 ->where('match_overs', $over);
             });
          })
          ->when($tournament, function($q) use ($tournament){
             $q->where('tournament_id', $tournament);
          })
          ->when($category, function($q) use ($category){
             $q->whereIn('tournament_id', function ($query) use ($category) {
                 $query->select('id')->from('tournaments')
                 ->where('tournament_category', $category);
             });
          })
          ->when($team, function($q) use ($team){
            $q->where('team_id', $team);
          })

          ->groupBy('batter_id')
          ->where('batter_id', $id)
          ->first();

            $PE = PlayingEleven::selectRaw("
                COUNT(DISTINCT CASE WHEN (match_type = '$lmt' AND is_played = 1) THEN id END) AS limited_match,
                COUNT(DISTINCT CASE WHEN (match_type = '$test' AND is_played = 1) THEN id END) AS test_match
            ")
            ->when($year, function($q) use ($year){
                $q->whereIn('fixture_id', function ($query) use ($year) {
                    $query->selectRaw('id')->from('fixtures as F')
                    ->whereRaw("Year(F.match_date) = $year");
                });
             })
             ->when($inning, function($q) use ($inning){
               $q->where('match_type', $inning);
             })
             ->when($ball_type, function($q) use ($ball_type){
                $q->whereIn('fixture_id', function ($query) use ($ball_type) {
                    $query->select('id')->from('fixtures')
                    ->where('ball_type', $ball_type);
                });
             })
             ->when($over, function($q) use ($over){
                $q->whereIn('fixture_id', function ($query) use ($over) {
                    $query->select('id')->from('fixtures')
                    ->where('match_overs', $over);
                });
             })
             ->when($tournament, function($q) use ($tournament){
                $q->whereIn('fixture_id', function ($query) use ($tournament) {
                    $query->select('id')->from('fixtures')
                    ->where('tournament_id', $tournament);
                });
             })
             ->when($category, function($q) use ($category){
                $q->whereIn('fixture_id', function ($query) use ($category) {
                    $query->select('id')->from('fixtures')
                    ->whereIn('tournament_id', function ($query2) use ($category) {
                        $query2->select('id')->from('tournaments')
                        ->where('tournament_category', $category);
                    });
                });
             })
             ->when($team, function($q) use ($team){
               $q->where('team_id', $team);
             })
            ->where('player_id', $id)
            ->groupBy('player_id')
            ->first();

            if($inningsBatter){
                $inningsBatter->highest_in_limited = $inningsBatter->highest_in_limited ? $inningsBatter->highest_in_limited : null;
                $inningsBatter->highest_in_test = $inningsBatter->highest_in_test ? $inningsBatter->highest_in_test : null;
                $inningsBatter->faced_limited_ball = $inningsBatter->faced_limited_ball ? $inningsBatter->faced_limited_ball : null;
                $inningsBatter->faced_test_ball = $inningsBatter->faced_test_ball ? $inningsBatter->faced_test_ball : null;
                $inningsBatter->limited_match_run = $inningsBatter->limited_match_run ? $inningsBatter->limited_match_run : null;
                $inningsBatter->test_match_run = $inningsBatter->test_match_run ? $inningsBatter->test_match_run : null;
                $inningsBatter->total_limited_match_sixes = $inningsBatter->total_limited_match_sixes ? $inningsBatter->total_limited_match_sixes : null;
                $inningsBatter->total_test_sixes = $inningsBatter->total_test_sixes ? $inningsBatter->total_test_sixes : null;
                $inningsBatter->total_limited_match_fours = $inningsBatter->total_limited_match_fours ? $inningsBatter->total_limited_match_fours : null;
                $inningsBatter->total_test_fours = $inningsBatter->totaltesth_fours ? $inningsBatter->total_test_fours : null;
            }

            $alt = [
                "limited_match_innings" => 0,
                "test_match_innings" => 0,
                "not_out_in_limited" => 0,
                "not_out_in_test" => 0,
                "ducks_in_limited" => 0,
                "ducks_in_test" => 0,
                "limited_match_hundred" => 0,
                "test_match_hundred" => 0,
                "limited_match_fifty" => 0,
                "test_match_fifty" => 0,
                "limited_match_thirty" => 0,
                "test_match_thirty" => 0,
                "be_out_in_limited" => 0,
                "be_out_in_test" => 0,
                "highest_in_limited" => null,
                "highest_in_test" => null,
                "faced_limited_ball" => null,
                "faced_test_ball" => null,
                "limited_match_run" => null,
                "test_match_run" => null,
                "total_limited_match_sixes" => null,
                "total_test_sixes" => null,
                "total_limited_match_fours" => null,
                "total_test_fours" => null,
                "limited_match_won" => 0,
                "limited_match_loss" => 0,
                "test_match_won" => 0,
                "test_match_loss" => 0,
            ];

            $alt2 = [
                "limited_match" => 0,
                "test_match" => 0,
            ];

            if(!$inningsBatter){
                $inningsBatter = $alt;
            }
            if(!$PE){
                $PE = $alt2;
            }

            return collect($inningsBatter)->merge($PE);



    }

    public function playerBowlingStatsQuery($data)
    {

        $id = $data['player_id'];
        $lmt = 'LIMITED OVERS';
        $test = 'TEST MATCH';
        $year = $data['year'] ?? null ;
        $inning = $data['inning'] ?? null ;
        $ball_type = $data['ball_type'] ?? null ;
        $over = $data['over'] ?? null ;
        $tournament = $data['tournament'] ?? null ;
        $category = $data['category'] ?? null ;
        $team = $data['team'] ?? null ;

        $inningBowler = InningBowlerResult::where('bowler_id', $id)
        ->selectRaw("
            COUNT(DISTINCT CASE WHEN match_type = '$lmt' THEN id END) AS limited_match_innings,
            COUNT(DISTINCT CASE WHEN match_type = '$test' THEN id END) AS test_match_innings,
            COUNT(DISTINCT CASE WHEN (match_type = '$lmt' AND wickets = 3) THEN id END) AS three_wickets_in_limited,
            COUNT(DISTINCT CASE WHEN (match_type = '$test' AND wickets = 3) THEN id END) AS three_wickets_in_test,
            COUNT(DISTINCT CASE WHEN (match_type = '$lmt' AND wickets = 5) THEN id END) AS five_wickets_in_limited,
            COUNT(DISTINCT CASE WHEN (match_type = '$test' AND wickets = 5) THEN id END) AS five_wickets_in_test,
            SUM(CASE WHEN match_type = '$lmt' THEN balls_bowled END)  as total_deliveries_in_limited,
            SUM(CASE WHEN match_type = '$test' THEN balls_bowled END)  as total_deliveries_in_test,
            SUM(CASE WHEN match_type = '$lmt' THEN maiden_overs END)  as maiden_in_limited,
            SUM(CASE WHEN match_type = '$test' THEN maiden_overs END)  as maiden_in_test,
            SUM(CASE WHEN match_type = '$lmt' THEN wickets END)  as wicket_in_limited_match,
            SUM(CASE WHEN match_type = '$test' THEN wickets END)  as wicket_in_test,
            SUM(CASE WHEN match_type = '$lmt' THEN runs_gave END)  as run_gave_in_limited_match,
            SUM(CASE WHEN match_type = '$test' THEN runs_gave END)  as run_gave_in_test,
            SUM(CASE WHEN match_type = '$lmt' THEN wide_balls END)  as wides_in_limited,
            SUM(CASE WHEN match_type = '$test' THEN wide_balls END)  as wides_in_test,
            SUM(CASE WHEN match_type = '$lmt' THEN no_balls END)  as no_balls_in_limited,
            SUM(CASE WHEN match_type = '$test' THEN no_balls END)  as no_balls_in_test,

            SUM(CASE WHEN match_type = '$lmt' THEN floor(overs_bowled) ELSE 0 END)  as overs_bowled_in_limited_only,
            SUM(CASE WHEN match_type = '$lmt' THEN ((overs_bowled - floor(overs_bowled))*10) ELSE 0  END) as overs_extra_balls_in_limited,
            SUM(CASE WHEN match_type = '$test' THEN floor(overs_bowled) ELSE 0 END)  as overs_bowled_in_test_only,
            SUM(CASE WHEN match_type = '$test' THEN ((overs_bowled - floor(overs_bowled))*10) ELSE 0  END) as overs_extra_balls_in_test,

            MAX(CASE WHEN match_type = '$lmt' THEN wickets END) AS highest_wicket_in_limited,
            MIN(CASE WHEN (match_type = '$lmt' AND wickets = (SELECT MAX(`wickets`) FROM inning_bowler_results WHERE bowler_id = $id AND match_type = '$lmt') ) THEN runs_gave ELSE null END) AS highest_run_in_limited,
            MAX(CASE WHEN match_type = '$test' THEN wickets ELSE null END) AS highest_wicket_in_test,
            MIN(CASE WHEN (match_type = '$test' AND wickets = (SELECT MAX(`wickets`) FROM inning_bowler_results WHERE bowler_id = $id AND match_type = '$test') ) THEN runs_gave END) AS highest_run_in_test,

            (SELECT COUNT(D.bowler_id)
            FROM deliveries AS D
            WHERE D.bowler_id = inning_bowler_results.bowler_id AND inning_bowler_results.match_type = '$lmt' AND D.ball_type = 'LEGAL' AND D.runs = 0 AND D.extras = 0) as dot_in_limited_match,

            (SELECT COUNT(D.bowler_id)
            FROM deliveries AS D
            WHERE D.bowler_id = inning_bowler_results.bowler_id AND inning_bowler_results.match_type = '$test' AND D.ball_type = 'LEGAL' AND D.runs = 0 AND D.extras = 0) as dot_in_test_match,

            (SELECT COUNT(D.bowler_id)
            FROM deliveries AS D
            WHERE D.bowler_id = inning_bowler_results.bowler_id AND inning_bowler_results.match_type = '$lmt' AND D.boundary_type = 'SIX') as total_limited_match_sixes,

            (SELECT COUNT(D.bowler_id)
            FROM deliveries AS D
            WHERE D.bowler_id = inning_bowler_results.bowler_id AND inning_bowler_results.match_type = '$test' AND D.boundary_type = 'SIX') as total_test_sixes,

            (SELECT COUNT(D.bowler_id)
            FROM deliveries AS D
            WHERE D.bowler_id = inning_bowler_results.bowler_id AND inning_bowler_results.match_type = '$lmt' AND D.boundary_type = 'FOUR') as total_limited_match_fours,

            (SELECT COUNT(D.bowler_id)
            FROM deliveries AS D
            WHERE D.bowler_id = inning_bowler_results.bowler_id AND inning_bowler_results.match_type = '$test' AND D.boundary_type = 'FOUR') as total_test_fours

        ")
        ->when($year, function($q) use ($year){
            $q->whereRaw("Year(created_at) = $year");
          })
          ->when($inning, function($q) use ($inning){
            $q->where('match_type', $inning);
          })
          ->when($ball_type, function($q) use ($ball_type){
             $q->whereIn('fixture_id', function ($query) use ($ball_type) {
                 $query->select('id')->from('fixtures')
                 ->where('ball_type', $ball_type);
             });
          })
          ->when($over, function($q) use ($over){
             $q->whereIn('fixture_id', function ($query) use ($over) {
                 $query->select('id')->from('fixtures')
                 ->where('match_overs', $over);
             });
          })
          ->when($tournament, function($q) use ($tournament){
             $q->where('tournament_id', $tournament);
          })
          ->when($category, function($q) use ($category){
             $q->whereIn('tournament_id', function ($query) use ($category) {
                 $query->select('id')->from('tournaments')
                 ->where('tournament_category', $category);
             });
          })
          ->when($team, function($q) use ($team){
            $q->where('team_id', $team);
          })
        ->groupBy('bowler_id')->first();

        if($inningBowler){
            $inningBowler->maiden_in_limited = $inningBowler->maiden_in_limited ? $inningBowler->maiden_in_limited : null;
            $inningBowler->maiden_in_test = $inningBowler->maiden_in_test ? $inningBowler->maiden_in_test : null;
            $inningBowler->wicket_in_test = $inningBowler->wicket_in_test ? $inningBowler->wicket_in_test : null;
            $inningBowler->wides_in_limited = $inningBowler->wides_in_limited ? $inningBowler->wides_in_limited : null;
            $inningBowler->wides_in_test = $inningBowler->wides_in_test ? $inningBowler->wides_in_test : null;
            $inningBowler->no_balls_in_limited = $inningBowler->no_balls_in_limited ? $inningBowler->no_balls_in_limited : null;
            $inningBowler->no_balls_in_test = $inningBowler->no_balls_in_test ? $inningBowler->no_balls_in_test : null;
            $inningBowler->highest_run_in_limited = $inningBowler->highest_run_in_limited ? $inningBowler->highest_run_in_limited : null;
            $inningBowler->highest_wicket_in_test = $inningBowler->highest_wicket_in_test ? $inningBowler->highest_wicket_in_test : null;
            $inningBowler->wicket_in_limited_match = $inningBowler->wicket_in_limited_match ? $inningBowler->wicket_in_limited_match : null;
        }



            $PE = PlayingEleven::selectRaw("
                COUNT(DISTINCT CASE WHEN (match_type = '$lmt' AND is_played = 1) THEN id END) AS limited_match,
                COUNT(DISTINCT CASE WHEN (match_type = '$test' AND is_played = 1) THEN id END) AS test_match
            ")
            ->when($year, function($q) use ($year){
                $q->whereIn('fixture_id', function ($query) use ($year) {
                    $query->selectRaw('id')->from('fixtures as F')
                    ->whereRaw("Year(F.match_date) = $year");
                });
             })
             ->when($inning, function($q) use ($inning){
               $q->where('match_type', $inning);
             })
             ->when($ball_type, function($q) use ($ball_type){
                $q->whereIn('fixture_id', function ($query) use ($ball_type) {
                    $query->select('id')->from('fixtures')
                    ->where('ball_type', $ball_type);
                });
             })
             ->when($over, function($q) use ($over){
                $q->whereIn('fixture_id', function ($query) use ($over) {
                    $query->select('id')->from('fixtures')
                    ->where('match_overs', $over);
                });
             })
             ->when($tournament, function($q) use ($tournament){
                $q->whereIn('fixture_id', function ($query) use ($tournament) {
                    $query->select('id')->from('fixtures')
                    ->where('tournament_id', $tournament);
                });
             })
             ->when($category, function($q) use ($category){
                $q->whereIn('fixture_id', function ($query) use ($category) {
                    $query->select('id')->from('fixtures')
                    ->whereIn('tournament_id', function ($query2) use ($category) {
                        $query2->select('id')->from('tournaments')
                        ->where('tournament_category', $category);
                    });
                });
             })
             ->when($team, function($q) use ($team){
               $q->where('team_id', $team);
             })
            ->where('player_id', $id)
            ->groupBy('player_id')
            ->first();

        $alt = [
            "limited_match_innings" => 0,
            "test_match_innings" => 0,
            "three_wickets_in_limited" => 0,
            "three_wickets_in_test" => 0,
            "five_wickets_in_limited" => 0,
            "five_wickets_in_test" => 0,
            "total_deliveries_in_limited" => null,
            "total_deliveries_in_test" => null,
            "maiden_in_limited" => null,
            "maiden_in_test" => null,
            "wicket_in_limited_match" => null,
            "wicket_in_test" => null,
            "run_gave_in_limited_match" => null,
            "run_gave_in_test" => null,
            "wides_in_limited" => null,
            "wides_in_test" => null,
            "no_balls_in_limited" => null,
            "no_balls_in_test" => null,
            "dot_in_limited_match" => 0,
            "dot_in_test_match" => 0,
            "total_limited_match_sixes" => 0,
            "total_test_sixes" => 0,
            "total_limited_match_fours" => 0,
            "total_test_fours" => 0,
            "overs_bowled_in_limited_only" => 0,
            "overs_extra_balls_in_limited" => 0,
            "overs_bowled_in_test_only" => 0,
            "overs_extra_balls_in_test" => 0,
            "highest_wicket_in_limited" => 0,
            "highest_run_in_limited" => null,
            "highest_wicket_in_test" => null,
            "highest_run_in_test" => null
        ];

        $alt2 = [
            "limited_match" => 0,
            "test_match" => 0
        ];

        if(!$inningBowler){
            $inningBowler = $alt;
        }
        if(!$PE){
            $PE = $alt2;
        }

        return collect($inningBowler)->merge($PE);
        // return (object) array_merge((array) $inningBowler, (array) $PE);


    }

    public function playerFieldingStatsQuery($data)
    {

        $id = $data['player_id'];
        $lmt = 'LIMITED OVERS';
        $test = 'TEST MATCH';
        $year = $data['year'] ?? null ;
        $inning = $data['inning'] ?? null ;
        $ball_type = $data['ball_type'] ?? null ;
        $over = $data['over'] ?? null ;
        $tournament = $data['tournament'] ?? null ;
        $category = $data['category'] ?? null ;
        $team = $data['team'] ?? null ;

                $delivery = Delivery::selectRaw("
                    COUNT(DISTINCT CASE WHEN (match_type = '$lmt' AND caught_by = $id) THEN id END) AS catch_by_in_limited,
                    COUNT(DISTINCT CASE WHEN (match_type = '$test' AND caught_by = $id) THEN id END) AS catch_by_in_test,
                    COUNT(DISTINCT CASE WHEN (match_type = '$lmt' AND assist_by = $id) THEN id END) AS assist_in_limited,
                    COUNT(DISTINCT CASE WHEN (match_type = '$test' AND assist_by = $id) THEN id END) AS assist_in_test,
                    COUNT(DISTINCT CASE WHEN (match_type = '$lmt' AND stumped_by = $id) THEN id END) AS stumped_in_limited,
                    COUNT(DISTINCT CASE WHEN (match_type = '$test' AND stumped_by = $id) THEN id END) AS stumped_in_test,
                    COUNT(DISTINCT CASE WHEN (match_type = '$lmt' AND run_out_by = $id) THEN id END) AS run_out_in_limited,
                    COUNT(DISTINCT CASE WHEN (match_type = '$test' AND run_out_by = $id) THEN id END) AS run_out_in_test
                ")
                ->when($year, function($q) use ($year){
                    $q->whereRaw("Year(created_at) = $year");
                  })
                  ->when($inning, function($q) use ($inning){
                    $q->where('match_type', $inning);
                  })
                  ->when($ball_type, function($q) use ($ball_type){
                     $q->whereIn('fixture_id', function ($query) use ($ball_type) {
                         $query->select('id')->from('fixtures')
                         ->where('ball_type', $ball_type);
                     });
                  })
                  ->when($over, function($q) use ($over){
                     $q->whereIn('fixture_id', function ($query) use ($over) {
                         $query->select('id')->from('fixtures')
                         ->where('match_overs', $over);
                     });
                  })
                  ->when($tournament, function($q) use ($tournament){
                     $q->where('tournament_id', $tournament);
                  })
                  ->when($category, function($q) use ($category){
                     $q->whereIn('tournament_id', function ($query) use ($category) {
                         $query->select('id')->from('tournaments')
                         ->where('tournament_category', $category);
                     });
                  })
                  ->when($team, function($q) use ($team, $id){
                    $q->whereIn('fixture_id', function ($query) use ($team, $id) {
                        $query->select('fixture_id')->from('playing_elevens')
                        ->where('player_id', $id)
                        ->where('team_id', $team);
                    });
                  })

                ->first();

            $PE = PlayingEleven::selectRaw("
              COUNT(DISTINCT CASE WHEN (match_type = '$lmt' AND is_played = 1) THEN id END) AS fielding_in_limited,
              COUNT(DISTINCT CASE WHEN (match_type = '$test' AND is_played = 1) THEN id END) AS fielding_in_test
            ")
            ->when($year, function($q) use ($year){
                $q->whereIn('fixture_id', function ($query) use ($year) {
                    $query->selectRaw('id')->from('fixtures as F')
                    ->whereRaw("Year(F.match_date) = $year");
                });
             })
             ->when($inning, function($q) use ($inning){
               $q->where('match_type', $inning);
             })
             ->when($ball_type, function($q) use ($ball_type){
                $q->whereIn('fixture_id', function ($query) use ($ball_type) {
                    $query->select('id')->from('fixtures')
                    ->where('ball_type', $ball_type);
                });
             })
             ->when($over, function($q) use ($over){
                $q->whereIn('fixture_id', function ($query) use ($over) {
                    $query->select('id')->from('fixtures')
                    ->where('match_overs', $over);
                });
             })
             ->when($tournament, function($q) use ($tournament){
                $q->whereIn('fixture_id', function ($query) use ($tournament) {
                    $query->select('id')->from('fixtures')
                    ->where('tournament_id', $tournament);
                });
             })
             ->when($category, function($q) use ($category){
                $q->whereIn('fixture_id', function ($query) use ($category) {
                    $query->select('id')->from('fixtures')
                    ->whereIn('tournament_id', function ($query2) use ($category) {
                        $query2->select('id')->from('tournaments')
                        ->where('tournament_category', $category);
                    });
                });
             })
             ->when($team, function($q) use ($team){
               $q->where('team_id', $team);
             })
            ->where('player_id', $id)
            ->groupBy('player_id')
            ->first();

            $delAlt = [
                "catch_by_in_limited" => 0,
                "catch_by_in_test" => 0,
                "assist_in_limited" => 0,
                "assist_in_test" => 0,
                "stumped_in_limited" => 0,
                "stumped_in_test" => 0,
                "run_out_in_limited" => 0,
                "run_out_in_test" => 0
            ];
            $peAlt = [
                "fielding_in_limited" => 0,
                "fielding_in_test" => 0
            ];

            if(!$delivery){
                $delivery = $delAlt;
            }
            if(!$PE){
                $PE = $peAlt;
            }
          return collect($delivery)->merge($PE);

    }

    public function playerCaptainStatsQuery($data)
    {

        $id = $data['player_id'];
        $lmt = 'LIMITED OVERS';
        $test = 'TEST MATCH';
        $year = $data['year'] ?? null ;
        $inning = $data['inning'] ?? null ;
        $ball_type = $data['ball_type'] ?? null ;
        $over = $data['over'] ?? null ;
        $tournament = $data['tournament'] ?? null ;
        $category = $data['category'] ?? null ;
        $team = $data['team'] ?? null ;

        $PE = PlayingEleven::selectRaw("
            COUNT(DISTINCT CASE WHEN (match_type = '$lmt' AND is_played = 1 AND is_captain = 1) THEN id END) AS played_limited_as_captain,
            COUNT(DISTINCT CASE WHEN (match_type = '$test' AND is_played = 1 AND is_captain = 1) THEN id END) AS played_test_as_captain,
            COUNT(DISTINCT CASE WHEN (match_type = '$lmt' AND is_played = 1 AND is_captain = 1 AND
            EXISTS( SELECT * FROM fixtures as F WHERE fixture_id = F.id AND team_id = F.toss_winner_team_id) ) THEN id END) AS toss_win_in_limited,
            COUNT(DISTINCT CASE WHEN (match_type = '$test' AND is_played = 1 AND is_captain = 1 AND
            EXISTS( SELECT * FROM fixtures as F WHERE fixture_id = F.id AND team_id = F.toss_winner_team_id) ) THEN id END) AS toss_win_in_test
        ")
        ->when($year, function($q) use ($year){
            $q->whereIn('fixture_id', function ($query) use ($year) {
                $query->selectRaw('id')->from('fixtures as F')
                ->whereRaw("Year(F.match_date) = $year");
            });
        })
        ->when($inning, function($q) use ($inning){
            $q->where('match_type', $inning);
        })
        ->when($ball_type, function($q) use ($ball_type){
            $q->whereIn('fixture_id', function ($query) use ($ball_type) {
                $query->select('id')->from('fixtures')
                ->where('ball_type', $ball_type);
            });
        })
        ->when($over, function($q) use ($over){
            $q->whereIn('fixture_id', function ($query) use ($over) {
                $query->select('id')->from('fixtures')
                ->where('match_overs', $over);
            });
        })
        ->when($tournament, function($q) use ($tournament){
            $q->whereIn('fixture_id', function ($query) use ($tournament) {
                $query->select('id')->from('fixtures')
                ->where('tournament_id', $tournament);
            });
        })
        ->when($category, function($q) use ($category){
            $q->whereIn('fixture_id', function ($query) use ($category) {
                $query->select('id')->from('fixtures')
                ->whereIn('tournament_id', function ($query2) use ($category) {
                    $query2->select('id')->from('tournaments')
                    ->where('tournament_category', $category);
                });
            });
        })
        ->when($team, function($q) use ($team){
            $q->where('team_id', $team);
        })
        ->where('player_id', $id)
        ->groupBy('player_id')
        ->first();

        $alt =[
        "played_limited_as_captain" => 0,
        "played_test_as_captain" => 0,
        "toss_win_in_limited" => 0,
        "toss_win_in_test" => 0
        ];
        if(!$PE){
            return $alt;
        }
        return $PE;

    }

    public function singlePlayerDetailsQuery($id)
    {
        return User::where('id', $id)->first();
    }

    public function highestBattingByRun($data)
    {
        $year = $data['year'] ?? null ;
        $inning = $data['inning'] ?? null ;
        $ball_type = $data['ball_type'] ?? null ;
        $over = $data['over'] ?? null ;
        $tournament = $data['tournament'] ?? null ;
        $category = $data['category'] ?? null ;
        $team = $data['team'] ?? null ;

        return User::where('registration_type', 'PLAYER')->select('id', 'first_name', 'last_name', 'profile_pic', 'username')
            ->withSum(['inningsbatter as player_runs' => function($q) use($year, $inning, $ball_type, $over, $tournament, $category, $team){
                $q->filterBattingLeaderBoard($year, $inning, $ball_type, $over, $tournament, $category, $team);
            }], 'runs_achieved')
            ->withSum(['inningsbatter as total_balls_faced' => function($q) use($year, $inning, $ball_type, $over, $tournament, $category, $team) {
                $q->filterBattingLeaderBoard($year, $inning, $ball_type, $over, $tournament, $category, $team);
            }], 'balls_faced')
            ->withAvg(['inningsbatter as player_average' => function($q) use($year, $inning, $ball_type, $over, $tournament, $category, $team) {
                $q->filterBattingLeaderBoard($year, $inning, $ball_type, $over, $tournament, $category, $team);
            }], 'runs_achieved')
            ->withCount(['inningsbatter as player_innings'=> function($q) use($year, $inning, $ball_type, $over, $tournament, $category, $team) {
                $q->filterBattingLeaderBoard($year, $inning, $ball_type, $over, $tournament, $category, $team);
            }])

                    //Year
            ->when($year, function($q) use ($year){
                $q->whereIn('id', function ($query) use ($year) {
                    $query->select('batter_id')->from('inning_batter_results as IB')
                    ->whereRaw("Year(IB.created_at) = $year");
                });
             })

                //Innings
             ->when($inning, function($q) use ($inning){
                $q->whereIn('id', function ($query) use ($inning) {
                    $query->select('batter_id')->from('inning_batter_results')
                    ->where('match_type', $inning);
                });
              })
                    //Ball types
            ->when($ball_type, function($q) use ($ball_type){
                $q->whereIn('id', function ($query) use ($ball_type) {
                    $query->select('batter_id')->from('inning_batter_results')
                    ->whereIn('fixture_id', function ($query) use ($ball_type) {
                        $query->select('id')->from('fixtures')
                        ->where('ball_type', $ball_type);
                    });
                });
            })

                    //Overs
             ->when($over, function($q) use ($over){
                $q->whereIn('id', function ($query) use ($over) {
                    $query->select('batter_id')->from('inning_batter_results')
                    ->whereIn('fixture_id', function ($query) use ($over) {
                        $query->select('id')->from('fixtures')
                        ->where('match_overs', $over);
                    });
                });
             })

                    //Tournaments
            ->when($tournament, function($q) use ($tournament){
                $q->whereIn('id', function ($q2) use ($tournament) {
                    $q2->select('batter_id')->from('inning_batter_results')
                    ->where('tournament_id', $tournament);
                });
             })

                    //Category
            ->when($category, function($q) use ($category){
                $q->whereIn('id', function ($q2) use ($category) {
                    $q2->select('batter_id')->from('inning_batter_results')
                    ->whereIn('tournament_id', function ($query2) use ($category) {
                        $query2->select('id')->from('tournaments')
                        ->where('tournament_category', $category);
                     });
                });
             })

                //Team
            ->when($team, function($q) use ($team){
                $q->whereIn('id', function ($q2) use ($team) {
                    $q2->select('batter_id')->from('inning_batter_results')
                    ->where('team_id', $team);
                });
             })

            ->orderByDesc('player_runs')->paginate(15);
    }

    public function highestBowlingByRun($data)
    {
        $year = $data['year'] ?? null ;
        $inning = $data['inning'] ?? null ;
        $ball_type = $data['ball_type'] ?? null ;
        $over = $data['over'] ?? null ;
        $tournament = $data['tournament'] ?? null ;
        $category = $data['category'] ?? null ;
        $team = $data['team'] ?? null ;

        return User::where('registration_type', 'PLAYER')->select('id', 'first_name', 'last_name', 'profile_pic', 'username')
            ->withSum(['inningsBowler as player_wickets' => function($q) use ($category, $year, $inning, $over, $ball_type, $team, $tournament){
                $q->filterBowlingLeaderBoard($category, $year, $inning, $over, $ball_type, $team, $tournament);
            }], 'wickets')
            ->withCount([
                'inningsBowler as overs_bowled' => function ($q) use ($category, $year, $inning, $over, $ball_type, $team, $tournament) {
                    $q->select(DB::raw('sum(floor(overs_bowled))'));
                    $q->filterBowlingLeaderBoard($category, $year, $inning, $over, $ball_type, $team, $tournament);
                },
                'inningsBowler as overs_extra_balls' => function ($q) use ($category, $year, $inning, $over, $ball_type, $team, $tournament) {
                    $q->select(DB::raw('sum(  (overs_bowled - floor(overs_bowled))*10  )'));
                    $q->filterBowlingLeaderBoard($category, $year, $inning, $over, $ball_type, $team, $tournament);
                }
            ])
            ->withSum(['inningsBowler as runs_gave' => function($q) use ($category, $year, $inning, $over, $ball_type, $team, $tournament){
                $q->filterBowlingLeaderBoard($category, $year, $inning, $over, $ball_type, $team, $tournament);
            }], 'runs_gave')
            ->withCount(['inningsBowler as player_innings' => function($q) use ($category, $year, $inning, $over, $ball_type, $team, $tournament){
                $q->filterBowlingLeaderBoard($category, $year, $inning, $over, $ball_type, $team, $tournament);
            }])
                        //Year
            ->when($year, function($q) use ($year){
                $q->whereIn('id', function ($query) use ($year) {
                    $query->select('IB.bowler_id')->from('inning_bowler_results as IB')
                    ->whereRaw("Year(IB.created_at) = $year");
                });
            })

                        //Tournament
            ->when($tournament, function($q) use ($tournament){
                $q->whereIn('id', function ($query) use ($tournament) {
                    $query->select('bowler_id')->from('inning_bowler_results')
                    ->where('tournament_id', $tournament);
                });
            })

                        //Category
            ->when($category, function($q) use ($category){
                $q->whereIn('id', function ($q2) use ($category) {
                    $q2->select('bowler_id')->from('inning_bowler_results')
                    ->whereIn('tournament_id', function ($query2) use ($category) {
                        $query2->select('id')->from('tournaments')
                        ->where('tournament_category', $category);
                     });
                });
            })
                        //Ball Type
            ->when($ball_type, function($q) use ($ball_type){
                $q->whereIn('id', function ($query) use ($ball_type) {
                    $query->select('bowler_id')->from('inning_bowler_results')
                    ->whereIn('fixture_id', function ($query) use ($ball_type) {
                        $query->select('id')->from('fixtures')
                        ->where('ball_type', $ball_type);
                    });
                });
            })

                    //Overs
            ->when($over, function($q) use ($over){
                $q->whereIn('id', function ($query) use ($over) {
                    $query->select('bowler_id')->from('inning_bowler_results')
                    ->whereIn('fixture_id', function ($query) use ($over) {
                        $query->select('id')->from('fixtures')
                        ->where('match_overs', $over);
                    });
                });
            })
                    //Team
            ->when($team, function($q) use ($team){
                $q->whereIn('id', function ($query) use ($team) {
                    $query->select('bowler_id')->from('inning_bowler_results')
                    ->where('team_id', $team);
                });
            })

            ->orderByDesc('player_wickets')
            ->orderBy('runs_gave', 'asc')
            ->paginate(15);
    }

    public function highestFieldingByDismissal($data)
    {
        return User::where('registration_type', 'PLAYER')->select('id', 'first_name', 'last_name', 'profile_pic', 'username')
            ->withCount([
                'deliveries as player_dismissals' => function ($q) {
                    $q->has('stumpBy')->orHas('runOutBy');
                },
                'playerElevens as total_matches' => function ($q) {
                    $q->where('is_played', 1);
                },
                'caughtBy as total_caughts',
                'stumpedBy as total_stumpings',
            ])
            ->orderByDesc('player_dismissals')
            ->orderBy('total_caughts', 'desc')
            ->paginate(15);
    }


    //Player-batting-insights-start
    public function playerCurrentForm($data)
    {
        $id = $data['player_id'];
        return PlayingEleven::where('player_id', $id)->where('is_played', 1)->has('innings_batter')
            ->with(['fixture' => function ($q) {
                $q->select('id', 'home_team_id', 'away_team_id', 'match_date');
                $q->with('home_team');
                $q->with('away_team');
            }])
            ->with(['innings_batter' => function ($q) use ($id) {
                $q->where('batter_id', $id);
                $q->select('id', 'fixture_id', 'overs_faced', 'runs_achieved', 'balls_faced', 'is_out', 'wicket_type', 'fours', 'sixes', 'batter_id');
            }])
            ->limit(5)
            ->orderByDesc('id')
            ->get(['id', 'fixture_id', 'team_id', 'player_id', 'is_played']);
    }

    public function battingWagon($data){
        $id = isset($data['player_id']) ?$data['player_id'] :0;
        $status = isset($data['status']) ? $data['status'] : '';

        $batter = Delivery::whereNotNull('deep_position')
        ->whereNotNull('shot_x')
        ->whereNotNull('shot_y')
        ->select('id', 'shot_position', 'deep_position', 'shot_x', 'shot_y', 'wicket_type', 'boundary_type', 'ball_type', 'runs', 'extras')
        ->where(function($q) use($id) {
            $q->where('batter_id', $id);
            $q->whereNull('run_type');
            $q->whereNotIn('ball_type', ['DB', 'WD']);
        });

            if($status && $status == "OUT"){
                $batter->whereNotNull('wicket_type');
            }
            if($status && $status == "SIX"){
                $batter->where('boundary_type', '=', 'SIX');
            }
            if($status && $status == "FOUR"){
                $batter->where('boundary_type', '=', 'FOUR');
            }
            if($status && $status == "DOT"){
                $batter->where('ball_type', '=', 'LEGAL')->where('runs', 0)->where('extras', 0);
            }
            if($status && $status == "ONE"){
                $batter->where('runs', 1);
            }
            if($status && $status == "TWO"){
                $batter->where('runs', 2);
            }
            if($status && $status == "THREE"){
                $batter->where('runs', 3);
            }
        return $batter->get();
    }

    public function testingWagon($data){

        $id = isset($data['player_id']) ?$data['player_id'] :0;
        $status = isset($data['status']) ? $data['status'] : '';
        $user = User::find($id);

        $batter = Delivery::leftjoin('field_coordinates', 'deliveries.shot_position', '=', 'field_coordinates.name')
        ->select('deliveries.id', 'shot_position', 'position', 'shot_x', 'shot_y')
        ->where('batter_id', $id)
        ->where('batsman_type', $user->batting_style)
        ->where('ball_type', '!=', 'DB')->where('ball_type','!=', 'WD');
            if($status && $status == "OUT"){
                $batter->whereNotNull('wicket_type');
            }
            if($status && $status == "SIX"){
                $batter->where('boundary_type', '=', 'SIX');
            }
            if($status && $status == "FOUR"){
                $batter->where('boundary_type', '=', 'FOUR');
            }
            if($status && $status == "DOT"){
                $batter->where('ball_type', '=', 'LEGAL')->where('runs', 0)->where('extras', 0);
            }
            if($status && $status == "ONE"){
                $batter->where('runs', 1);
            }
            if($status && $status == "TWO"){
                $batter->where('runs', 2);
            }
            if($status && $status == "THREE"){
                $batter->where('runs', 3);
            }
        return $batter->get();
    }

    public function batterTotals($data){
        $id = isset($data['player_id']) ?$data['player_id'] :0;
        $status = isset($data['status']) ?$data['status'] : null;
        $totals = Delivery::whereNotNull('deep_position')
        ->whereNotNull('shot_x')
        ->whereNotNull('shot_y')
        ->where(function($q) use($id) {
            $q->where('batter_id', $id);
            $q->whereNull('run_type');
            $q->whereNotIn('ball_type', ['DB', 'WD']);
        });
        if($status && $status == "OUT"){
            $totals->where('wicket_type', '!=', null);
        }
        if($status && $status == "SIX"){
            $totals->where('boundary_type', '=', 'SIX');
        }
        if($status && $status == "FOUR"){
            $totals->where('boundary_type', '=', 'FOUR');
        }
        if($status && $status == "DOT"){
            $totals->where('ball_type', '=', 'LEGAL')->where('runs', 0)->where('extras', 0);
        }
        if($status && $status == "ONE"){
            $totals->where('runs', 1);
        }
        if($status && $status == "TWO"){
            $totals->where('runs', 2);
        }
        if($status && $status == "THREE"){
            $totals->where('runs', 3);
        }
        return $totals->count();
    }

    public function bowlingWagon($data){
        $id = isset($data['player_id']) ?$data['player_id'] :0;
        $status = isset($data['status']) ? $data['status'] : '';

        $bowler = Delivery::select('id', 'shot_position', 'shot_x', 'shot_y', 'wicket_by', 'deep_position', 'wicket_type', 'boundary_type', 'ball_type', 'runs', 'extras')
        ->whereNotNull('deep_position')
        ->whereNotNull('shot_x')
        ->whereNotNull('shot_y')
        ->where(function($q) use($id){
            $q->where('bowler_id', $id);
            $q->whereNull('run_type');
            $q->whereNotNull('deep_position');
            $q->whereNotIn('ball_type', ['DB', 'WD']);
        });
            if($status && $status == "OUT"){
                $bowler->whereNotNull('wicket_type')->where('wicket_by', $id);
            }
            if($status && $status == "SIX"){
                $bowler->where('boundary_type', '=', 'SIX');
            }
            if($status && $status == "FOUR"){
                $bowler->where('boundary_type', '=', 'FOUR');
            }
            if($status && $status == "DOT"){
                $bowler->where('ball_type', '=', 'LEGAL')->where('runs', 0)->where('extras', 0);
            }
            if($status && $status == "ONE"){
                $bowler->where('runs', 1);
            }
            if($status && $status == "TWO"){
                $bowler->where('runs', 2);
            }
            if($status && $status == "THREE"){
                $bowler->where('runs', 3);
            }
        return $bowler->get();
    }


    public function bowlingTotals($data){
        $id = isset($data['player_id']) ?$data['player_id'] :0;
        $status = isset($data['status']) ?$data['status'] : '';
        $totals = Delivery::whereNotNull('deep_position')
        ->whereNotNull('shot_x')
        ->whereNotNull('shot_y')
        ->where(function($q) use($id){
            $q->where('bowler_id', $id);
            $q->whereNull('run_type');
            $q->whereNotIn('ball_type', ['DB', 'WD']);
        });
        if($status && $status == "OUT"){
            $totals->where('wicket_type', '!=', null);
        }
        if($status && $status == "SIX"){
            $totals->where('boundary_type', '=', 'SIX');
        }
        if($status && $status == "FOUR"){
            $totals->where('boundary_type', '=', 'FOUR');
        }
        if($status && $status == "DOT"){
            $totals->where('ball_type', '=', 'LEGAL')->where('runs', 0)->where('extras', 0);
        }
        if($status && $status == "ONE"){
            $totals->where('runs', 1);
        }
        if($status && $status == "TWO"){
            $totals->where('runs', 2);
        }
        if($status && $status == "THREE"){
            $totals->where('runs', 3);
        }
        return $totals->count();
    }

    public function faceOffWagon($data){
        $batterId = $data['player_id'];
        $bowlerId = $data['face_off_player_id'];
        $status = $data['status'] ?? null;
        $type = $data['type'] ?? null;

        if($type == 'Bowler'){
            $batterId = $data['face_off_player_id'];
            $bowlerId = $data['player_id'];
        }

        return Delivery::select('id', 'deep_position', 'shot_position', 'runs', 'ball_type', 'boundary_type', 'wicket_type', 'shot_x', 'shot_y')
            ->where('batter_id', $batterId)
            ->where('bowler_id', $bowlerId)
            ->whereNotNull('deep_position')
            ->whereNotNull('shot_x')
            ->whereNotNull('shot_y')
            ->when($status == 'OUT', function($q) {
                $q->whereNotNull('wicket_type');
            })
            ->when($status == 'SIX', function($q) {
                $q->where('boundary_type', 'SIX');
            })
            ->when($status == 'FOUR', function($q) {
                $q->where('boundary_type', 'FOUR');
            })
            ->when($status == 'DOT', function($q) {
                $q
                ->where(function($q){
                    $q->where('ball_type', 'LEGAL')->orWhere('ball_type', 'NB');
                })
                ->where('runs', 0)->where('extras', 0);
            })
            ->when($status == 'ONE', function($q) {
                $q
                ->where(function($q){
                    $q->where('ball_type', 'LEGAL')->orWhere('ball_type', 'NB');
                })
                ->where('runs', 1);
            })
            ->when($status == 'TWO', function($q) {
                $q
                ->where(function($q){
                    $q->where('ball_type', 'LEGAL')->orWhere('ball_type', 'NB');
                })
                ->where('runs', 2);
            })
            ->when($status == 'THREE', function($q) {
                $q
                ->where(function($q){
                    $q->where('ball_type', 'LEGAL')->orWhere('ball_type', 'NB');
                })
                ->where('runs', 3);
            })
            ->get();
    }

    public function battingsAvgByPosition($data){
        $id = isset($data['player_id']) ? $data['player_id'] :0;
        $player = User::where('id', $id)
        ->select('id')
        ->withCount([
          'batting as total_runs_against_RAF' =>function($q){
            $q->select(DB::raw('sum(runs)'));
            $q->whereHas('bowler', function (Builder $query) {
                $query->where('bowling_style', '=', 'RAF');
            });
          },
          'batting as total_runs_against_RAM' =>function($q){
            $q->select(DB::raw('sum(runs)'));
            $q->whereHas('bowler', function (Builder $query) {
                $query->where('bowling_style', '=', 'RAM');
            });
          },
          'batting as total_runs_against_LAF' =>function($q){
            $q->select(DB::raw('sum(runs)'));
            $q->whereHas('bowler', function (Builder $query) {
                $query->where('bowling_style', '=', 'LAF');
            });
          },
          'batting as total_runs_against_LAM' =>function($q){
            $q->select(DB::raw('sum(runs)'));
            $q->whereHas('bowler', function (Builder $query) {
                $query->where('bowling_style', '=', 'LAM');
            });
          },
          'batting as total_runs_against_SLAC' =>function($q){
            $q->select(DB::raw('sum(runs)'));
            $q->whereHas('bowler', function (Builder $query) {
                $query->where('bowling_style', '=', 'SLAC');
            });
          },
          'batting as total_runs_against_SLAO' =>function($q){
            $q->select(DB::raw('sum(runs)'));
            $q->whereHas('bowler', function (Builder $query) {
                $query->where('bowling_style', '=', 'SLAO');
            });
          },
          'batting as total_runs_against_RAOB' =>function($q){
            $q->select(DB::raw('sum(runs)'));
            $q->whereHas('bowler', function (Builder $query) {
                $query->where('bowling_style', '=', 'RAOB');
            });
          },
          'batting as total_runs_against_RALB' =>function($q){
            $q->select(DB::raw('sum(runs)'));
            $q->whereHas('bowler', function (Builder $query) {
                $query->where('bowling_style', '=', 'RALB');
            });
          },
          'batting as total_runs_against_OTHERS' =>function($q){
            $q->select(DB::raw('sum(runs)'));
            $q->whereHas('bowler', function (Builder $query) {
                $query->where('bowling_style', '=', 'OTHERS');
            });
          },

          //balls-against
          'batting as total_balls_against_RAF' =>function($q){
            $q->whereHas('bowler', function (Builder $query) {
                $query->where('bowling_style', '=', 'RAF');
            });
          },
          'batting as total_balls_against_RAM' =>function($q){
            $q->whereHas('bowler', function (Builder $query) {
                $query->where('bowling_style', '=', 'RAM');
            });
          },
          'batting as total_balls_against_LAF' =>function($q){
            $q->whereHas('bowler', function (Builder $query) {
                $query->where('bowling_style', '=', 'LAF');
            });
          },
          'batting as total_balls_against_LAM' =>function($q){
            $q->whereHas('bowler', function (Builder $query) {
                $query->where('bowling_style', '=', 'LAM');
            });
          },
          'batting as total_balls_against_SLAC' =>function($q){
            $q->whereHas('bowler', function (Builder $query) {
                $query->where('bowling_style', '=', 'SLAC');
            });
          },
          'batting as total_balls_against_SLAO' =>function($q){
            $q->whereHas('bowler', function (Builder $query) {
                $query->where('bowling_style', '=', 'SLAO');
            });
          },
          'batting as total_balls_against_RAOB' =>function($q){
            $q->whereHas('bowler', function (Builder $query) {
                $query->where('bowling_style', '=', 'RAOB');
            });
          },
          'batting as total_balls_against_RALB' =>function($q){
            $q->whereHas('bowler', function (Builder $query) {
                $query->where('bowling_style', '=', 'RALB');
            });
          },
          'batting as total_balls_against_OTHERS' =>function($q){
            $q->whereHas('bowler', function (Builder $query) {
                $query->where('bowling_style', '=', 'OTHERS');
            });
          },

          //wickets-agains
          'batting as total_wickets_against_RAF' =>function($q){
            $q->where('wicket_type', '!=', null);
            $q->whereHas('bowler', function (Builder $query) {
                $query->where('bowling_style', '=', 'RAF');
            });
          },
          'batting as total_wickets_against_RAM' =>function($q){
            $q->where('wicket_type', '!=', null);
            $q->whereHas('bowler', function (Builder $query) {
                $query->where('bowling_style', '=', 'RAM');
            });
          },
          'batting as total_wickets_against_LAF' =>function($q){
            $q->where('wicket_type', '!=', null);
            $q->whereHas('bowler', function (Builder $query) {
                $query->where('bowling_style', '=', 'LAF');
            });
          },
          'batting as total_wickets_against_LAM' =>function($q){
            $q->where('wicket_type', '!=', null);
            $q->whereHas('bowler', function (Builder $query) {
                $query->where('bowling_style', '=', 'LAM');
            });
          },
          'batting as total_wickets_against_SLAC' =>function($q){
            $q->where('wicket_type', '!=', null);
            $q->whereHas('bowler', function (Builder $query) {
                $query->where('bowling_style', '=', 'SLAC');
            });
          },
          'batting as total_wickets_against_SLAO' =>function($q){
            $q->where('wicket_type', '!=', null);
            $q->whereHas('bowler', function (Builder $query) {
                $query->where('bowling_style', '=', 'SLAO');
            });
          },
          'batting as total_wickets_against_RAOB' =>function($q){
            $q->where('wicket_type', '!=', null);
            $q->whereHas('bowler', function (Builder $query) {
                $query->where('bowling_style', '=', 'RAOB');
            });
          },
          'batting as total_wickets_against_RALB' =>function($q){
            $q->where('wicket_type', '!=', null);
            $q->whereHas('bowler', function (Builder $query) {
                $query->where('bowling_style', '=', 'RALB');
            });
          },
          'batting as total_wickets_against_OTHERS' =>function($q){
            $q->where('wicket_type', '!=', null);
            $q->whereHas('bowler', function (Builder $query) {
                $query->where('bowling_style', '=', 'OTHERS');
            });
          },

        ])
       ->first();

       return $player;
    }

    public function faceOffTotals($data){
        $batterId = $data['player_id'];
        $bowlerId = $data['face_off_player_id'];
        $status = $data['status'] ?? null;
        $type = $data['type'] ?? null;

        if($type == 'Bowler'){
            $batterId = $data['face_off_player_id'];
            $bowlerId = $data['player_id'];
        }

        return Delivery::where('batter_id', $batterId)
            ->where('bowler_id', $bowlerId)
            ->whereNotNull('deep_position')
            ->whereNotNull('shot_x')
            ->whereNotNull('shot_y')
            ->when($status == 'OUT', function($q) {
                $q->whereNotNull('wicket_type');
            })
            ->when($status == 'SIX', function($q) {
                $q->where('boundary_type', 'SIX');
            })
            ->when($status == 'FOUR', function($q) {
                $q->where('boundary_type', 'FOUR');
            })
            ->when($status == 'DOT', function($q) {
                $q
                ->where(function($q){
                    $q->where('ball_type', 'LEGAL')->orWhere('ball_type', 'NB');
                })
                ->where('runs', 0)->where('extras', 0);
            })
            ->when($status == 'ONE', function($q) {
                $q
                ->where(function($q){
                    $q->where('ball_type', 'LEGAL')->orWhere('ball_type', 'NB');
                })
                ->where('runs', 1);
            })
            ->when($status == 'TWO', function($q) {
                $q
                ->where(function($q){
                    $q->where('ball_type', 'LEGAL')->orWhere('ball_type', 'NB');
                })
                ->where('runs', 2);
            })
            ->when($status == 'THREE', function($q) {
                $q
                ->where(function($q){
                    $q->where('ball_type', 'LEGAL')->orWhere('ball_type', 'NB');
                })
                ->where('runs', 3);
            })
            ->count();
    }



    public function playerCurrentBowlingFormAndInnings($data)
    {
        $limit = isset($data['limit']) ? $data['limit'] : '';
        $id = $data['player_id'];
        return PlayingEleven::where('player_id', $id)->where('is_played', 1)->has('innings_bowler')
            ->with(['fixture' => function ($q) {
                $q->select('id', 'home_team_id', 'away_team_id', 'match_date');
                $q->with('home_team');
                $q->with('away_team');
            }])
            ->with(['innings_bowler' => function ($q) use ($id) {
                $q->where('bowler_id', $id);
                $q->select('id', 'fixture_id', 'maiden_overs', 'bowler_id', 'balls_bowled', 'overs_bowled', 'runs_gave', 'wickets', 'inning_id');
                $q->with(['deliveries' => function($q) use ($id){
                    $q
                    ->select('inning_id', 'wicket_type')
                    ->where('wicket_by', $id);
                }]);
                $q->withCount([
                    'deliveries as LBW' => function ($q) use ($id) {
                        $q->where('bowler_id', $id);
                        $q->where('wicket_type', '=', 'LBW');
                    },
                    'deliveries as sixes' => function ($q) use ($id) {
                        $q->where('bowler_id', $id);
                        $q->where('boundary_type', '=', 'SIX');
                    },
                    'deliveries as fours' => function ($q) use ($id) {
                        $q->where('bowler_id', $id);
                        $q->where('boundary_type', '=', 'FOUR');
                    },
                    'deliveries as right_hand_wickets' => function ($q) use ($id) {
                        $q->where('wicket_by', $id);
                        $q->whereHas('batter', function (Builder $query) {
                            $query->where('batting_style', '=', 'RH');
                        });
                    },
                    'deliveries as left_hand_wickets' => function ($q) use ($id) {
                        $q->where('wicket_by', $id);
                        $q->whereHas('batter', function (Builder $query) {
                            $query->where('batting_style', '=', 'LH');
                        });
                    },
                ]);
            }])
            ->limit($limit)->orderByDesc('id')->get(['id', 'fixture_id', 'team_id', 'player_id', 'is_played']);
    }

    public function playerBattingComparison($data)
    {

        $id = $data['player_id'];

        $player = User::where('id', $id)->select('id', 'first_name', 'last_name', 'username')
            ->withCount([
                'playerElevens as total_matches' => function ($query) {
                    $query->where('is_played', 1);
                },
                'inningsbatter as total_innings',
                'inningsbatter as total_matches_out' => function ($query) {
                    $query->where('is_out', '=', 1);
                },
                'inningsbatter as total_matches_not_out' => function ($query) {
                    $query->where('is_out', '=', 0);
                },
                'inningsbatter as total_ducks' => function ($query) {
                    $query->where('runs_achieved', '=', 0)->where('is_out', '=', 1);
                },
                'inningsbatter as total_hundreds' => function ($query) {
                    $query->where('runs_achieved', '>', 99);
                },
                'inningsbatter as total_fifties' => function ($query) {
                    $query->whereBetween('runs_achieved', [50, 99]);
                },
                'inningsbatter as total_thirties' => function ($query) {
                    $query->whereBetween('runs_achieved', [30, 49]);
                }
            ])
            ->withMax(['inningsbatter as highest_score'], 'runs_achieved')
            // ->withAvg(['inningsbatter as avg_score'], 'runs_achieved')
            ->withSum(['inningsbatter as total_runs'], 'runs_achieved')
            ->withSum(['inningsbatter as balls_faced'], 'balls_faced')
            ->first();
        return $player;
    }

    public function playerBowlingComparison($data)
    {

        $id = $data['player_id'];
        $player = User::where('id', $id)->select('id', 'first_name', 'last_name', 'username')
            ->withCount([
                'playerElevens as total_matches' => function ($query) {
                    $query->where('is_played', 1);
                },
                'inningsBowler as total_innings',
            ])
            ->withSum(
                ['inningsBowler as total_deliveries'], 'balls_bowled'
            )
            ->withSum(
                ['inningsBowler as total_maidens'], 'maiden_overs'
            )
            ->withSum(
                ['inningsBowler as total_overs'], 'overs_bowled'
            )
            ->withSum(
                ['inningsBowler as total_wickets'], 'wickets'
            )
            ->withSum(
                ['inningsBowler as total_runs'], 'runs_gave'
            )
            ->withSum(
                ['inningsBowler as total_wides'], 'wide_balls'
            )
            ->withSum(
                ['inningsBowler as total_noballs'], 'no_balls'
            )
            ->with(['inningsBowler' => function ($q) use ($id) {
                $q->where('wickets', '=', DB::raw("(select max(`wickets`) from inning_bowler_results where bowler_id = $id)"))
                    ->select('id', 'bowler_id', 'runs_gave', 'wickets')->limit(1);
            }])
            ->first();
        return $player;
    }

    public function fieldingCompare($id)
    {
        $match = PlayingEleven::where('player_id', $id)->where('is_played', 1)->count();
        $caughtBy = Delivery::where('caught_by', $id)->count();
        $caughtBehindBy = Delivery::where('caught_by', $id)->where('wicket_type', '=', 'CAUGHT_BEHIND')->count();
        $stumpedBy = Delivery::where('stumped_by', $id)->count();
        $runOutBy = Delivery::where('run_out_by', $id)->count();
        $byes = Delivery::where('run_type', '=', 'B')->count();
        $ob = [
            "match" => $match,
            "caughtBy" => $caughtBy,
            "caughtBehindBy" => $caughtBehindBy,
            "stumpedBy" => $stumpedBy,
            "runOutBy" => $runOutBy,
            "byes" => $byes,
            "totals" => ($runOutBy  +$caughtBy +$caughtBehindBy + $stumpedBy + $byes),
        ];
        return ["player_fielding" => $ob];
    }

    public function bowlingComarisonTop($data)
    {
        $id = $data['player_id'];
        $player = User::where('id', $id)->select('id')
            ->withCount(['bowlerWickets as first_six_wickets' => function ($q) {
                $q->whereBetween('position', [1, 2]);
            }])
            ->withCount(['deliveries as total_boundaries' => function ($q) {
                $q->whereIn('boundary_type', ['SIX', 'FOUR']);
                $q->whereNull('run_type');
            }])
            ->withCount(['deliveries as total_runs_against_rh' => function ($q) {
                $q->whereHas('batter', function (Builder $query) {
                    $query->where('batting_style', '=', 'RH');
                });
                $q->select(DB::raw('sum(runs)'));
            }])
            ->withCount(['deliveries as total_runs_against_lh' => function ($q) {
                $q->whereHas('batter', function (Builder $query) {
                    $query->where('batting_style', '=', 'LH');
                });
                $q->select(DB::raw('sum(runs)'));
            }])
            ->withCount(['bowlerWicketsDelivery as total_wickets_against_rh' => function ($q) {
                $q->whereHas('batter', function (Builder $query) {
                    $query->where('batting_style', '=', 'RH');
                });
            }])
            ->withCount(['bowlerWicketsDelivery as total_wickets_against_lh' => function ($q) {
                $q->whereHas('batter', function (Builder $query) {
                    $query->where('batting_style', '=', 'LH');
                });
            }])
            ->withSum(['inningsBowler as total_wickets'], 'wickets')
            ->withSum(['inningsBowler as total_balls_bowled'], 'balls_bowled')
            ->first();
        return $player;
    }


    public function playerOutTypeComparisonQuery($playerId)
    {
        $player_info = Delivery::
        select(DB::raw(
            "SUM(CASE WHEN wicket_type = 'BOWLED' THEN 1 ELSE 0 END) as total_bowled_wickets,
                  SUM(CASE WHEN wicket_type = 'CAUGHT' THEN 1 ELSE 0 END)  as total_caught_wickets,
                  SUM(CASE WHEN wicket_type = 'CAUGHT_BOWLED' THEN 1 ELSE 0 END)  as total_caught_and_bowled_wickets,
                  SUM(CASE WHEN wicket_type = 'CAUGHT_BEHIND' THEN 1 ELSE 0 END)  as total_caught_behind_wickets,
                  SUM(CASE WHEN wicket_type = 'STUMPED' THEN 1 ELSE 0 END)  as total_stumped_wickets,
                  SUM(CASE WHEN wicket_type = 'RUN_OUT' THEN 1 ELSE 0 END)  as total_run_out_wickets,
                  SUM(CASE WHEN wicket_type = 'LBW' THEN 1 ELSE 0 END)  as total_lbw_wickets,
                  SUM(CASE WHEN wicket_type = 'ABSENT' THEN 1 ELSE 0 END)  as total_absent_wickets,
                  SUM(CASE WHEN wicket_type = 'RETIRED_HURT' THEN 1 ELSE 0 END)  as total_retired_hurt_wickets,
                  SUM(CASE WHEN wicket_type = 'ACTION_OUT' THEN 1 ELSE 0 END)  as total_action_wickets,
                  SUM(CASE WHEN wicket_type = 'HIT_WICKET' THEN 1 ELSE 0 END)  as total_hit_wickets,
                  SUM(CASE WHEN wicket_type = 'HIT_BALL_TWICE' THEN 1 ELSE 0 END)  as total_hit_ball_twice_wickets,
                  SUM(CASE WHEN wicket_type = 'OBSTRUCTING_FIELD' THEN 1 ELSE 0 END)  as total_obstructing_field_wickets,
                  SUM(CASE WHEN wicket_type = 'TIME_OUT' THEN 1 ELSE 0 END)  as total_time_out_wickets,
                  SUM(CASE WHEN wicket_type != 'RETIRED_HURT' AND wicket_type != 'RETIRED' THEN 1 ELSE 0 END)  as total_wickets"
        ))
            ->where(function ($query) use ($playerId) {
                $query->where('batter_id', $playerId)
                    ->orWhere('run_out_batter', $playerId);
            })
            ->whereNotNull('wicket_type')
            ->first();
        return $player_info;
    }

    public function playerBowlingOverallStats($data)
    {

        $id = $data['player_id'];
        $player = User::where('id', $id)->select('id', 'first_name', 'last_name', 'username')
            ->withCount([
                'playerElevens as total_matches' => function ($query) {
                    $query->where('is_played', 1);
                },
                'inningsBowler as total_innings',
                'deliveries as total_dots' => function ($query) {
                    $query->where('ball_type', '=', 'LEGAL')->where('runs', 0)->where('extras', 0);
                },
                'caughtBy as total_caughts',
                'deliveries as total_fours' => function ($q) {
                    $q->whereIn('ball_type', ['LEGAL', 'NB'])->where('boundary_type', '=', 'FOUR');
                },
                'deliveries as total_sixes' => function ($q) use ($id) {
                    $q->whereIn('ball_type', ['LEGAL', 'NB'])->where('boundary_type', '=', 'SIX');
                },
                'inningsBowler as total_three_wickets' => function ($query) {
                    $query->where('wickets', 3);
                },
                'inningsBowler as total_five_wickets' => function ($query) {
                    $query->where('wickets', 5);
                },
                'inningsBowler as total_match_overs' => function ($query) {
                    $query->select(DB::raw('sum(floor(overs_bowled))'));
                },
                'inningsBowler as total_overs_extras_balls' => function ($query) {
                    $query->select(DB::raw('floor(sum(  (overs_bowled - floor(overs_bowled))*10  ))'));
                },
            ])
            ->withSum(
                ['inningsBowler as total_deliveries'], 'balls_bowled'
            )
            ->withSum(
                ['inningsBowler as total_maidens'], 'maiden_overs'
            )
            ->withSum(
                ['inningsBowler as total_wickets'], 'wickets'
            )
            ->withSum(
                ['inningsBowler as total_runs'], 'runs_gave'
            )
            ->withSum(
                ['inningsBowler as total_wides'], 'wide_balls'
            )
            ->withSum(
                ['inningsBowler as total_noballs'], 'no_balls'
            )
            ->with(['inningsBowler' => function ($q) use ($id) {
                $q->where('wickets', '=', DB::raw("(select max(`wickets`) from inning_bowler_results where bowler_id = $id)"))
                    ->select('id', 'bowler_id', 'runs_gave', 'wickets')->limit(1);
            }])
            ->first();
        return $player;
    }


    public function getPlayerList($data)
    {
        $id = $data['player_id'];
        $lastId = isset($data['last_id']) ? $data['last_id'] : null;
        $str = isset($data['str']) ? $data['str'] : null;
        $user = User::where('id', '!=', $id)->where('registration_type', 'PLAYER');
        if ($lastId) {
            $user->where('id', '<', $lastId);
        }
        if ($str) {
            $user->where(function ($query) use ($str) {
                $query->orWhereRaw("concat(first_name, ' ', last_name) like '%$str%' ");
            });
        }

        return $user->orderByDesc('id')->limit(15)->get(['id', 'first_name', 'last_name', 'profile_pic', 'username']);
    }

    public function battingFaceOff($data)
    {

        $id = $data['player_id'];
        $pId = isset($data['face_off_player_id']) ? $data['face_off_player_id'] : '';
        return User::where('id', $id)->where('registration_type', '=', 'PLAYER')
            ->select('id', 'first_name', 'last_name', 'username')
            ->withCount([
                'inningsbatter as total_innings' => function ($q) use ($pId) {
                    $q->whereHas('innings_bowler', function (Builder $query) use ($pId) {
                        $query->where('bowler_id', $pId);
                    });
                },
                'batting as total_balled_faced' => function ($q) use ($pId) {
                    $q
                        ->where('bowler_id', $pId)
                        ->where(function($q){
                            $q
                                ->where('ball_type', '=', 'LEGAL')
                                ->orWhere('ball_type', '=', 'NB');
                        });
                },
                'batting as total_dot_balls' => function ($q) use ($pId) {
                    $q
                        ->where('bowler_id', $pId)
                        ->where('ball_type', '=', 'LEGAL')
                        ->where('runs', 0)
                        ->where('extras', 0);
                },
                'batting as total_sixes' => function ($q) use ($pId) {
                    $q
                        ->where('bowler_id', $pId)
                        ->where(function($q){
                            $q
                                ->where('ball_type', '=', 'LEGAL')
                                ->orWhere('ball_type', '=', 'NB');
                        })
                        ->where('boundary_type', '=', 'SIX');
                },
                'batting as total_fours' => function ($q) use ($pId) {
                    $q
                    ->where('bowler_id', $pId)
                    ->where(function($q){
                        $q
                            ->where('ball_type', '=', 'LEGAL')
                            ->orWhere('ball_type', '=', 'NB');
                    })
                    ->where('boundary_type', '=', 'FOUR');
                },
                'batting as total_wickets' => function ($q) use ($pId) {
                    $q
                        ->where('bowler_id', $pId)
                        ->where('wicket_by', $pId);
                },
            ])
            ->withSum(['batting_by_deliveries as total_runs_scored' => function ($q) use ($pId) {
                $q->where('bowler_id', $pId);
            }], 'runs')
            ->first();
    }


    public function bowlingFaceOff($data)
    {

        $id = $data['player_id'];
        $pId = isset($data['face_off_player_id']) ? $data['face_off_player_id'] : '';
        return User::where('id', $id)->where('registration_type', '=', 'PLAYER')
            ->select('id', 'first_name', 'last_name', 'username')
            ->withCount([
                'inningsBowler as total_innings' => function ($q) use ($pId) {
                    $q->whereHas('innings_batter', function (Builder $query) use ($pId) {
                        $query->where('batter_id', $pId);
                    });
                },
                'deliveries as total_balled_faced' => function ($q) use ($pId) {
                    $q->where('batter_id', $pId)->whereIn('ball_type', ['LEGAL', 'NB']);
                },
                'deliveries as total_dot_balls' => function ($q) use ($pId) {
                    $q->where(function($q2) use ($pId){
                        $q2->where('batter_id', $pId)
                        ->where('ball_type', '=', 'LEGAL')
                        ->where('runs', 0)->where('extras', 0);
                    });
                    // $q->where('batter_id', $pId)->where('ball_type', '=', 'LEGAL')->where('runs', 0)->where('extras', 0);
                },
                'deliveries as total_sixes' => function ($q) use ($pId) {
                    $q->where('batter_id', $pId)->whereIn('ball_type', ['LEGAL', 'NB'])->where('boundary_type', '=', 'SIX');
                },
                'deliveries as total_fours' => function ($q) use ($pId) {
                    $q->where('batter_id', $pId)->whereIn('ball_type', ['LEGAL', 'NB'])->where('boundary_type', '=', 'FOUR');
                },
            ])
            ->withSum(['deliveries as total_runs_scored' => function ($q) use ($pId) {
                $q->where('batter_id', $pId)->where('ball_type', '=', 'LEGAL');
            }], 'runs')
            ->first();
    }

    public function bowlingPostion($id)
    {

        $Batter = Over::where('bowler_id', $id)
            ->selectRaw(
                "count(id) as total_overs,
            over_number,
            (SELECT
             sum(runs)
            FROM deliveries
            WHERE deliveries.over_id = overs.id ) as total_runs,
            (SELECT
            SUM(CASE WHEN wicket_by = $id THEN 1 ELSE 0 END)
            FROM deliveries
            WHERE deliveries.over_id = overs.id ) as total_wickets
            "
            )->groupBy('over_number')->limit(50)->get();
        return $Batter;

    }

    // (value/2) AS calculated
    public function battingPosition($id)
    {
        $Batter = InningBatterResult::where('batter_id', $id)
            ->selectRaw('
            count(id) as totalInnings,
            position,
            sum(runs_achieved) as totalRuns,
            sum(balls_faced) as totalBallFaced,
            round(( sum(runs_achieved)/sum(balls_faced))*100, 2) AS strikeRate'
            )->groupBy('position')->get();
        return $Batter;
    }

    public function outBetweenRuns($id)
    {
        $Batter =User::where('id', $id)->select('id')
        ->withCount(['inningsbatter as 1-10' =>function($q){
            $q->where('is_out', 1)->whereBetween('runs_achieved', [1, 10]);
        }])
        ->withCount(['inningsbatter as 11-20' =>function($q){
            $q->where('is_out', 1)->whereBetween('runs_achieved', [11, 20]);
        }])
        ->withCount(['inningsbatter as 21-30' =>function($q){
            $q->where('is_out', 1)->whereBetween('runs_achieved', [21, 30]);
        }])
        ->withCount(['inningsbatter as 31-40' =>function($q){
            $q->where('is_out', 1)->whereBetween('runs_achieved', [31, 40]);
        }])
        ->withCount(['inningsbatter as 41-50' =>function($q){
            $q->where('is_out', 1)->whereBetween('runs_achieved', [41, 50]);
        }])
        ->withCount(['inningsbatter as total_wickets' =>function($q){
            $q->where('is_out', 1);
        }])
        ->first();
        $total_wickets = $Batter->total_wickets;
        unset($Batter->id, $Batter->total_wickets);
        $Batter = $Batter->toArray();
        $new_array = [];
        foreach($Batter as $key => $val){

            array_push($new_array,[
                'wickets'=>$val && $total_wickets ? floor(($val/$total_wickets)*100):0,
                'runs' => $key
            ]);

        }


        return $new_array;
    }

    public function getBatterInningsWicketRecords($playerId){
        return InningBatterResult::select('runs_achieved')
            ->where('batter_id', $playerId)
            ->where('is_out', 1)
            ->orderBy('runs_achieved')
            ->get();
    }

    public function battingAgainstDifferentBowlers($id){
        return User::where('id', $id)->select('id')
        ->withCount(['batting' =>function($q){

        }])
        ->first();
    }

    public function awardInMatches($data){
        $id = $data['player_id'];
        return Fixture::where('player_of_the_match', $id)->select('id', 'player_of_the_match', 'match_date', 'away_team_id', 'home_team_id', 'tournament_id')
        ->with('home_team')->with('away_team')->with('tournament:id,tournament_name')
        ->with(['bestPlayerBatting' =>function($q) use ($id){
            $q->where('batter_id', $id);
            $q->select('id', 'fixture_id', 'batter_id', 'sixes', 'fours', 'runs_achieved', 'balls_faced');
        }])
        ->with(['bestPlayerBowling' =>function($q) use ($id){
            $q->where('bowler_id', $id);
            $q->select('id', 'fixture_id', 'bowler_id', 'overs_bowled', 'maiden_overs', 'balls_bowled', 'wickets', 'runs_gave');
        }])->withCount(['awardLike as total_likes' =>function($q) use ($id){
            $q->where('player_id', $id);
        }])
        ->orderByDesc('id')->get();
    }

    public function awardInTournaments($data){
        $id = $data['player_id'];
        return Tournament::where('player_of_the_tournament', $id)
        ->select('id', 'tournament_name' ,'player_of_the_tournament', 'start_date', 'end_date')
        ->withCount(['awardLike as total_likes' => function($q) use($id){
            $q->where('player_id', $id);
        }])
        ->with(['bestPlayerBattings' =>function($q) use ($id){
            $q->where('batter_id', $id)
            ->select('id','tournament_id','batter_id','team_id')
            ->with('team')
            ->first();
        }])
        ->with(['bestPlayerBowlings' =>function($q) use ($id){
            $q->where('bowler_id', $id)
            ->select('id','tournament_id','bowler_id','team_id')
            ->with('team')
            ->first();
        }])
        //batter
        ->withCount([
           'bestPlayerBattings as total_innings_as_batter' => function($q) use ($id){
             $q->where('batter_id', $id);
           },
           'bestPlayerBattings as total_outs_as_batter' => function($q) use ($id){
             $q->where('batter_id', $id);
             $q->where('is_out', 1);
           },
        ])
        ->withSum(['bestPlayerBattings as total_runs_as_batter' =>function($q) use ($id){
            $q->where('batter_id', $id);
        }],'runs_achieved')
        ->withSum(['bestPlayerBattings as total_balls_as_batter' =>function($q) use ($id){
            $q->where('batter_id', $id);
        }],'balls_faced')
        ->withSum(['bestPlayerBattings as total_fours_as_batter' =>function($q) use ($id){
            $q->where('batter_id', $id);
        }],'fours')
        ->withSum(['bestPlayerBattings as total_sixes_as_batter' =>function($q) use ($id){
            $q->where('batter_id', $id);
        }],'sixes')
        ->withMax(['bestPlayerBattings as max_as_batter' =>function($q) use ($id){
            $q->where('batter_id', $id);
        }],'runs_achieved')

        //bowler
        ->withCount([
          'bestPlayerBowlings as total_innings_as_bowler' => function($q) use ($id){
            $q->where('bowler_id', $id);
           },
          'bestPlayerBowlings as total_overs_as_bowler' => function($q) use ($id){
            $q->where('bowler_id', $id);
            $q->select(DB::raw('sum(floor(overs_bowled))'));
           },
          'bestPlayerBowlings as total_overs_extras_as_bowler' => function($q) use ($id){
            $q->where('bowler_id', $id);
            $q->select(DB::raw('sum(overs_bowled - floor(overs_bowled))*10'));
           },
          'bestPlayerBowlings as total_maidens_as_bowler' => function($q) use ($id){
            $q->where('bowler_id', $id);
            $q->select(DB::raw('sum( floor(maiden_overs) )'));
           },
          'bestPlayerBowlings as total_wickets_as_bowler' => function($q) use ($id){
            $q->where('bowler_id', $id);
            $q->select(DB::raw('sum( wickets )'));
           },
        ])
        ->withSum(['bestPlayerBowlings as total_runs_as_bowler' =>function($q) use ($id){
            $q->where('bowler_id', $id);
        }],'runs_gave')
        ->withMax(['bestPlayerBowlings as highest_as_bowler' =>function($q) use ($id){
            $q->where('bowler_id', $id);
        }],'wickets')

        ->get();
    }

    public function awardsLike($data){
        return AwardLike::create($data);
    }

    public function checkAward($data){
        $type = $data['type'];
        $fId = isset($data['fixture_id']) ? $data['fixture_id'] :0;
        $tId = isset($data['tournament_id']) ? $data['tournament_id'] :0;
        $uid = $data['user_id'];
        $check = AwardLike::where('user_id', $uid);
        $ob = [];
        if($fId && $type == "LIKE"){
            $ob['fixture_like'] = $check->where('fixture_id', $fId)->count();
        }
        if($tId && $type == "LIKE"){
            $ob['tournament_like'] = $check->where('tournament_id', $tId)->count();
        }
        return (object)$ob;
    }

    public function deleteAwardLike($fId, $tId, $uid){

        $player = AwardLike::where('user_id', $uid);

        if($fId){
            return $player->where('fixture_id', $fId)->delete();
        }

        if($tId){
            return $player->where('tournament_id', $tId)->delete();
        }
    }

    public function bowlerStatesByYear($id)
    {

        // $innings2 = InningBowlerResult::get();
        // $innings = InningBowlerResult::selectRaw('year(created_at) as year,
        //   sum(inning_bowler_results.wickets) as total_wickets')
        //     ->addSelect(['last_flight' => Delivery::selectRaw('COUNT(id)')
        //     ->whereColumn('deliveries.inning_id', 'inning_bowler_results.id')
        //     ->whereColumn('deliveries.bowler_id', 'inning_bowler_results.bowler_id')
        //     ->limit(1)
        //      ])
        //     ->groupBy('year')
        //     ->orderByRaw('min(created_at) desc')
        //     ->get();

        $innings = DB::select("select year(I.created_at) as year,
        count(I.inning_id) as total_innings,
        sum(I.maiden_overs) as total_maidens,
        sum(I.wickets) as total_wickets,
        sum(floor(I.overs_bowled)) as total_overs_without_balls,
        sum((I.overs_bowled - floor(I.overs_bowled))*10) as total_balls,
        sum(I.runs_gave) as total_runs,
        max(I.wickets) as best_wickets,
        CASE WHEN wickets = max(I.wickets) THEN I.runs_gave ELSE 0 END as best_wickets_runs,
        SUM(CASE WHEN wickets = 3 THEN 1 ELSE 0 END)  as total_three_wickets,
        SUM(CASE WHEN wickets = 5 THEN 1 ELSE 0 END)  as total_five_wickets,

        (SELECT COUNT(deliveries.inning_id) FROM deliveries
        WHERE deliveries.inning_id = I.inning_id AND ball_type = 'LEGAL' AND runs = 0 AND extras = 0 AND bowler_id = $id) as total_dot_balls

        FROM inning_bowler_results as I where bowler_id = $id group by year ORDER BY year DESC
         ");

        return $innings;
    }

    //Face off comparison insights start

    public function getPlayerFaceOffOutsComparisonQuery($data)
    {
        $playerId = $data['player_id'];
        $comparerId = $data['comparer_id'];
        return Delivery::select(
            DB::raw(
                "SUM(CASE WHEN wicket_type = 'CAUGHT' THEN 1 ELSE 0 END) as total_caught_wickets,
                   SUM(CASE WHEN wicket_type = 'BOWLED' THEN 1 ELSE 0 END) as total_bowled_wickets"
            ))
            ->where('batter_id', $playerId)
            ->where('bowler_id', $comparerId)
            ->first();
    }

    //Face off comparison insights end



}
