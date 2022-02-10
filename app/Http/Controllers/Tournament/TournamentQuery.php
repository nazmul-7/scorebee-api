<?php

namespace App\Http\Controllers\Tournament;

use App\Models\Delivery;
use App\Models\Ground;
use App\Models\Team;
use App\Models\Fixture;
use App\Models\Inning;
use App\Models\MatchRank;
use App\Models\Tournament;
use App\Models\LeagueGroup;
use App\Models\PlayingEleven;
use App\Models\TournamentTeam;
use App\Models\LeagueGroupTeam;
use App\Models\TournamentSetting;
use App\Models\TournamentGround;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Log;

class TournamentQuery
{

    //Tournament-start
    public function getTournamentByIdQuery($tournamentId)
    {
        return Tournament::select('id', 'tournament_name', 'tournament_banner', 'tournament_logo', 'start_date', 'end_date', 'organizer_id', 'is_whatsapp', 'organizer_phone', 'tournament_type')
            ->where('id', $tournamentId)->withCount(['tournament_team' => function ($q) {
                $q->where('status', 'PENDING');
            }])
            ->first();
    }

    public function getTournamentsQuery($data)
    {
        $q = Tournament::where('organizer_id', $data['user_id'])->where('is_match_finished', 0)->orderBy('id', 'desc');
        if (isset($data['last_id'])) {
            $q->where('id', '<', $data['last_id']);
        }
        return $q->get();
    }

    public function getAllTournamentsQuery($data)
    {
        // return "i am from query";
        $type = isset($data['type']) ? $data['type'] : '';
        $status = isset($data['status']) ? $data['status'] : '';
        $uid = isset($data['uid']) ? $data['uid'] : 0;
        $category = $data['category'] ?? null;
        $ballType = $data['ballType'] ?? null;
        $matchType = $data['matchType'] ?? null;
        $year = $data['year'] ?? null;

        $q = Tournament::select('id', 'tournament_name', 'tournament_banner', 'city', 'start_date', 'end_date', 'is_start', 'is_finished')
        ->when($category, function($q) use($category){
            $q->where('tournament_category', $category);
        })
        ->when($ballType, function($q) use($ballType){
            $q->where('ball_type', $ballType);
        })
        ->when($matchType, function($q) use($matchType){
            $q->where('match_type', $matchType);
        })
        ->when($year, function($q) use($year){
            $q->whereYear('start_date', $year);
        });
        if (isset($data['last_id']) && $data['last_id']) {
            $q->where('id', '<', $data['last_id']);
        }
        if ($status && $status == "my_tournament" && $uid) {
            $q->where('organizer_id', $uid);
            $q->orWhereHas('fixtures', function (Builder $q) use($uid) {
                $q->whereHas('playingElevens', function (Builder $q2) use($uid) {
                    $q2->where('player_id', $uid);
                });
            });
            $q->orWhereHas('tournament_team', function (Builder $q) use($uid) {
                $q->whereHas('team', function (Builder $q2) use($uid) {
                    $q2->where('owner_id', $uid);
                });
            });
        }
        if ($type && $type == "home") {
            $q->where('is_start', 1);
            $q->orWhere('is_start', 0);
        }

        if ($type && $type == "ongoing") {
                $q
                ->where('is_start', 1)
                ->where('is_finished', 0)
                ->orderByDesc('start_date')
                ->orderByDesc('updated_at');
        }

        if ($type && $type == "upcoming") {
            $q
                ->where('is_start', 0)
                ->where('is_finished', 0)
                ->orderByRaw('start_date < now()')
                ->orderBy('start_date')
                ->orderBy('created_at');
        }

        if ($type && $type == "recent") {
            $q
                ->where('is_start', 1)
                ->where('is_finished', 1)
                ->orderByDesc('end_date')
                ->orderByDesc('updated_at');
        }

        return $q->limit(15)->get();
    }

    public function getAllTournamentsV2Query($type, $limit)
    {

        return Tournament::select('id', 'tournament_name', 'tournament_banner', 'is_start', 'is_finished')
            ->when($type === 'ONGOING', function($q){
                $q
                ->where('is_start', 1)
                ->where('is_finished', 0)
                ->orderByDesc('start_date')
                ->orderByDesc('updated_at');
            })
            ->when($type === 'UPCOMING', function($q){
                $q
                ->where('is_start', 0)
                ->where('is_finished', 0)
                ->whereDate('start_date', '>=', now())
                ->orderBy('start_date')
                ->orderBy('created_at');
            })
            ->when($type === 'RECENT', function($q){
                $q
                ->where('is_start', 1)
                ->where('is_finished', 1)
                ->orderByDesc('end_date')
                ->orderByDesc('updated_at');
            })
            ->limit($limit)
            ->get();
    }

    //Tournament-start

    public function createTournamentsQuery($ob)
    {
        $t = Tournament::create($ob);
        $t->grounds()->attach($ob['ground_id'], ['tour_owner_id' => $ob['organizer_id']]);
        return $t;
    }

    public function updateTournamentsQuery($tId, $ob)
    {

        $t = Tournament::where('id', $tId)->first();
        $gId = $ob['ground_id'];
        unset($ob['ground_id']);
        $t->update($ob);
        // return $ob;
        // if(isset($ob['ground_id'])){
        //     $gId = $ob['ground_id'];
        //     unset($ob['ground_id']);
        $t->grounds()->syncWithPivotValues($gId, ['tour_owner_id' => Auth::id()]);
        // }
        return [
            'message' => 'Tournament updated successfully !'
        ];
    }

    public function deleteTournamentQuery($tId, $uId)
    {
        $t = Tournament::where('id', $tId)->where('organizer_id', $uId)->delete();
        if ($t) {
            return [
                'msg' => 'Tournament Deleted Successfully !'
            ];
        }
    }

    public function inningAvg($id, $num){
        $inning = Inning::where('tournament_id', $id)
        ->where('is_first_innings', $num)
        ->whereHas('fixture', function (Builder $query) {
            $query->where('is_match_finished', 1);
            $query->where('is_match_no_result', 0);
        })
        ->avg('total_runs');
        return floor($inning);
    }
    public function tournamentStats($data){
        $id =  $data['tournament_id'];

        $join = "from innings where tournament_id = $id and
            EXISTS (SELECT *
            FROM fixtures
            WHERE innings.fixture_id = fixtures.id
            and is_match_finished = 1 and
            is_match_no_result = 0 and
         tournament_id = $id )";

         $existCondition = "EXISTS (SELECT *
         FROM fixtures
         WHERE innings.fixture_id = fixtures.id
         and is_match_finished = 1 and
         is_match_no_result = 0 and
         innings.bowling_team_id = fixtures.match_winner_team_id and
          tournament_id = $id )";

         $existCondition2 = "EXISTS (SELECT *
         FROM fixtures
         WHERE innings.fixture_id = fixtures.id
         and is_match_finished = 1 and
         is_match_no_result = 0 and
         innings.batting_team_id = fixtures.match_winner_team_id and
          tournament_id = $id )";

        $highestScore = Inning::where('tournament_id', $id)
        ->whereRaw("total_runs = (select max(`total_runs`) $join)")
        ->with('batting_team')->with('bowling_team')->select('id', 'tournament_id', 'fixture_id', 'total_runs', 'total_wickets', 'total_overs','batting_team_id', 'bowling_team_id')
        ->with(['oppTeam' => function($q) use ($join){
            $q->whereRaw("total_runs != (select max(`total_runs`) $join)");
            $q->select('id','fixture_id', 'total_runs', 'total_wickets', 'total_overs','batting_team_id', 'bowling_team_id');
        }])
        ->first();


        $lowestScore =Inning::where('tournament_id', $id)
        ->whereRaw("total_runs = (select min(`total_runs`) $join)")
        ->with('batting_team')->with('bowling_team')
        ->select('id', 'tournament_id', 'fixture_id', 'total_runs', 'total_wickets', 'total_overs','batting_team_id', 'bowling_team_id')
        ->with(['oppTeam' => function($q) use($join){
            $q->whereRaw("total_runs != (select min(`total_runs`) $join)");
            $q->select('id','fixture_id', 'total_runs', 'total_wickets', 'total_overs','batting_team_id', 'bowling_team_id');
        }])
        ->first();

        $highestScoreChased = Inning::where('tournament_id', $id)->where('is_first_innings', 1)
            ->with('batting_team')->with('bowling_team')
            ->select('id', 'tournament_id', 'fixture_id', 'total_runs', 'total_wickets', 'total_overs','batting_team_id', 'bowling_team_id')
            ->whereRaw("
                total_runs = (select max(total_runs)
                from innings where tournament_id = $id and $existCondition)"
            )
            ->first();
        $lowestScoreDefend = Inning::where('tournament_id', $id)->where('is_first_innings', 1)
            ->with('batting_team')->with('bowling_team')
            ->select('id', 'tournament_id', 'fixture_id', 'total_runs', 'total_wickets', 'total_overs','batting_team_id', 'bowling_team_id')
            ->whereRaw("total_runs = (select min(total_runs) from innings where tournament_id = $id AND $existCondition2 )")
            ->first();

        $firstInningAvg = $this->inningAvg($id, 1);
        $secondInningAvg = $this->inningAvg($id, 0);
        $totalMatches = Fixture::where('tournament_id', $id)
                ->where('is_match_finished', 1)
                ->where('is_match_no_result', 0)
                ->count();
        $matchWonBattingFirst = Fixture::where('fixtures.tournament_id', $id)
                ->leftjoin('innings', 'fixtures.match_winner_team_id', 'innings.batting_team_id')
                ->whereRaw('fixtures.toss_winner_team_id = innings.batting_team_id')
                ->whereRaw('fixtures.id = innings.fixture_id')
                ->where('is_match_finished', 1)
                ->where('is_match_no_result', 0)
                ->where('is_first_innings', 1)
                ->count();

        $matchWonBawlingFirst = Fixture::where('fixtures.tournament_id', $id)
                ->leftjoin('innings', 'fixtures.match_winner_team_id', 'innings.bowling_team_id')
                ->whereRaw('fixtures.toss_winner_team_id = innings.bowling_team_id')
                ->whereRaw('fixtures.id = innings.fixture_id')
                ->select('fixtures.*', 'innings.bowling_team_id', 'innings.fixture_id as f')
                ->where('is_match_finished', 1)
                ->where('is_first_innings', 1)
                ->where('is_match_no_result', 0)
                ->count();
        $totalRunsInTournament = Delivery::where('tournament_id', $id)
            ->whereHas('fixture', function (Builder $query) {
                $query->where('is_match_finished', 1);
                $query->where('is_match_no_result', 0);
            })->count(DB::raw('(runs+extras)'));
        return $totalRunsInTournament;
        $tourTotalFours = Delivery::where('tournament_id', $id)->whereHas('fixture', function (Builder $query) {
            $query->where('is_match_finished', 1);
            $query->where('is_match_no_result', 0);
            })->where('boundary_type', '=', 'FOUR')->count();

        $tourTotalSixes = Delivery::where('tournament_id', $id)
            ->whereHas('fixture', function (Builder $query) {
                $query->where('is_match_finished', 1);
                $query->where('is_match_no_result', 0);
            })->where('boundary_type', '=', 'SIX')->count();

        // return [$tourTotalFours, $tourTotalSixes];

        return [
            "highestScore" => $highestScore,
            "lowestScore" => $lowestScore,
            "highestScoreChased" => $highestScoreChased,
            "lowestScoreDefend" => $lowestScoreDefend,
            "firstInningAvg" => $firstInningAvg,
            "secondInningAvg" => $secondInningAvg,
            "totalMatches" => $totalMatches,
            "matchWonBattingFirst" => $matchWonBattingFirst,
            "matchWonBawlingFirst" => $matchWonBawlingFirst,
            "tourTotalFours" => $tourTotalFours,
            "tourTotalSixes" => $tourTotalSixes,
        ];

    }

    public function getSingleTournament($tId)
    {
        return Tournament::where('id', $tId)->first();
    }
    //Tournament-end

    //Tournament-settings
    public function tournamentSettingsQuery($id, $ob)
    {
        return Tournament::where('id', $id)->update($ob);
    }
    //Tournament-settings


    //Ground-start

    public function addGroundInTournamentQuery($obj)
    {
        return TournamentGround::insert($obj);
    }

    public function tournamentGroundListsQuery($data)
    {
        $tId = $data['tournament_id'];
        $term = $data['term'] ?? null;

        return Ground::whereHas('tournaments', function ($query) use ($tId) {
            $query->where('tournament_id', $tId);
        })
            ->when($term, function ($query) use ($term) {
                $query->where('ground_name', 'LIKE', "%$term%");
            })
            ->get();
    }

    //Ground-end

    //Tournament-fixture

    public function tournamentFixtureQuery($id, $data)
    {
        $lastId = isset($data['last_id']) ? $data['last_id'] : '';
        $f = Fixture::where('tournament_id', $id)->with('home_team')->with('away_team')->orderBy('id', 'desc')->limit(10);
        if ($lastId) {
            $f->where('id', '<', $lastId);
        }
        $fixture = $f->get();
        return $fixture;
    }

    //Tournament-fixture


    //Tournament-team-start

    public function tournamentTeamListQuery($id, $data)
    {
        $lastId = isset($data['last_id']) ? $data['last_id'] : '';
        $status = isset($data['status']) ? $data['status'] : '';
        $str = isset($data['str']) ? $data['str'] : '';

        $tTeam = TournamentTeam::where('tournament_id', $id)->with('team', 'team.captain:id,first_name,last_name');
        if ($lastId) {
            $tTeam->where('id', '<', $lastId);
        }
        if ($status) {
            $tTeam->where('status', $status);
        } else {
            $tTeam->where('status', 'ACCEPTED');
        }
        if ($str) {
            $tTeam->whereHas('team', function ($q2) use ($str) {
                $q2->where('team_name', 'like', '%' . $str . '%');
            });
        }
        $tournamentTeam = $tTeam->orderBy('id', 'desc')->limit(10)->get();
        return $tournamentTeam;
    }

    public function getGroupsByTournament($tournamentId)
    {
        return LeagueGroup::select('id', 'league_group_name AS name')
            ->where('tournament_id', $tournamentId)
            ->orderBy('id')
            ->get();
    }

    public function getNextGroupByTournament($tournamentId, $groupName)
    {
        return LeagueGroup::select('id')
            ->where('tournament_id', $tournamentId)
            ->where('league_group_name', $groupName)
            ->first();
    }

    public function addTeamToGroupQuery($data)
    {
        return LeagueGroupTeam::create($data);
    }

    public function tournamentPointsTableQuery($id, $lg_Id, $lastId, $limit)
    {
        $lg = LeagueGroup::where('tournament_id', $id)
            ->with(['group_teams' => function ($q) {
                $q->select('id', 'league_group_id', 'team_id');
                $q->withSum(['team_points as total_won'], 'won');
                $q->withSum(['team_points as total_loss'], 'loss');
                $q->withSum(['team_points as total_tied'], 'draw');
                $q->withSum(['team_points as total_points'], 'points');
                $q->withCount(['team_points as total_matches']);
                $q->with('teams:id,team_name,team_short_name');
                $q->withCount([
                    'team_points as NR' => function ($q2) {
                        $q2->where([
                            'won' => 0,
                            'loss' => 0,
                            'draw' => 0,
                        ]);
                    },
                    'team_batting_innings as home_team_total_overs' => function ($q2) {
                        $q2->select(DB::raw('sum(floor(total_overs)) '));
                    },
                    'team_batting_innings as home_team_total_balls' => function ($q2) {
                        $q2->select(DB::raw('sum(  (total_overs - floor(total_overs))*10  )'));
                    },
                    'team_bowling_innings as away_team_total_overs' => function ($q2) {
                        $q2->select(DB::raw('sum(floor(total_overs)) '));
                    },
                    'team_bowling_innings as away_team_total_balls' => function ($q2) {
                        $q2->select(DB::raw('sum(  (total_overs - floor(total_overs))*10  )'));
                    },
                ]);
                $q->withSum(['team_batting_innings as home_team_total_runs'], 'total_runs');
                $q->withSum(['team_bowling_innings as away_team_total_runs'], 'total_runs');
                $q->orderByDesc('total_points');
            }]);
        if ($lg_Id) {
            $lg->where('id', $lg_Id);
        }
        if ($limit) {
            $lg->limit($limit);
        } else {
            $lg->limit(10);
        }

        if ($lastId) {
            $lg->where('id', '>', $lastId);
        }

        return $lg->get(['id', 'league_group_name', 'tournament_id']);
    }

    // public function tournamentPointsTableQuery($id)
    // {
    //     // $tournament = Team::select('id', 'team_name', 'team_logo')->whereHas('tournament_team', function ($q2) use ($id) {
    //     //     $q2->where('tournament_id', '=', $id);
    //     // })
    //     //     ->withSum(
    //     //         ['match_rank as points' => function ($query) use ($id) {
    //     //             $query->where('tournament_id', '=', $id);
    //     //         }],
    //     //         'points'
    //     //     )
    //     //     ->withSum(
    //     //         ['home_team_fixture as opposite_team_run' => function ($query) {
    //     //             $query->where('is_match_no_result', 0);
    //     //         }],
    //     //         'away_team_runs'
    //     //     )
    //     //     ->withSum(
    //     //         ['away_team_fixture as opposite_team_run_two' => function ($query) {
    //     //             $query->where('is_match_no_result', 0);
    //     //         }],
    //     //         'home_team_runs'
    //     //     )
    //     //     ->withSum(
    //     //         ['home_team_fixture as team_run' => function ($query) {
    //     //             $query->where('is_match_no_result', 0);
    //     //         }],
    //     //         'home_team_runs'
    //     //     )
    //     //     ->withSum(
    //     //         ['away_team_fixture as team_run_two' => function ($query) {
    //     //             $query->where('is_match_no_result', 0);
    //     //         }],
    //     //         'away_team_runs'
    //     //     )
    //     //     ->withSum(
    //     //         ['home_team_fixture as opposite_team_overs' => function ($query) {
    //     //             $query->where('is_match_no_result', 0);
    //     //         }],
    //     //         'away_team_overs'
    //     //     )
    //     //     ->withSum(
    //     //         ['away_team_fixture as opposite_team_overs_two' => function ($query) {
    //     //             $query->where('is_match_no_result', 0);
    //     //         }],
    //     //         'home_team_overs'
    //     //     )
    //     //     ->withSum(
    //     //         ['home_team_fixture as team_overs' => function ($query) {
    //     //             $query->where('is_match_no_result', 0);
    //     //         }],
    //     //         'home_team_overs'
    //     //     )
    //     //     ->withSum(
    //     //         ['away_team_fixture as team_overs_two' => function ($query) {
    //     //             $query->where('is_match_no_result', 0);
    //     //         }],
    //     //         'away_team_overs'
    //     //     )
    //     //     ->withCount([
    //     //         'match_rank as total_won' => function ($query) use ($id) {
    //     //             $query->where('tournament_id', '=', $id)->where('won', 1);
    //     //         },
    //     //         'match_rank as total_loss' => function ($query) use ($id) {
    //     //             $query->where('tournament_id', '=', $id)->where('loss', 1);
    //     //         },
    //     //         'match_rank as tied' => function ($query) use ($id) {
    //     //             $query->where('tournament_id', '=', $id)->where('draw', 1);
    //     //         },
    //     //         'match_rank as match' => function ($query) use ($id) {
    //     //             $query->where('tournament_id', '=', $id);
    //     //         },
    //     //         'match_rank as NR' => function ($query) use ($id) {

    //     //             $query->where('tournament_id', '=', $id)->where([
    //     //                 'won' => 0,
    //     //                 'loss' => 0,
    //     //                 'draw' => 0,
    //     //             ]);
    //     //         },
    //     //     ])
    //     //     ->withSum(['batting_team_inning as runs_scored' => function($q) use ($id){
    //     //         $q->where('tournament_id', $id);
    //     //         $q->whereDoesntHave('fixture', function (Builder $query) {
    //     //             $query->where('is_match_no_result', 1);
    //     //         });
    //     //     }], 'total_runs')
    //     //     ->withCount(['batting_team_inning as team_overs_faced' => function($q) use ($id){
    //     //         $q->where('tournament_id', $id);
    //     //         $q->whereDoesntHave('fixture', function (Builder $query) {
    //     //             $query->where('is_match_no_result', 1);
    //     //         });
    //     //         $q->select(DB::raw('sum(floor(total_overs)) '));
    //     //     }])
    //     //     ->withCount(['batting_team_inning as team_overs_ball_faced' => function($q) use ($id){
    //     //         $q->where('tournament_id', $id);
    //     //         $q->whereDoesntHave('fixture', function (Builder $query) {
    //     //             $query->where('is_match_no_result', 1);
    //     //         });
    //     //         $q->select(DB::raw('sum(  (total_overs - floor(total_overs))*10  )'));
    //     //     }])


    //     //     ->withSum(['bowling_team_inning as runs_conceded' => function($q) use ($id){
    //     //         $q->where('tournament_id', $id);
    //     //         $q->whereDoesntHave('fixture', function (Builder $query) {
    //     //             $query->where('is_match_no_result', 1);
    //     //         });
    //     //     }], 'total_runs')

    //     //     ->withCount(['bowling_team_inning as overs_bowled' => function($q) use ($id){
    //     //         $q->where('tournament_id', $id);
    //     //         $q->whereDoesntHave('fixture', function (Builder $query) {
    //     //             $query->where('is_match_no_result', 1);
    //     //         });
    //     //         $q->select(DB::raw('sum(floor(total_overs))'));
    //     //     }])
    //     //     ->withCount(['bowling_team_inning as overs_ball_bowled' => function($q) use ($id){
    //     //         $q->where('tournament_id', $id);
    //     //         $q->whereDoesntHave('fixture', function (Builder $query) {
    //     //             $query->where('is_match_no_result', 1);
    //     //         });
    //     //         $q->select(DB::raw('sum(  (total_overs - floor(total_overs))*10  )'));
    //     //     }])

    //     //     ->get();

    //     // return $tournament;
    // }

    public function tournamentDetailsQuery($id)
    {
        $tournament = Tournament::where('id', $id)->with('grounds:id,ground_name,city')->with('organizer:id,first_name,last_name')->first();
        return $tournament;
    }

    public function singletournamentDetails($id)
    {
        $tournament = Tournament::where('id', $id)->with(['organizer' => function ($q) {
            $q->select('id', 'first_name', 'last_name', 'email', 'profile_pic', 'state', 'country', 'phone');
            $q->withCount('tournaments');
        }])
            ->select('id', 'tournament_name', 'tournament_banner', 'tournament_logo', 'tournament_category', 'tournament_type', 'ball_type', 'city', 'start_date', 'end_date', 'organizer_id', 'details', 'tournament_type')->first();
        $tournament->start_date = date("j M, Y", strtotime($tournament->start_date));
        $tournament->end_date = date("j M, Y", strtotime($tournament->end_date));
        return $tournament;
    }

    public function tournamentScoreQuery($id)
    {
        return Tournament::where('id', $id)->select('id', 'tournament_name', 'tournament_logo')
            ->withCount(['deliveries as total_sixes_in_tournament' => function ($q) {
                $q->where('boundary_type', '=', 'SIX');
            }])
            ->withCount(['deliveries as total_fours_in_tournament' => function ($q) {
                $q->where('boundary_type', '=', 'FOUR');
            }])
            ->withCount(['deliveries as total_wickets_in_tournament' => function ($q) {
                $q->where('wicket_type', '!=', null);
            }])
            ->withCount('fixtures as total_matches')->withCount('innings as total_innings')
            ->withCount(['deliveries as total_runs' => function ($q) {
                $q->select(DB::raw('SUM(runs + extras)'));
            }])
            ->withCount(['deliveries as total_wickets' => function ($q) {
                $q->where('wicket_type', '!=', null);
            }])
            ->withSum(['deliveries as total_extras'], 'extras')
            ->withCount(['deliveries as total_fours' => function ($q) {
                $q->where('boundary_type', '=', 'FOUR');
            }])
            ->withCount(['innings_batter as total_centuries' => function ($q) {
                $q->whereBetween('runs_achieved', [100, 199]);
            }])
            ->withCount(['innings_batter as total_fifties' => function ($q) {
                $q->whereBetween('runs_achieved', [50, 99]);
            }])
            ->withSum(['innings_bowler as total_maiden_overs'], 'maiden_overs')
            ->withCount(['deliveries as total_dots' => function ($q) {
                $q->where('ball_type', '=', 'LEGAL');
                $q->where('wicket_type', '=', null);
                $q->where('runs', '=', 0);
                $q->where('extras', '=', 0);
            }])
            ->withCount(['deliveries as total_catches' => function ($q) {
                $q->where('caught_by', '!=', Null);
            }])
            ->withCount(['deliveries as total_stumpeds' => function ($q) {
                $q->where('stumped_by', '!=', Null);
            }])
            // ->with(['fixtures as highest_home_runs' => function($q){
            //     $q->orderBy('home_team_runs', 'desc');
            //     $q->limit(1);
            //     $q->select('id','tournament_id','home_team_runs');
            // }])
            // ->with(['fixtures as highest_away_runs' => function($q){
            //     $q->orderBy('home_team_runs', 'desc');
            //     // $q->whereRaw('home_team_runs > away_team_runs');
            //     $q->limit(1);
            //     $q->select('id','tournament_id','home_team_runs');
            // }])
            // ->withMax(['fixtures as max_score' =>function($q) use ($id){
            //     $q->where('home_team_wickets', '=', DB::raw("(select max(`home_team_runs`) from fixtures where tournament_id = $id)"));
            // }],'home_team_runs')
            // ->withMax(
            //     ['fixtures as max_score_wicket' => function($query) use ($id) {
            //         $query->where('home_team_runs','=', DB::raw("(select max(`home_team_runs`) from fixtures where tournament_id = $id)"));
            //     }],'home_team_wickets')
            // ->withMin(
            //     ['innings as lowest_score'],'total_runs')
            // ->withMin(
            //     ['innings as lowest_score_wicket' => function($query) use ($id) {
            //         $query->where('total_runs','=', DB::raw("(select min(`total_runs`) from innings where tournament_id = $id)"));
            //     }],
            // 'total_wickets')
            ->first();
    }

    //Tournament-team-start

    //Round-start

    public function addRoundQuery($obj, $uid)
    {
        $data = [
            "tournament_round" => $obj['tournament_round'],
        ];
        return Tournament::where('id', $obj['tournament_id'])->where('user_id', $uid)->update($data);
    }


    //Round-end


    //Team-start

    public function addTeamQuery($ob)
    {
        return Team::create($ob);
    }

    public function editTeamQuery($id, $uid, $ob)
    {
        return Team::where('id', $id)->where('user_id', $uid)->update($ob);
    }

    public function deleteTeamQuery($id, $uid)
    {
        return Team::where('id', $id)->where('user_id', $uid)->delete();
    }





    //Team-end

    //tournament-start
    // public function addTournamentQuery($ob){
    //     return PlayingTeam::create($ob);
    // }

    // public function removeTournament($tId){
    //     return PlayingEleven::where('id', $tId)->delete();
    // }
    //tournament-end

    //LeagueGroup-start
    // public function addGroupQuery($ob){
    //     return LeagueGroup::create($ob);
    // }

    // public function editGroupQuery($gId, $obj){
    //     return LeagueGroup::where('id', $gId)->update($obj);
    // }

    // public function removeGroupQuery($gId){
    //     return LeagueGroup::where('id', $gId)->delete();
    // }

    //LeagueGroup-end

    //LeagueGroup Team -start
    // public function addTeamsInGroupQuery($ob){
    //     return GroupTeam::create($ob);
    // }

    // public function editTeamsInGroupQuery($gId, $obj){
    //     return GroupTeam::where('id', $gId)->update($obj);
    // }

    // public function removeTeamsInGroup($gId){
    //     return GroupTeam::where('id', $gId)->delete();
    // }

    //LeagueGroup team -end

    // Create Fixture

    public function createFixtures($matches, $type = 'multiple')
    {
        if ($type == 'single') return Fixture::create($matches);
        return Fixture::insert($matches);
    }

    public function getLeagueGroupsByTournamentId($tournament_id)
    {
        return LeagueGroup::where('tournament_id', $tournament_id)->get();
    }

    public function getTeamsIdByLeagueGroups($group_id)
    {
        return LeagueGroupTeam::where('league_group_id', $group_id)->pluck('team_id');
    }

    public function getKnockoutMatchesByRound($tournament_id, $round)
    {
        return Fixture::where('tournament_id', $tournament_id)->where('knockout_round', $round)->get();
    }

    public function getKnockoutMatches($tournament_id)
    {
        return Fixture::where('tournament_id', $tournament_id)->orderBy('match_no', 'desc')->get();
    }

    public function getTournamentsListQuery($type, $userType)
    {
        return Tournament::select('id', 'tournament_name', 'tournament_banner', 'start_date', 'end_date')
            ->when($userType == 'ORGANIZER', function ($q) {
                $q->where('organizer_id', \auth('sanctum')->id());
            })
            ->when($userType == 'CLUB_OWNER', function ($q) {
                $q
                    ->whereIn('id', function ($q) {
                        $q->select('tournament_id')
                            ->from('tournament_teams')
                            ->where('status', 'ACCEPTED')
                            ->whereIn('team_id', function ($q) {
                                $q->select('id')->from('teams')->where('owner_id', \auth('sanctum')->id());
                            });
                    });
            })
            ->when($userType == 'PLAYER', function ($q) {
                $q->orWhereHas('fixtures', function (Builder $q) {
                    $q->whereHas('playingElevens', function (Builder $q2) {
                        $q2->where('player_id', \auth('sanctum')->id());
                    });
                });
            })
            ->when($type === 'ONGOING', function($q){
                $q
                ->where('is_start', 1)
                ->where('is_finished', 0)
                ->orderByDesc('start_date')
                ->orderByDesc('updated_at');
            })
            ->when($type === 'UPCOMING', function($q){
                $q
                ->where('is_start', 0)
                ->where('is_finished', 0)
                ->orderByRaw('start_date < now()')
                ->orderBy('start_date')
                ->orderBy('created_at');
            })
            ->when($type === 'RECENT', function($q){
                $q
                ->where('is_start', 1)
                ->where('is_finished', 1)
                ->orderByDesc('end_date')
                ->orderByDesc('updated_at');
            })
            ->limit(5)
            ->get();
    }

    public function playerCurrentBowlingFormAndInnings($data)
    {

    }

}
