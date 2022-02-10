//Player BAtting Stats

$raw ="(SELECT COUNT(PE.player_id)
            FROM playing_elevens as PE
            WHERE PE.player_id = $id
            AND EXISTS (SELECT *
            FROM fixtures as F
            WHERE PE.fixture_id = F.id
            AND PE.team_id = ";
$player = User::where('id', $id)
            ->select('id', 'first_name', 'last_name', 'username')
            ->selectRaw("
                $raw F.match_winner_team_id AND PE.match_type = 'LIMITED OVERS')) as limited_match_won,
                $raw F.match_loser_team_id AND PE.match_type = 'LIMITED OVERS')) as limited_match_loss,
                $raw F.match_winner_team_id AND PE.match_type = 'TEST MATCH')) as test_match_won,
                $raw F.match_loser_team_id AND PE.match_type = 'TEST MATCH')) as test_match_loss
                ")
            ->withCount([
                'playerElevens as test_match' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH')->where('is_played', 1);
                },
                'playerElevens as limited_match' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS')->where('is_played', 1);
                },
                'inningsbatter as test_match_innings' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH');
                },
                'inningsbatter as limited_match_innings' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS');
                },
                'inningsbatter as not_out_in_test' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH')->where('is_out', '=', 0);
                },
                'inningsbatter as not_out_in_limited' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS')->where('is_out', '=', 0);
                },
                'inningsbatter as ducks_in_test' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH')->where('runs_achieved', '=', 0)->where('is_out', '=', 1);
                },
                'inningsbatter as ducks_in_limited' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS')->where('runs_achieved', '=', 0)->where('is_out', '=', 1);
                },
                'inningsbatter as test_match_hundred' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH')->where('runs_achieved', '>', 99);
                },
                'inningsbatter as limited_match_hundred' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS')->where('runs_achieved', '>', 99);
                },
                'inningsbatter as test_match_fifty' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH')->whereBetween('runs_achieved', [50, 99]);
                },
                'inningsbatter as limited_match_fifty' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS')->whereBetween('runs_achieved', [50, 99]);
                },
                'inningsbatter as test_match_thirty' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH')->whereBetween('runs_achieved', [30, 49]);
                },
                'inningsbatter as limited_match_thirty' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS')->whereBetween('runs_achieved', [30, 49]);
                },
                'inningsbatter as be_out_in_limited' => function ($q) {
                    $q->where('match_type', '=', 'LIMITED OVERS')->where('is_out', 1);
                },
                'inningsbatter as be_out_in_test' => function ($q) {
                    $q->where('match_type', '=', 'TEST MATCH')->where('is_out', 1);
                },

            ])
            ->withMax(['inningsbatter as highest_in_limited' => function ($q) {
                $q->where('match_type', '=', 'LIMITED OVERS');
            }], 'runs_achieved')
            ->withMax(['inningsbatter as highest_in_test' => function ($q) {
                $q->where('match_type', '=', 'TEST MATCH');
            }], 'runs_achieved')

            ->withSum(
                ['inningsbatter as faced_limited_ball' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS');
                }],
                'balls_faced'
            )
            ->withSum(
                ['inningsbatter as faced_test_ball' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH');
                }],
                'balls_faced'
            )
            ->withSum(
                ['inningsbatter as test_match_run' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH');
                }],
                'runs_achieved'
            )
            ->withSum(
                ['inningsbatter as limited_match_run' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS');
                }],
                'runs_achieved'
            )
            ->withSum(['inningsbatter as total_test_sixes' => function ($q) {
                $q->where('match_type', '=', 'LIMITED OVERS');
            }], 'sixes')
            ->withSum(['inningsbatter as total_limited_match_sixes' => function ($q) {
                $q->where('match_type', '=', 'TEST MATCH');
            }], 'sixes')
            ->withSum(['inningsbatter as total_test_fours' => function ($q) {
                $q->where('match_type', '=', 'LIMITED OVERS');
            }], 'fours')
            ->withSum(['inningsbatter as total_limited_match_fours' => function ($q) {
                $q->where('match_type', '=', 'TEST MATCH');
            }], 'fours')
            ->first();
        return $player;


//Player-Bowling-Stats


$player = User::where('id', $id)->select('id', 'first_name', 'last_name', 'username')
            ->withCount([
                'playerElevens as test_match' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH')->where('is_played', 1);
                },
                'playerElevens as limited_match' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS')->where('is_played', 1);
                },
                'deliveries as dot_in_test_match' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH')->where('ball_type', '=', 'LEGAL')->where('runs', 0)->where('extras', 0);
                },
                'deliveries as dot_in_limited_match' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS')->where('ball_type', '=', 'LEGAL')->where('runs', 0)->where('extras', 0);
                },
                'inningsBowler as test_match_innings' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH');
                },
                'inningsBowler as limited_match_innings' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS');
                },
                'inningsBowler as three_wickets_in_test' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH')->where('wickets', 3);
                },
                'inningsBowler as three_wickets_in_limited' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS')->where('wickets', 3);
                },
                'inningsBowler as five_wickets_in_test' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH')->where('wickets', 5);
                },
                'inningsBowler as five_wickets_in_limited' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS')->where('wickets', 5);
                },
                'deliveries as total_test_sixes' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH')->where('boundary_type', '=', 'SIX');
                },
                'deliveries as total_limited_match_sixes' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS')->where('boundary_type', '=', 'SIX');
                },
                'deliveries as total_test_fours' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH')->where('boundary_type', '=', 'FOUR');
                },
                'deliveries as total_limited_match_fours' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS')->where('boundary_type', '=', 'FOUR');
                },
                'inningsBowler as overs_bowled_in_test_only' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH');
                    $query->select(DB::raw('sum(floor(overs_bowled))'));
                },
                'inningsBowler as overs_extra_balls_in_test' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH');
                    $query->select(DB::raw('sum(  (overs_bowled - floor(overs_bowled))*10  )'));
                },
                'inningsBowler as overs_bowled_in_limited_only' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS');
                    $query->select(DB::raw('sum(floor(overs_bowled))'));
                },
                'inningsBowler as overs_extra_balls_in_limited' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS');
                    $query->select(DB::raw('sum(  (overs_bowled - floor(overs_bowled))*10  )'));
                },
            ])
            ->withSum(
                ['inningsBowler as total_deliveries_in_test' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH');
                }],
                'balls_bowled'
            )
            ->withSum(
                ['inningsBowler as total_deliveries_in_limited' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS');
                }],
                'balls_bowled'
            )
            ->withSum(
                ['inningsBowler as maiden_in_test' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH');
                }],
                'maiden_overs'
            )
            ->withSum(
                ['inningsBowler as maiden_in_limited' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS');
                }],
                'maiden_overs'
            )
            ->withSum(
                ['inningsBowler as wicket_in_test' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH');
                }],
                'wickets'
            )
            ->withSum(
                ['inningsBowler as wicket_in_limited_match' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS');
                }],
                'wickets'
            )
            ->withSum(
                ['inningsBowler as run_gave_in_test' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH');
                }],
                'runs_gave'
            )
            ->withSum(
                ['inningsBowler as run_gave_in_limited_match' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS');
                }],
                'runs_gave'
            )
            ->withSum(
                ['inningsBowler as wides_in_test' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH');
                }],
                'wide_balls'
            )
            ->withSum(
                ['inningsBowler as wides_in_limited' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS');
                }],
                'wide_balls'
            )
            ->withSum(
                ['inningsBowler as no_balls_in_test' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH');
                }],
                'no_balls'
            )
            ->withSum(
                ['inningsBowler as no_balls_in_limited' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS');
                }],
                'no_balls'
            )
            ->with(['inningsBowler' => function ($q) use ($id) {
                $q->where('match_type', '=', 'LIMITED OVERS')
                    ->where('wickets', '=', DB::raw("(select max(`wickets`) from inning_bowler_results where bowler_id = $id)"))
                    ->select('id', 'bowler_id', 'runs_gave', 'wickets')->limit(1);
            }])
            ->with(['best_in_test' => function ($q) use ($id) {
                $q->where('match_type', '=', 'TEST MATCH')
                    ->where('wickets', '=', DB::raw("(select max(`wickets`) from inning_bowler_results where bowler_id = $id)"))
                    ->select('id', 'bowler_id', 'runs_gave', 'wickets')->limit(1);
            }])
            ->first();
        return $player;



        //Fielding Stats
        $player = User::where('id', $id)->select('id', 'first_name', 'last_name')
            ->withCount([
                'playingElevens as fielding_in_test' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH')->where('is_played', 1);
                },
                'playingElevens as fielding_in_limited' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS')->where('is_played', 1);
                },
                'caughtBy as catch_by_in_test' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH');
                },
                'caughtBy as catch_by_in_limited' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS');
                },
                'assistBy as assist_in_test' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH');
                },
                'assistBy as assist_in_limited' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS');
                },
                'stumpedBy as stumped_in_test' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH');
                },
                'stumpedBy as stumped_in_limited' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS');
                },
                'nunOutBy as run_out_in_test' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH');
                },
                'nunOutBy as run_out_in_limited' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS');
                },
            ])
            ->first();
        return $player;


        //Player Captail stats


        $player = User::where('id', $id)->select('id', 'first_name', 'last_name', 'email', 'username')
            ->withCount([
                'playingElevens as played_test_as_captain' => function ($query) {
                    $query->where('match_type', '=', 'TEST MATCH')->where('is_captain', 1);
                },
                'playingElevens as played_limited_as_captain' => function ($query) {
                    $query->where('match_type', '=', 'LIMITED OVERS')->where('is_captain', 1);
                },
                'playingElevens as toss_win_in_test' => function ($q) {
                    $q->where('match_type', '=', 'TEST MATCH')->where('is_played', 1);
                    $q->whereHas('toss_winner');
                },
                'playingElevens as toss_win_in_limited' => function ($q) {
                    $q->where('match_type', '=', 'LIMITED OVERS')->where('is_played', 1);
                    $q->whereHas('toss_winner');
                },
            ])
            ->first();
        return $player;
