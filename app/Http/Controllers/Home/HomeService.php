<?php

namespace App\Http\Controllers\Home;

class HomeService
{
    private $homeQuery;

    public function __construct(HomeQuery $homepageQuery)
    {
        $this->homeQuery = $homepageQuery;
    }

    public function getAllMatchesList($data)
    {
        $messyCollection = $this->homeQuery->getAllMatchesListQuery($data);

//        return $messyCollection;
        $formattedCollection = collect();

        foreach ($messyCollection as $messyObj) {
            $formattedObj = collect();

//          match general information
            $formattedObj->put('fixture_id', $messyObj->id);

            // formatting match number
            $numberFormatter = new \NumberFormatter('en_US', \NumberFormatter::ORDINAL);
            $formattedObj->put('match_no', $numberFormatter->format($messyObj->match_no));
            $formattedObj->put('round_type', $messyObj->round_type);

            if ($messyObj->leagueGroup) {
                $formattedObj->put('group_name', $messyObj->leagueGroup->league_group_name);
            }

            if ($messyObj->tournament and $messyObj->tournament->tournament_name) {
                $formattedObj->put('tournament_name', $messyObj->tournament->tournament_name);
            }

            $formattedObj->put('tournament_id', $messyObj->tournament_id);
            $formattedObj->put('is_match_start', $messyObj->is_match_start);
            $formattedObj->put('toss_winner_team_id', $messyObj->toss_winner_team_id);
            $formattedObj->put('team_elected_to', $messyObj->team_elected_to);


//          formatting batting team information
            if ($messyObj->toss_winner_team_id == $messyObj->home_team_id) {
                $formattedObj->put('batting_team_id', $messyObj->home_team_id);
                $formattedObj->put('batting_team_name', $messyObj->homeTeam->team_name);
                $formattedObj->put('batting_team_logo', $messyObj->homeTeam->team_logo);
                $formattedObj->put('batting_team_runs_took', $messyObj->home_team_runs);
                $formattedObj->put('batting_team_overs_faced', $messyObj->away_team_overs);
                $formattedObj->put('batting_team_wickets_loss', $messyObj->away_team_wickets);
                $formattedObj->put('bowling_team_id', $messyObj->away_team_id);
                $formattedObj->put('bowling_team_name', $messyObj->awayTeam->team_name);
                $formattedObj->put('bowling_team_logo', $messyObj->awayTeam->team_logo);
                $formattedObj->put('bowling_team_runs_took', $messyObj->away_team_runs);
                $formattedObj->put('bowling_team_overs_faced', $messyObj->home_team_overs);
                $formattedObj->put('bowling_team_wickets_loss', $messyObj->home_team_wickets);


//              checking first innings is start
                if ($formattedObj->get('batting_team_overs_faced')) {
                    $formattedObj->put('first_innings_start', 1);
                } else {
                    $formattedObj->put('first_innings_start', 0);
                }
//              checking second innings is start
                if ($formattedObj->get('bowling_team_overs_faced')) {
                    $formattedObj->put('second_innings_start', 1);
                } else {
                    $formattedObj->put('second_innings_start', 0);
                }

            } else if ($messyObj->toss_winner_team_id == $messyObj->away_team_id) {
                $formattedObj->put('batting_team_id', $messyObj->away_team_id);
                $formattedObj->put('batting_team_name', $messyObj->awayTeam->team_name);
                $formattedObj->put('batting_team_logo', $messyObj->awayTeam->team_logo);
                $formattedObj->put('batting_team_runs_took', $messyObj->away_team_runs);
                $formattedObj->put('batting_team_overs_faced', $messyObj->home_team_overs);
                $formattedObj->put('batting_team_wickets_loss', $messyObj->home_team_wickets);
                $formattedObj->put('bowling_team_id', $messyObj->home_team_id);
                $formattedObj->put('bowling_team_name', $messyObj->homeTeam->team_name);
                $formattedObj->put('bowling_team_logo', $messyObj->homeTeam->team_logo);
                $formattedObj->put('bowling_team_runs_took', $messyObj->home_team_runs);
                $formattedObj->put('bowling_team_overs_faced', $messyObj->away_team_overs);
                $formattedObj->put('bowling_team_wickets_loss', $messyObj->away_team_wickets);

//              checking first innings is start
                if ($formattedObj->get('batting_team_overs_faced')) {
                    $formattedObj->put('first_innings_start', 1);
                } else {
                    $formattedObj->put('first_innings_start', 0);
                }
//              checking second innings is start
                if ($formattedObj->get('bowling_team_overs_faced')) {
                    $formattedObj->put('second_innings_start', 1);
                } else {
                    $formattedObj->put('second_innings_start', 0);
                }
            } else {
                $formattedObj->put('batting_team_id', $messyObj->homeTeam->id);
                $formattedObj->put('batting_team_name', $messyObj->homeTeam->team_name);
                $formattedObj->put('batting_team_logo', $messyObj->homeTeam->team_logo);
                $formattedObj->put('bowling_team_id', $messyObj->awayTeam->id);
                $formattedObj->put('bowling_team_name', $messyObj->awayTeam->team_name);
                $formattedObj->put('bowling_team_logo', $messyObj->awayTeam->team_logo);
            }

//          formatting match_date and time
            $matchDateTime = date('D, F d', strtotime($messyObj->match_date)) . '. ' . date('h:i a', strtotime($messyObj->start_time));
            $formattedObj->put('match_datetime', $matchDateTime);
            $formattedCollection->push($formattedObj);
        }

        return $formattedCollection;
    }

    public function getLiveMatchesList($data)
    {
        $messyCollection = $this->homeQuery->getAllMatchesListQuery($data);

        $formattedCollection = collect();

        foreach ($messyCollection as $messyObj) {
            $formattedObj = collect();

//          match general information
            $formattedObj->put('fixture_id', $messyObj->id);

            // formatting match number
            $numberFormatter = new \NumberFormatter('en_US', \NumberFormatter::ORDINAL);
            $formattedObj->put('match_no', $numberFormatter->format($messyObj->match_no));
            $formattedObj->put('round_type', $messyObj->round_type);

            if ($messyObj->leagueGroup) {
                $formattedObj->put('group_name', $messyObj->leagueGroup->league_group_name);
            }

            if ($messyObj->tournament and $messyObj->tournament->tournament_name) {
                $formattedObj->put('tournament_name', $messyObj->tournament->tournament_name);
            }

            $formattedObj->put('tournament_id', $messyObj->tournament_id);
            $formattedObj->put('is_match_start', $messyObj->is_match_start);
            $formattedObj->put('toss_winner_team_id', $messyObj->toss_winner_team_id);
            $formattedObj->put('team_elected_to', $messyObj->team_elected_to);


//          formatting batting team information
            if ($messyObj->toss_winner_team_id == $messyObj->home_team_id) {
                $formattedObj->put('batting_team_id', $messyObj->home_team_id);
                $formattedObj->put('batting_team_name', $messyObj->homeTeam->team_name);
                $formattedObj->put('batting_team_logo', $messyObj->homeTeam->team_logo);
                $formattedObj->put('batting_team_runs_took', $messyObj->home_team_runs);
                $formattedObj->put('batting_team_overs_faced', $messyObj->away_team_overs);
                $formattedObj->put('batting_team_wickets_loss', $messyObj->away_team_wickets);
                $formattedObj->put('bowling_team_id', $messyObj->away_team_id);
                $formattedObj->put('bowling_team_name', $messyObj->awayTeam->team_name);
                $formattedObj->put('bowling_team_logo', $messyObj->awayTeam->team_logo);
                $formattedObj->put('bowling_team_runs_took', $messyObj->away_team_runs);
                $formattedObj->put('bowling_team_overs_faced', $messyObj->home_team_overs);
                $formattedObj->put('bowling_team_wickets_loss', $messyObj->home_team_wickets);


//              checking first innings is start
                if ($formattedObj->get('batting_team_overs_faced')) {
                    $formattedObj->put('first_innings_start', 1);
                } else {
                    $formattedObj->put('first_innings_start', 0);
                }
//              checking second innings is start
                if ($formattedObj->get('bowling_team_overs_faced')) {
                    $formattedObj->put('second_innings_start', 1);
                } else {
                    $formattedObj->put('second_innings_start', 0);
                }

            } else if ($messyObj->toss_winner_team_id == $messyObj->away_team_id) {
                $formattedObj->put('batting_team_id', $messyObj->away_team_id);
                $formattedObj->put('batting_team_name', $messyObj->awayTeam->team_name);
                $formattedObj->put('batting_team_logo', $messyObj->awayTeam->team_logo);
                $formattedObj->put('batting_team_runs_took', $messyObj->away_team_runs);
                $formattedObj->put('batting_team_overs_faced', $messyObj->home_team_overs);
                $formattedObj->put('batting_team_wickets_loss', $messyObj->home_team_wickets);
                $formattedObj->put('bowling_team_id', $messyObj->home_team_id);
                $formattedObj->put('bowling_team_name', $messyObj->homeTeam->team_name);
                $formattedObj->put('bowling_team_logo', $messyObj->homeTeam->team_logo);
                $formattedObj->put('bowling_team_runs_took', $messyObj->home_team_runs);
                $formattedObj->put('bowling_team_overs_faced', $messyObj->away_team_overs);
                $formattedObj->put('bowling_team_wickets_loss', $messyObj->away_team_wickets);

//              checking first innings is start
                if ($formattedObj->get('batting_team_overs_faced')) {
                    $formattedObj->put('first_innings_start', 1);
                } else {
                    $formattedObj->put('first_innings_start', 0);
                }
//              checking second innings is start
                if ($formattedObj->get('bowling_team_overs_faced')) {
                    $formattedObj->put('second_innings_start', 1);
                } else {
                    $formattedObj->put('second_innings_start', 0);
                }
            } else {
                $formattedObj->put('batting_team_id', $messyObj->homeTeam->id);
                $formattedObj->put('batting_team_name', $messyObj->homeTeam->team_name);
                $formattedObj->put('batting_team_logo', $messyObj->homeTeam->team_logo);
                $formattedObj->put('bowling_team_id', $messyObj->awayTeam->id);
                $formattedObj->put('bowling_team_name', $messyObj->awayTeam->team_name);
                $formattedObj->put('bowling_team_logo', $messyObj->awayTeam->team_logo);
            }

//          formatting match_date and time
            $matchDateTime = date('D, F d', strtotime($messyObj->match_date)) . '. ' . date('h:i a', strtotime($messyObj->start_time));
            $formattedObj->put('match_datetime', $matchDateTime);
            $formattedCollection->push($formattedObj);
        }

        return $formattedCollection;
    }

    public function getMatchesListType($data)
    {
        if ($data['match_type'] === 'ALL') {
            $data['is_match_finished'] = 0;
        } else if ($data['match_type'] === 'LIVE') {
            $data['is_match_start'] = 1;
            $data['is_match_finished'] = 0;
        } else if ($data['match_type'] === 'UPCOMING') {
            $data['is_match_start'] = 0;
            $data['is_match_finished'] = 0;
        } else if ($data['match_type'] === 'RECENT') {
            $data['is_match_start'] = 1;
            $data['is_match_finished'] = 1;
        }

        $messyCollection = $this->homeQuery->getMatchesListQuery($data);

        return $messyCollection;
        $formattedCollection = collect();

        foreach ($messyCollection as $messyObj) {
            $formattedObj = collect();
//          formatting match name
            $matchName = '';
            if ($messyObj->match_no) {
                $matchName .= $messyObj->match_no;
            }
            if ($messyObj->round_type) {
                $matchName .= ' ' . $messyObj->round_type;
            }

            if ($messyObj->leagueGroup and $messyObj->leagueGroup->league_group_name) {
                $matchName .= ' ' . $messyObj->leagueGroup->league_group_name;
            }

            if ($messyObj->tournament and $messyObj->tournament->tournament_name) {
                $matchName .= ' ' . $messyObj->tournament->tournament_name;
            }

//          match general information
            $formattedObj->put('fixture_id', $messyObj->id);
            $formattedObj->put('match_title', ltrim($matchName));
            $formattedObj->put('tournament_id', $messyObj->tournament_id);

            if ($messyObj->is_match_finished) {
                $formattedObj->put('is_match_finished', $messyObj->is_match_finished);
                $formattedObj->put('match_winner_team_id', $messyObj->match_winner_team_id);
                $formattedObj->put('match_final_result', $messyObj->match_final_result);
            } else {
                $formattedObj->put('is_match_start', $messyObj->is_match_start);
                $formattedObj->put('toss_winner_team_id', $messyObj->toss_winner_team_id);
                $formattedObj->put('team_elected_to', $messyObj->team_elected_to);
            }

//          formatting batting team information
            if ($messyObj->toss_winner_team_id == $messyObj->home_team_id) {
                $formattedObj->put('batting_team_id', $messyObj->home_team_id);
                $formattedObj->put('batting_team_name', $messyObj->homeTeam->team_name);
                $formattedObj->put('batting_team_logo', $messyObj->homeTeam->team_logo);
                $formattedObj->put('batting_team_runs_took', $messyObj->home_team_runs);
                $formattedObj->put('batting_team_overs_faced', $messyObj->away_team_overs);
                $formattedObj->put('batting_team_wickets_loss', $messyObj->away_team_wickets);
                $formattedObj->put('bowling_team_id', $messyObj->away_team_id);
                $formattedObj->put('bowling_team_name', $messyObj->awayTeam->team_name);
                $formattedObj->put('bowling_team_logo', $messyObj->awayTeam->team_logo);
                $formattedObj->put('bowling_team_runs_took', $messyObj->away_team_runs);
                $formattedObj->put('bowling_team_overs_faced', $messyObj->home_team_overs);
                $formattedObj->put('bowling_team_wickets_loss', $messyObj->home_team_wickets);

                if (!$formattedObj->get('is_match_finished')) {
//              checking first innings is start
                    if ($formattedObj->get('batting_team_overs_faced')) {
                        $formattedObj->put('first_innings_start', 1);
                    } else {
                        $formattedObj->put('first_innings_start', 0);
                    }
//              checking second innings is start
                    if ($formattedObj->get('bowling_team_overs_faced')) {
                        $formattedObj->put('second_innings_start', 1);
                    } else {
                        $formattedObj->put('second_innings_start', 0);
                    }
                }
            } else if ($messyObj->toss_winner_team_id == $messyObj->away_team_id) {
                $formattedObj->put('batting_team_id', $messyObj->away_team_id);
                $formattedObj->put('batting_team_name', $messyObj->awayTeam->team_name);
                $formattedObj->put('batting_team_logo', $messyObj->awayTeam->team_logo);
                $formattedObj->put('batting_team_runs_took', $messyObj->away_team_runs);
                $formattedObj->put('batting_team_overs_faced', $messyObj->home_team_overs);
                $formattedObj->put('batting_team_wickets_loss', $messyObj->home_team_wickets);
                $formattedObj->put('bowling_team_id', $messyObj->home_team_id);
                $formattedObj->put('bowling_team_name', $messyObj->homeTeam->team_name);
                $formattedObj->put('bowling_team_logo', $messyObj->homeTeam->team_logo);
                $formattedObj->put('bowling_team_runs_took', $messyObj->home_team_runs);
                $formattedObj->put('bowling_team_overs_faced', $messyObj->away_team_overs);
                $formattedObj->put('bowling_team_wickets_loss', $messyObj->away_team_wickets);
                if (!$formattedObj->get('is_match_finished')) {
//              checking first innings is start
                    if ($formattedObj->get('batting_team_overs_faced')) {
                        $formattedObj->put('first_innings_start', 1);
                    } else {
                        $formattedObj->put('first_innings_start', 0);
                    }
//              checking second innings is start
                    if ($formattedObj->get('bowling_team_overs_faced')) {
                        $formattedObj->put('second_innings_start', 1);
                    } else {
                        $formattedObj->put('second_innings_start', 0);
                    }
                }
            } else {
                $formattedObj->put('batting_team_id', $messyObj->homeTeam->id);
                $formattedObj->put('batting_team_name', $messyObj->homeTeam->team_name);
                $formattedObj->put('batting_team_logo', $messyObj->homeTeam->team_logo);
                $formattedObj->put('bowling_team_id', $messyObj->awayTeam->id);
                $formattedObj->put('bowling_team_name', $messyObj->awayTeam->team_name);
                $formattedObj->put('bowling_team_logo', $messyObj->awayTeam->team_logo);
            }

//          formatting match_date and time
            $matchDateTime = date('D, F d', strtotime($messyObj->match_date)) . '. ' . date('h:i a', strtotime($messyObj->start_time));
            $formattedObj->put('match_datetime', $matchDateTime);
            $formattedCollection->push($formattedObj);
        }

        return $formattedCollection;
    }

    public function getTournamentsList($data)
    {
        $messyCollection = $this->homeQuery->getTournamentsListQuery($data);

        foreach ($messyCollection as $messyObj) {
            $messyObj->match_type = ucwords(strtolower($messyObj->match_type));
            $messyObj->start_date = date('M d Y', strtotime($messyObj->start_date));
            $messyObj->end_date = date('M d Y', strtotime($messyObj->end_date));
        }

        return $messyCollection;
    }
}
