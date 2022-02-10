<?php

namespace App\Http\Controllers\Match;

use App\Http\Controllers\Club\ClubQuery;
use App\Http\Controllers\Team\TeamQuery;

// use App\Http\Controllers\Notification\NotificationQuery;
use App\Http\Controllers\Notification\NotificationService;
use App\Http\Controllers\Tournament\TournamentQuery;
use App\Http\Controllers\Tournament\TournamentService;
use App\Http\Controllers\TournamentSchedule\TournamentScheduleQuery;
use App\Http\Controllers\TournamentSchedule\TournamentScheduleService;
use App\Http\Controllers\Universal\UniversalService;
use App\Models\Fixture;
use App\Models\Inning;
use App\Models\Panalty;
use App\Models\Tournament;
use ArrayIterator;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MatchService
{
    private $matchQuery;
    private $teamQuery;
    private $notificationQuery;
    private $tournamentService;
    private $tournamentQuery;
    private $universalService;
    private $tournamentScheduleQuery;
    private $clubQuery;

    public function __construct(MatchQuery $matchQuery, TeamQuery $teamQuery, NotificationService $notificationQuery, TournamentService $tournamentService, TournamentQuery $tournamentQuery, UniversalService $universalService, TournamentScheduleQuery $tournamentScheduleQuery, ClubQuery $clubQuery)
    {
        $this->matchQuery = $matchQuery;
        $this->teamQuery = $teamQuery;
        $this->tournamentService = $tournamentService;
        $this->notificationQuery = $notificationQuery;
        $this->tournamentQuery = $tournamentQuery;
        $this->universalService = $universalService;
        $this->tournamentScheduleQuery = $tournamentScheduleQuery;
        $this->clubQuery = $clubQuery;
    }

    public function getChallengedMatchesList($data)
    {
        $timezone = $data['timezone'] ?? 'Asia/Dhaka';

        $matches = $this->matchQuery->getChallengedMatchesListQuery($data);
        $currentPage = $matches->currentPage();
        $lastPage = $matches->lastPage();
        $matches->map(function($item) use($timezone){
            if($item->match_date != '1111-11-11'){
                $matchDate = $item->match_date;
                $startTime = $item->start_time ?? Carbon::now('UTC')->format('H:i:s');
                $date = $matchDate . ' ' . $startTime;
                $date = Carbon::createFromFormat('Y-m-d H:i:s', $date, 'UTC')->setTimezone($timezone);
                $item->match_date = $date->format('Y-m-d');
                $item->start_time = $date->format('H:i:s');
            }
        });

        $groupMatches = $matches->groupBy('match_date');
        $formattedCollection = collect();

        foreach ($groupMatches as $key => $matches) {

            $formattedChildCollection = collect();
            $date = Carbon::parse($key);
            $day = $date->format('l');
            if ($date->isToday()) {
                $day = 'Today';
            } else if ($date->isYesterday()) {
                $day = 'Yesterday';
            } else if ($date->isTomorrow()) {
                $day = 'Tomorrow';
            }

            $formattedChildCollection->put('day', $day);
            $formattedChildCollection->put('date', date('d M, Y', strtotime($key)));
            $matchesCollection = collect();

            foreach ($matches as $match) {
                $match['home_team_name'] = $match->homeTeam->team_name;
                $match['away_team_name'] = $match->awayTeam->team_name;
                $match['home_team_logo'] = $match->homeTeam->team_logo;
                $match['away_team_logo'] = $match->awayTeam->team_logo;
                $match['start_time'] = date('h:i A', strtotime($match->start_time));

                unset($match['homeTeam']);
                unset($match['awayTeam']);
                unset($match['match_date']);

                $matchesCollection->push($match);
            }

            $formattedChildCollection->put('matches', $matchesCollection);
            $formattedCollection->push($formattedChildCollection);
        }

        return [
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'matches' => $formattedCollection,
        ];
    }

    public function getMatchesByRound($tournament_id, $round_type)
    {
        $messyCollection = $this->matchQuery->getMatchesByRoundQuery($tournament_id, $round_type);
        $messyCollection = $messyCollection->groupBy('match_date');

        // checking previous round finished or not
        $tournament = $this->matchQuery->getTournamentById($tournament_id);
        $currentRoundIndex = array_search($round_type, array_column($tournament['group_settings'], 'round_type'));
        $isPreviousRoundFinished = 1;
        $previousRoundType = '';

        if ($currentRoundIndex) {
            $previousRoundType = $tournament['group_settings'][$currentRoundIndex - 1]['round_type'];
            $totalUnfinishedMatches = $this->matchQuery->countTournamentMatchesByRound($tournament_id, $previousRoundType, null, 0);
            if ($totalUnfinishedMatches) {
                $isPreviousRoundFinished = 0;
            }
            $previousRoundType = ucwords(strtolower(str_replace('-', ' ', $previousRoundType)));
        }

        $formattedCollection = collect();

        foreach ($messyCollection as $key => $messyChildCollection) {

            $formattedChildCollection = collect();

            $date = $key === '1111-11-11' ? 'Upcoming' : date('d M, Y', strtotime($key));
            $formattedChildCollection->put('date', $date);
            $matchesCollection = collect();

            foreach ($messyChildCollection as $match) {
                $homeTeamId = $awayTeamId = null;
                $homeTeamLogo = $awayTeamLogo = "";
                $homeTeamName = $match->temp_team_one_name;
                $awayTeamName = $match->temp_team_two_name;

                if ($match->home_team_id) {
                    $homeTeamId = $match->home_team_id;
                    $homeTeamName = $match->homeTeam->team_name;
                    $homeTeamLogo = $match->homeTeam->team_logo;
                }

                if ($match->away_team_id) {
                    $awayTeamId = $match->away_team_id;
                    $awayTeamName = $match->awayTeam->team_name;
                    $awayTeamLogo = $match->awayTeam->team_logo;
                }

                $match = [
                    'id' => $match->id,
                    'is_ready' => isset($homeTeamId) and isset($awayTeamId),
                    'home_team_id' => $homeTeamId,
                    'away_team_id' => $awayTeamId,
                    'home_team_name' => $homeTeamName,
                    'away_team_name' => $awayTeamName,
                    'home_team_logo' => $homeTeamLogo,
                    'away_team_logo' => $awayTeamLogo,
                    'is_match_start' => $match->is_match_start,
                    'is_match_finished' => $match->is_match_finished,
                    'previous_round' => $previousRoundType,
                    'is_previous_round_finished' => $isPreviousRoundFinished,
                    'match_time' => $key === '1111-11-11' ? "" : date('h:i A', strtotime($match->start_time))
                ];

                $matchesCollection->push($match);
            }

            $formattedChildCollection->put('matches', $matchesCollection);
            $formattedCollection->push($formattedChildCollection);
        }

        return $formattedCollection;
    }

    public function getMyMatchesList($data)
    {
        $user = Auth::user();
        $data['user_id'] = $user->id;
        $data['user_type'] = $user->registration_type;

        if (isset($data['match_status']) and $data['match_status'] == 'UPCOMING') {
            $data['is_match_start'] = 0;
            $data['is_match_finished'] = 0;
        } else if (isset($data['match_status']) and $data['match_status'] == 'LIVE') {
            $data['is_match_start'] = 1;
            $data['is_match_finished'] = 0;
        } else if (isset($data['match_status']) and $data['match_status'] == 'RECENT') {
            $data['is_match_start'] = 1;
            $data['is_match_finished'] = 1;
        }

        $messyCollection = $this->matchQuery->getMyMatchesListQuery($data);

        $formattedCollection = collect();

        foreach ($messyCollection as $messyObj) {
            $formattedObj = collect();

            $formattedObj->put('fixture_id', $messyObj->id);
            $formattedObj->put('match_no', "Individual");
            $formattedObj->put('tournament_city', $messyObj->tournament->city ?? "");
            $formattedObj->put('tournament_name', $messyObj->tournament->tournament_name ?? "");
            $formattedObj->put('round_type', ucwords(strtolower(str_replace('-', ' ', $messyObj->round_type))) ?? "");
            $formattedObj->put('group_name', "");

            if ($messyObj->round_type == 'GROUP LEAGUE') {
                $formattedObj->put('group_name', $messyObj->leagueGroup->league_group_name ?? "");
            }

            if ($messyObj->tournament_id) {
                //          formatting match number
                $numberFormatter = new \NumberFormatter('en_US', \NumberFormatter::ORDINAL);
                $formattedObj->put('match_no', $numberFormatter->format($messyObj->match_no) ?? "");
            }

            //match_status
            $matchStatus = "UPCOMING";

            if ($messyObj->is_match_start == 1 && $messyObj->is_match_finished == 0) {
                $matchStatus = "LIVE";
            } else if ($messyObj->is_match_start == 1 && $messyObj->is_match_finished == 1) {
                $matchStatus = "RECENT";
            }

            $formattedObj->put('match_status', $matchStatus);

            $formattedObj->put('batting_team_name', $messyObj->homeTeam->team_name ?? $messyObj->temp_team_one_name);
            $formattedObj->put('batting_team_logo', $messyObj->homeTeam->team_logo ?? "");
            $formattedObj->put('bowling_team_name', $messyObj->awayTeam->team_name ?? $messyObj->temp_team_two_name);
            $formattedObj->put('bowling_team_logo', $messyObj->awayTeam->team_logo ?? "");
            $formattedObj->put('batting_team_state', "");
            $formattedObj->put('bowling_team_state', "");
            $formattedObj->put('match_result', 'Not decided yet');

            $tossWinnerTeam = "";
            $battingTeamRuns = $messyObj->home_team_runs ?? 0;
            $battingTeamOvers = $messyObj->home_team_overs ?? 0.0;
            $battingTeamWickets = $messyObj->home_team_wickets ?? 0;
            $bowlingTeamRuns = $messyObj->away_team_runs ?? 0;
            $bowlingTeamOvers = $messyObj->away_team_overs ?? 0.0;
            $bowlingTeamWickets = $messyObj->away_team_wickets ?? 0;

            if ($messyObj->is_match_start or $messyObj->is_match_finished) {

                if ($messyObj->toss_winner_team_id == $messyObj->home_team_id) {
                    $tossWinnerTeam = $messyObj->homeTeam->team_name;
                } else {
                    $tossWinnerTeam = $messyObj->awayTeam->team_name;
                }

                if (($messyObj->toss_winner_team_id == $messyObj->home_team_id and $messyObj->team_elected_to == 'BAT')
                    or ($messyObj->toss_winner_team_id == $messyObj->away_team_id and $messyObj->team_elected_to == 'BOWL')
                ) {
                    $formattedObj->put('batting_team_name', $messyObj->homeTeam->team_name);
                    $formattedObj->put('batting_team_logo', $messyObj->homeTeam->team_logo);
                    $formattedObj->put('bowling_team_name', $messyObj->awayTeam->team_name);
                    $formattedObj->put('bowling_team_logo', $messyObj->awayTeam->team_logo);
                } else {
                    $formattedObj->put('batting_team_name', $messyObj->awayTeam->team_name);
                    $formattedObj->put('batting_team_logo', $messyObj->awayTeam->team_logo);
                    $formattedObj->put('bowling_team_name', $messyObj->homeTeam->team_name);
                    $formattedObj->put('bowling_team_logo', $messyObj->homeTeam->team_logo);
                }
            }

            if ($messyObj->is_match_start) {
                $matchResult = $tossWinnerTeam . ' won the toss and elected to ' . ucfirst(strtolower($messyObj->team_elected_to));
                $formattedObj->put('match_result', $matchResult);

                if ($messyObj->total_innings) {
                    $firstInnings = $messyObj->innings->where('is_first_innings', 1)->first();
                    $secondInnings = $messyObj->innings->where('is_first_innings', 0)->first();
                    $firstInningsStart = $firstInnings->innings_status == 'Started' ? 1 : 0;
                    $secondInningsStart = $secondInnings->innings_status == 'Started' ? 1 : 0;
                    $firstInningsFinished = $firstInnings->innings_status == 'Finished' ? 1 : 0;
                    $secondInningsFinished = $secondInnings->innings_status == 'Finished' ? 1 : 0;

                    if ($firstInningsStart or $firstInningsFinished) {
                        $state = $battingTeamRuns . "/" . $battingTeamWickets . ' (' . $battingTeamOvers . ' Over)';
                        $formattedObj->put('batting_team_state', $state);
                    } else {
                        $formattedObj->put('batting_team_state', 'Yet to bat');
                    }

                    if ($firstInningsFinished and ($secondInningsStart or $secondInningsFinished)) {
                        $state = $bowlingTeamRuns . '/' . $bowlingTeamWickets . ' (' . $bowlingTeamOvers . ' Over)';
                        $formattedObj->put('bowling_team_state', $state);

                        $runsNeed = $battingTeamRuns - $bowlingTeamRuns;
                        $oversLeft = $messyObj->match_overs - $bowlingTeamOvers;
                        $ballsLeft = (0.6 - ($bowlingTeamOvers - floor($bowlingTeamOvers)));

                        $oversLeft = floor($oversLeft);
                        if ($ballsLeft > 0) $oversLeft += $ballsLeft;

                        $matchResult = "";
                        if ($ballsLeft > 0 or $runsNeed > 0) {
                            $matchResult = $formattedObj->get('bowling_team_name') . ' needs ' . $runsNeed . ' from ' . $oversLeft . ' over.';
                        }
                        $formattedObj->put('match_result', $matchResult);
                    } else if ($firstInningsFinished and !$secondInningsStart) {
                        $formattedObj->put('bowling_team_state', 'Yet to bat');
                    }

                    if ($firstInningsFinished and $secondInningsFinished) {
                        $formattedObj->put('match_result', $messyObj->match_final_result);
                    }
                }
            } else if (($messyObj->home_team_id and $messyObj->away_team_id) and $messyObj->match_date != '1111-11-11') {
                $matchDateTime = date('D, F d', strtotime($messyObj->match_date)) . ', ' . date('h:i A', strtotime($messyObj->start_time));
                $formattedObj->put('match_result', $matchDateTime);
            }

            $formattedCollection->push($formattedObj);
        }

        return [
            'current_page' => $messyCollection->currentPage(),
            'last_page' => $messyCollection->lastPage(),
            'matches' => $formattedCollection,
        ];
    }

    public function getAllMatchesList($data)
    {
        $messyCollection = $this->matchQuery->getAllMatchesListQuery($data);
        $formattedCollection = collect();

        foreach ($messyCollection as $messyObj) {
            $formattedObj = collect();

            //match general information
            $formattedObj->put('fixture_id', $messyObj->id);

            // formatting match number
            $matchNumber = 'Individual';
            if ($messyObj->tournament_id) {
                $numberFormatter = new \NumberFormatter('en_US', \NumberFormatter::ORDINAL);
                $matchNumber = $numberFormatter->format($messyObj->match_no);
            }

            $formattedObj->put('match_no', $matchNumber);
            $formattedObj->put('round_type', $messyObj->round_type);
            $formattedObj->put('fixture_type', $messyObj->fixture_type);

            if ($messyObj->round_type == 'GROUP LEAGUE' and $messyObj->fixture_type == 'GROUP' and $messyObj->leagueGroup) {
                $formattedObj->put('round_type', $messyObj->leagueGroup->league_group_name ?? "");
            }

            if ($messyObj->tournament and $messyObj->tournament->tournament_name) {
                $formattedObj->put('tournament_name', $messyObj->tournament->tournament_name);
            }

            $formattedObj->put('tournament_id', $messyObj->tournament_id);
            $formattedObj->put('is_match_start', $messyObj->is_match_start);
            $formattedObj->put('team_elected_to', $messyObj->team_elected_to);


            if ($messyObj->is_match_start) {

                if ($messyObj->toss_winner_team_id == $messyObj->home_team_id) {
                    $formattedObj->put('toss_winner_team', $messyObj->homeTeam->team_name);
                } else {
                    $formattedObj->put('toss_winner_team', $messyObj->awayTeam->team_name);
                }

                if (($messyObj->toss_winner_team_id == $messyObj->home_team_id and $messyObj->team_elected_to == 'BAT')
                    or ($messyObj->toss_winner_team_id == $messyObj->away_team_id and $messyObj->team_elected_to == 'BOWL')
                ) {
                    $formattedObj->put('batting_team_id', $messyObj->home_team_id);
                    $formattedObj->put('batting_team_name', $messyObj->homeTeam->team_name);
                    $formattedObj->put('batting_team_logo', $messyObj->homeTeam->team_logo);
                    $formattedObj->put('bowling_team_id', $messyObj->away_team_id);
                    $formattedObj->put('bowling_team_name', $messyObj->awayTeam->team_name);
                    $formattedObj->put('bowling_team_logo', $messyObj->awayTeam->team_logo);
                    $formattedObj->put('batting_team_runs_took', $messyObj->home_team_runs);
                    $formattedObj->put('batting_team_overs_faced', $messyObj->home_team_overs);
                    $formattedObj->put('batting_team_wickets_loss', $messyObj->home_team_wickets);
                    $formattedObj->put('bowling_team_runs_took', $messyObj->away_team_runs);
                    $formattedObj->put('bowling_team_overs_faced', $messyObj->away_team_overs);
                    $formattedObj->put('bowling_team_wickets_loss', $messyObj->away_team_wickets);
                } else if (
                    ($messyObj->toss_winner_team_id == $messyObj->home_team_id and $messyObj->team_elected_to == 'BOWL')
                    or ($messyObj->toss_winner_team_id == $messyObj->away_team_id and $messyObj->team_elected_to == 'BAT')
                ) {
                    $formattedObj->put('batting_team_id', $messyObj->away_team_id);
                    $formattedObj->put('batting_team_name', $messyObj->awayTeam->team_name);
                    $formattedObj->put('batting_team_logo', $messyObj->awayTeam->team_logo);
                    $formattedObj->put('bowling_team_id', $messyObj->home_team_id);
                    $formattedObj->put('bowling_team_name', $messyObj->homeTeam->team_name);
                    $formattedObj->put('bowling_team_logo', $messyObj->homeTeam->team_logo);
                    $formattedObj->put('batting_team_runs_took', $messyObj->away_team_runs);
                    $formattedObj->put('batting_team_overs_faced', $messyObj->away_team_overs);
                    $formattedObj->put('batting_team_wickets_loss', $messyObj->away_team_wickets);
                    $formattedObj->put('bowling_team_runs_took', $messyObj->home_team_runs);
                    $formattedObj->put('bowling_team_overs_faced', $messyObj->home_team_overs);
                    $formattedObj->put('bowling_team_wickets_loss', $messyObj->home_team_wickets);
                }

                $formattedObj->put('is_match_finished', $messyObj->is_match_finished);
                $formattedObj->put('match_final_result', $messyObj->match_final_result);
            } else {
                $formattedObj->put('batting_team_id', $messyObj->home_team_id);
                $formattedObj->put('batting_team_name', $messyObj->homeTeam->team_name ?? $messyObj->temp_team_one_name);
                $formattedObj->put('batting_team_logo', $messyObj->homeTeam->team_logo ?? "");
                $formattedObj->put('bowling_team_id', $messyObj->away_team_id);
                $formattedObj->put('bowling_team_name', $messyObj->awayTeam->team_name ?? $messyObj->temp_team_two_name);
                $formattedObj->put('bowling_team_logo', $messyObj->awayTeam->team_logo ?? "");
            }

            if ($messyObj->is_match_start and !$messyObj->is_match_finished) {
                if ($messyObj->total_innings) {
                    $firstInnings = $messyObj->innings->where('is_first_innings', 1)->first();
                    $secondInnings = $messyObj->innings->where('is_first_innings', 0)->first();

                    $firstInningsStatus = $firstInnings->innings_status == 'Started' ? 1 : 0;
                    $secondInningsStatus = $secondInnings->innings_status == 'Started' ? 1 : 0;
                    $formattedObj->put('first_innings_id', $firstInnings->id);
                    $formattedObj->put('first_innings_start', $firstInningsStatus);
                    $formattedObj->put('second_innings_id', $secondInnings->id);
                    $formattedObj->put('second_innings_start', $secondInningsStatus);
                }
            }

            //          formatting match_date and time
            $matchDateTime = 'Upcoming';
            if ($messyObj->match_date != '1111-11-11') {
                $matchDateTime = date('D, F d', strtotime($messyObj->match_date)) . '. ' . date('h:i A', strtotime($messyObj->start_time));
            }

            $formattedObj->put('match_datetime', $matchDateTime);
            $formattedCollection->push($formattedObj);
        }
        return $formattedCollection;
    }

    public function getAllMatchesListV2($data)
    {
        $timezone = $data['timezone'] ?? 'Asia/Dhaka';
        $limit = 5;

        $messyCollection = $this->matchQuery->getAllMatchesListV2Query('LIVE', $limit, $timezone);
        $limit -= $messyCollection->count();

        if ($limit) {
            $upcomingMatches = $this->matchQuery->getAllMatchesListV2Query('UPCOMING', $limit, $timezone);
            $messyCollection = $messyCollection->merge($upcomingMatches);
            $limit -= $upcomingMatches->count();
        }

        if ($limit) {
            $recentMatches = $this->matchQuery->getAllMatchesListV2Query('RECENT', $limit, $timezone);
            $messyCollection = $messyCollection->merge($recentMatches);
        }

        $formattedCollection = collect();

        foreach ($messyCollection as $messyObj) {
            $formattedObj = collect();

            $formattedObj->put('fixture_id', $messyObj->id);
            $formattedObj->put('tournament_id', $messyObj->tournament_id);
            $formattedObj->put('match_no', "Individual");
            $formattedObj->put('tournament_city', $messyObj->tournament->city ?? "");
            $formattedObj->put('tournament_name', $messyObj->tournament->tournament_name ?? "");
            $formattedObj->put('round_type', ucwords(strtolower(str_replace('-', ' ', $messyObj->round_type))) ?? "");
            $formattedObj->put('fixture_type', $messyObj->fixture_type ?? "KNOCKOUT");
            $formattedObj->put('is_visible', $messyObj->home_team_id and $messyObj->away_team_id);

            if ($messyObj->round_type == 'GROUP LEAGUE') {
                $formattedObj->put('round_type', $messyObj->leagueGroup->league_group_name ?? "");
            }

            if ($messyObj->tournament_id) {
                //              formatting match number
                $numberFormatter = new \NumberFormatter('en_US', \NumberFormatter::ORDINAL);
                $formattedObj->put('match_no', $numberFormatter->format($messyObj->match_no) ?? "");
            }

            $formattedObj->put('batting_team_id', $messyObj->home_team_id);
            $formattedObj->put('bowling_team_id', $messyObj->away_team_id);
            $formattedObj->put('batting_team_name', $messyObj->homeTeam->team_name ?? $messyObj->temp_team_one_name);
            $formattedObj->put('bowling_team_name', $messyObj->awayTeam->team_name ?? $messyObj->temp_team_two_name);
            $formattedObj->put('batting_team_logo', $messyObj->homeTeam->team_logo ?? "");
            $formattedObj->put('bowling_team_logo', $messyObj->awayTeam->team_logo ?? "");
            $formattedObj->put('batting_team_state', "");
            $formattedObj->put('bowling_team_state', "");
            $matchStatus = "NOT_DECIDED";
            $matchResult = 'Not decided yet';

            $tossWinnerTeam = "";
            $battingTeamRuns = $messyObj->home_team_runs ?? 0;
            $battingTeamOvers = $messyObj->home_team_overs ?? 0.0;
            $battingTeamWickets = $messyObj->home_team_wickets ?? 0;
            $bowlingTeamRuns = $messyObj->away_team_runs ?? 0;
            $bowlingTeamOvers = $messyObj->away_team_overs ?? 0.0;
            $bowlingTeamWickets = $messyObj->away_team_wickets ?? 0;

            if ($messyObj->is_match_start or $messyObj->is_match_finished) {

                if ($messyObj->toss_winner_team_id == $messyObj->home_team_id) {
                    $tossWinnerTeam = $messyObj->homeTeam->team_name;
                } else {
                    $tossWinnerTeam = $messyObj->awayTeam->team_name;
                }

                if (($messyObj->toss_winner_team_id == $messyObj->home_team_id and $messyObj->team_elected_to == 'BOWL')
                    or ($messyObj->toss_winner_team_id == $messyObj->away_team_id and $messyObj->team_elected_to == 'BAT')
                ) {
                    $formattedObj->put('batting_team_id', $messyObj->away_team_id);
                    $formattedObj->put('batting_team_name', $messyObj->awayTeam->team_name);
                    $formattedObj->put('batting_team_logo', $messyObj->awayTeam->team_logo);
                    $formattedObj->put('bowling_team_id', $messyObj->home_team_id);
                    $formattedObj->put('bowling_team_name', $messyObj->homeTeam->team_name);
                    $formattedObj->put('bowling_team_logo', $messyObj->homeTeam->team_logo);
                }
            }

            if ($messyObj->is_match_start == 1 && $messyObj->is_match_finished == 0) {
                $matchStatus = "LIVE";
                $matchResult = $tossWinnerTeam . ' won the toss and elected to ' . ucfirst(strtolower($messyObj->team_elected_to));

                if ($messyObj->total_innings) {
                    $firstInnings = $messyObj->innings->where('is_first_innings', 1)->first();
                    $secondInnings = $messyObj->innings->where('is_first_innings', 0)->first();
                    $firstInningsStart = $firstInnings->innings_status == 'Started' ? 1 : 0;
                    $secondInningsStart = $secondInnings->innings_status == 'Started' ? 1 : 0;
                    $firstInningsFinished = $firstInnings->innings_status == 'Finished' ? 1 : 0;
                    $secondInningsFinished = $secondInnings->innings_status == 'Finished' ? 1 : 0;

                    if ($firstInningsStart or $firstInningsFinished) {
                        $state = $battingTeamRuns . "/" . $battingTeamWickets . ' (' . $battingTeamOvers . ' Over)';
                        $formattedObj->put('batting_team_state', $state);
                    } else {
                        $formattedObj->put('batting_team_state', 'Yet to bat');
                    }

                    if ($firstInningsFinished and ($secondInningsStart or $secondInningsFinished)) {
                        $state = $bowlingTeamRuns . '/' . $bowlingTeamWickets . ' (' . $bowlingTeamOvers . ' Over)';
                        $formattedObj->put('bowling_team_state', $state);

                        $runsNeed = ($battingTeamRuns + 1) - $bowlingTeamRuns;
                        $ballsLeft = (floor($messyObj->match_overs * 6) - $this->overstoBall($messyObj->away_team_overs));
                        $bowlingTeamWicketsLeft = $this->matchQuery->getBattingTeamWicketsLeft($messyObj->id, $formattedObj->get('bowling_team_id')) ?? 0;
                        $matchResult = "";
                        if ($ballsLeft > 0 and $runsNeed > 0 and $bowlingTeamWicketsLeft > 1) {
                            // $obj['match_statement'] = $obj['team_name'] . ' Need ' . ($obj['target'] - $liveMatch->total_runs) . ' runs in ' . (floor($checkMatch->match_overs * 6) - $this->overstoBall($liveMatch->total_overs)) . ' balls';
                            $matchResult = $formattedObj->get('bowling_team_name') . ' needs ' . $runsNeed . ' runs from ' . $ballsLeft . ' balls.';
                        } else if ($bowlingTeamRuns > $battingTeamRuns) {
                            $matchResult = $formattedObj['bowling_team_name'] . ' won by ' . ($bowlingTeamWicketsLeft - 1) . ' wickets';
                        } else if ($bowlingTeamRuns == $bowlingTeamRuns) {
                            $matchResult = 'Draw';
                        } else {
                            $matchResult = $formattedObj['batting_team_name'] . ' won by ' . ((int)($battingTeamRuns) - (int)($bowlingTeamRuns)) . ' runs';
                        }
                        $formattedObj->put('match_result', $matchResult);
                    } else if ($firstInningsFinished and !$secondInningsStart) {
                        $formattedObj->put('bowling_team_state', 'Yet to bat');
                        $formattedObj->put('match_result', "Innings Break");
                    }

                    if ($firstInningsFinished and $secondInningsFinished) {
                        $formattedObj->put('match_result', $messyObj->match_final_result);
                    }

                    if($messyObj['events']){
                        $event = json_decode($messyObj['events']);
                        $formattedObj->put('match_result', $event->event_name . ' Break');
                    }
                }
            } else if ($messyObj->is_match_start == 1 && $messyObj->is_match_finished == 1) {
                $battingTeamState = $messyObj->home_team_runs . '/' . $$messyObj->home_team_wickets . ' (' . $$messyObj->home_team_overs . ' Over)';
                $bowlingTeamState = $messyObj->away_team_runs . '/' . $$messyObj->away_team_wickets . ' (' . $$messyObj->away_team_overs . ' Over)';
                $formattedObj->put('batting_team_state', $battingTeamState);
                $formattedObj->put('bowling_team_state', $bowlingTeamState);
                $matchStatus = "RECENT";
                $matchResult = $messyObj->match_final_result;
            } else if (($messyObj->home_team_id and $messyObj->away_team_id) and $messyObj->match_date != '1111-11-11') {
                $matchStatus = "UPCOMING";
                $matchDate = $messyObj->match_date ?? Carbon::now('UTC')->format('Y-m-d');
                $startTime = $messyObj->start_time ?? Carbon::now('UTC')->format('H:i:s');

                $date = $matchDate . ' ' . $startTime;
                $date = Carbon::createFromFormat('Y-m-d H:i:s', $date, 'UTC');
                $date = $date->setTimezone($timezone);
                $matchDateTime = $date->format('D, F d, h:i A');
                $matchResult = $matchDateTime;
            }

            if ($messyObj->is_match_postponed or ($messyObj->type == 'RECENT' and !$messyObj->is_match_start)) {
                $matchResult = 'Postponed';
            } else if ($messyObj->is_match_cancelled) {
                $matchResult = 'Cancelled';
            }

            $formattedObj->put('match_result', $matchResult);
            $formattedObj->put('match_status', $matchStatus);
            $formattedCollection->push($formattedObj);
        }

        return $formattedCollection;
    }

    public function getAllLiveMatchesList($data)
    {
        $messyCollection = $this->matchQuery->getAllLiveMatchesListQuery($data);
        $timezone = $data['timezone'] ?? 'Asia/Dhaka';
        $formattedCollection = collect();

        foreach ($messyCollection as $messyObj) {
            $formattedObj = collect();

            //          match general information
            $formattedObj->put('fixture_id', $messyObj->id);
            $formattedObj->put('tournament_id', $messyObj->tournament_id);
            //          formatting match number
            $formattedObj->put('round_type', $messyObj->round_type ?? "");
            $formattedObj->put('match_no', 'Individual');
            $formattedObj->put('tournament_name', "");

            if ($messyObj->tournament_id) {
                //              formatting match number
                $formattedObj->put('tournament_name', $messyObj->tournament->tournament_name);
                $numberFormatter = new \NumberFormatter('en_US', \NumberFormatter::ORDINAL);
                $formattedObj->put('match_no', $messyObj->match_no ? (string)$numberFormatter->format($messyObj->match_no) : "NA");

                if ($messyObj->round_type == 'GROUP LEAGUE') {
                    $formattedObj->put('round_type', $messyObj->leagueGroup->league_group_name ?? "");
                } else if ($messyObj->round_type != 'IPL') {
                    $formattedObj->put('round_type', ucwords(strtolower(str_replace('-', ' ', $messyObj->round_type))) ?? "");
                }
            }

            $tossWinnerTeam = "";
            $battingTeamRuns = $messyObj->home_team_runs ?? 0;
            $battingTeamOvers = $messyObj->home_team_overs ?? 0.0;
            $battingTeamWickets = $messyObj->home_team_wickets ?? 0;
            $bowlingTeamRuns = $messyObj->away_team_runs ?? 0;
            $bowlingTeamOvers = $messyObj->away_team_overs ?? 0.0;
            $bowlingTeamWickets = $messyObj->away_team_wickets ?? 0;

            $formattedObj->put('batting_team_state', "");
            $formattedObj->put('bowling_team_state', "");
            $formattedObj->put('match_result', "");

//          formatting match_date and time
            $matchDate = $messyObj->match_date ?? Carbon::now('UTC')->format('Y-m-d');
            $startTime = $messyObj->start_time ?? Carbon::now('UTC')->format('H:i:s');

            $date = $matchDate . ' ' . $startTime;
            $date = Carbon::createFromFormat('Y-m-d H:i:s', $date, 'UTC');
            $date = $date->setTimezone($timezone);
            $matchDate = date('D, F d', strtotime($date));
            $startTime = date('h:i A', strtotime($date));

            $formattedObj->put('match_date', $matchDate);
            $formattedObj->put('start_time', $startTime);

            if ($messyObj->toss_winner_team_id == $messyObj->home_team_id) {
                $tossWinnerTeam = $messyObj->homeTeam->team_name;
            } else {
                $tossWinnerTeam = $messyObj->awayTeam->team_name;
            }

            $formattedObj->put('batting_team_id', $messyObj->home_team_id);
            $formattedObj->put('batting_team_name', $messyObj->homeTeam->team_name ?? $messyObj->temp_team_one_name);
            $formattedObj->put('batting_team_logo', $messyObj->homeTeam->team_logo ?? "");
            $formattedObj->put('bowling_team_id', $messyObj->away_team_id);
            $formattedObj->put('bowling_team_name', $messyObj->awayTeam->team_name ?? $messyObj->temp_team_two_name);
            $formattedObj->put('bowling_team_logo', $messyObj->awayTeam->team_logo ?? "");

            if (($messyObj->toss_winner_team_id == $messyObj->home_team_id and $messyObj->team_elected_to == 'BOWL')
                or ($messyObj->toss_winner_team_id == $messyObj->away_team_id and $messyObj->team_elected_to == 'BAT')
            ) {
                $formattedObj->put('batting_team_id', $messyObj->away_team_id);
                $formattedObj->put('batting_team_name', $messyObj->awayTeam->team_name);
                $formattedObj->put('batting_team_logo', $messyObj->awayTeam->team_logo);
                $formattedObj->put('bowling_team_id', $messyObj->home_team_id);
                $formattedObj->put('bowling_team_name', $messyObj->homeTeam->team_name);
                $formattedObj->put('bowling_team_logo', $messyObj->homeTeam->team_logo);
            }

            if ($messyObj->is_match_start) {
                $matchResult = $tossWinnerTeam . ' won the toss and elected to ' . ucfirst(strtolower($messyObj->team_elected_to));
                $formattedObj->put('match_result', $matchResult);

                if ($messyObj->total_innings) {
                    $firstInnings = $messyObj->innings->where('is_first_innings', 1)->first();
                    $secondInnings = $messyObj->innings->where('is_first_innings', 0)->first();
                    $firstInningsStart = $firstInnings->innings_status == 'Started' ? 1 : 0;
                    $secondInningsStart = $secondInnings->innings_status == 'Started' ? 1 : 0;
                    $firstInningsFinished = $firstInnings->innings_status == 'Finished' ? 1 : 0;
                    $secondInningsFinished = $secondInnings->innings_status == 'Finished' ? 1 : 0;

                    $formattedObj->put('batting_team_state', 'Yet to bat');
                    if ($firstInningsStart or $firstInningsFinished) {
                        $state = $battingTeamRuns . "/" . $battingTeamWickets . ' (' . $battingTeamOvers . ' Over)';
                        $formattedObj->put('batting_team_state', $state);
                    }

                    if ($firstInningsFinished and ($secondInningsStart or $secondInningsFinished)) {
                        $state = $bowlingTeamRuns . '/' . $bowlingTeamWickets . ' (' . $bowlingTeamOvers . ' Over)';
                        $formattedObj->put('bowling_team_state', $state);

                        $runsNeed = ($battingTeamRuns + 1) - $bowlingTeamRuns;
                        $ballsLeft = (floor($messyObj->match_overs * 6) - $this->overstoBall($messyObj->away_team_overs));
                        $bowlingTeamWicketsLeft = $this->matchQuery->getBattingTeamWicketsLeft($messyObj->id, $formattedObj->get('bowling_team_id')) ?? 0;
                        $matchResult = "";

                        if ($ballsLeft > 0 and $runsNeed > 0 and $bowlingTeamWicketsLeft > 1) {
                            $matchResult = $formattedObj->get('bowling_team_name') . ' needs ' . $runsNeed . ' runs from ' . $ballsLeft . ' balls.';
                        } else if ($bowlingTeamRuns > $battingTeamRuns) {
                            $matchResult = $formattedObj['bowling_team_name'] . ' won by ' . ($bowlingTeamWicketsLeft - 1) . ' wickets';
                        } else if ($bowlingTeamRuns == $bowlingTeamRuns) {
                            $matchResult = 'Draw';
                        } else {
                            $matchResult = $formattedObj['batting_team_name'] . ' won by ' . ((int)($battingTeamRuns) - (int)($bowlingTeamRuns)) . ' runs';
                        }

                        $formattedObj->put('match_result', $matchResult);
                    } else if ($firstInningsFinished and !$secondInningsStart) {
                        $formattedObj->put('bowling_team_state', 'Yet to bat');
                        $formattedObj->put('match_result', "Innings Break");
                    }

                    if ($firstInningsFinished and $secondInningsFinished) {
                        $formattedObj->put('match_result', $messyObj->match_final_result);
                    }
                }

                if($messyObj['events']){
                    $event = json_decode($messyObj['events']);
                    $formattedObj->put('match_result', $event->event_name . ' Break');
                }
            }

            $formattedCollection->push($formattedObj);
        }

        return [
            'current_page' => $messyCollection->currentPage(),
            'last_page' => $messyCollection->lastPage(),
            'matches' => $formattedCollection,
        ];
    }

    public function getMatchesListByType($data)
    {
        $timezone = $data['timezone'] ?? 'Asia/Dhaka';

        if ($data['match_type'] === 'UPCOMING') {
            $data['is_match_start'] = 0;
            $data['is_match_finished'] = 0;
        } else if ($data['match_type'] === 'RECENT') {
            $data['is_match_start'] = 1;
            $data['is_match_finished'] = 1;
        }

        $messyCollection = $this->matchQuery->getMatchesListByTypeQuery($data);
        $currentPage = $messyCollection->currentPage();
        $lastPage = $messyCollection->lastPage();

        $messyCollection->map(function($item) use($timezone){
            if($item->match_date != '1111-11-11'){
                $matchDate = $item->match_date;
                $startTime = $item->start_time ?? Carbon::now('UTC')->format('H:i:s');
                $date = $matchDate . ' ' . $startTime;
                $date = Carbon::createFromFormat('Y-m-d H:i:s', $date, 'UTC')->setTimezone($timezone);
                $item->match_date = $date->format('Y-m-d');
                $item->start_time = $date->format('H:i:s');
            }
        });

        $messyCollection = $messyCollection->groupBy('match_date');

        $formattedCollection = collect();

        //        return $messyCollection;
        foreach ($messyCollection as $key => $messyChildCollection) {

            $formattedChildCollection = collect();
            $formattedChildObj = collect();

            $day = '';
            $date = '';

            if ($key == '1111-11-11' and $data['match_type'] === 'UPCOMING') {
                $day = 'Not Decided Yet';
                $date = '';
            } else {
                $date = Carbon::parse($key);
                if ($date->isToday()) {
                    $day = 'Today';
                } else if ($date->isYesterday()) {
                    $day = 'Yesterday';
                } else if ($date->isTomorrow()) {
                    $day = 'Tomorrow';
                } else {
                    $day = $date->format('l');
                }
                $date = $date->format('d M, Y');
            }

            $formattedChildCollection->put('day', $day);
            $formattedChildCollection->put('date', $date);

            foreach ($messyChildCollection as $messyObj) {
                $formattedObj = collect();
                //              match general information
                $formattedObj->put('fixture_id', $messyObj->id);
                $formattedObj->put('tournament_id', $messyObj->tournament_id);
                $formattedObj->put('match_no', 'Individual');
                $formattedObj->put('round_type', $messyObj->round_type ?? "");
                $formattedObj->put('tournament_name', $messyObj->tournament->tournament_name ?? "");
                $formattedObj->put('is_visible', $messyObj->home_team_id and $messyObj->away_team_id);

                if ($messyObj->tournament_id) {
                    //                  formatting match number
                    $numberFormatter = new \NumberFormatter('en_US', \NumberFormatter::ORDINAL);
                    $formattedObj->put('match_no', $messyObj->match_no ? (string)$numberFormatter->format($messyObj->match_no) : "NA");

                    if ($messyObj->round_type == 'GROUP LEAGUE') {
                        $formattedObj->put('round_type', $messyObj->leagueGroup->league_group_name ?? "");
                    } else if ($messyObj->round_type != 'IPL') {
                        $formattedObj->put('round_type', ucwords(strtolower(str_replace('-', ' ', $messyObj->round_type))) ?? "");
                    }
                }

                $formattedObj->put('batting_team_id', $messyObj->home_team_id);
                $formattedObj->put('batting_team_name', $messyObj->homeTeam->team_name ?? $messyObj->temp_team_one_name);
                $formattedObj->put('batting_team_logo', $messyObj->homeTeam->team_logo ?? "");
                $formattedObj->put('bowling_team_id', $messyObj->away_team_id);
                $formattedObj->put('bowling_team_name', $messyObj->awayTeam->team_name ?? $messyObj->temp_team_two_name);
                $formattedObj->put('bowling_team_logo', $messyObj->awayTeam->team_logo ?? "");
                $battingTeamState = "";
                $bowlingTeamState = "";
                $matchFinalResult = 'Postponed';

                if ($data['match_type'] == 'UPCOMING') {
                    $matchFinalResult = $messyObj->start_time ? date('h:i A', strtotime($messyObj->start_time)) : "";
                } else if ($messyObj->is_match_cancelled) {
                    $matchFinalResult = 'Cancelled';
                } else if (
                    $messyObj->is_match_finished and
                    ($messyObj->toss_winner_team_id == $messyObj->home_team_id and $messyObj->team_elected_to == 'BOWL')
                    or ($messyObj->toss_winner_team_id == $messyObj->away_team_id and $messyObj->team_elected_to == 'BAT')
                ) {
                    $formattedObj->put('batting_team_id', $messyObj->away_team_id);
                    $formattedObj->put('batting_team_name', $messyObj->awayTeam->team_name);
                    $formattedObj->put('batting_team_logo', $messyObj->awayTeam->team_logo);
                    $formattedObj->put('bowling_team_id', $messyObj->home_team_id);
                    $formattedObj->put('bowling_team_name', $messyObj->homeTeam->team_name);
                    $formattedObj->put('bowling_team_logo', $messyObj->homeTeam->team_logo);
                    $battingTeamState = $messyObj->home_team_runs . "/" . $messyObj->home_team_wickets . ' (' . $messyObj->home_team_overs . ' Over)';
                    $bowlingTeamState = $messyObj->away_team_runs . "/" . $messyObj->away_team_wickets . ' (' . $messyObj->away_team_overs . ' Over)';
                    $matchFinalResult = $messyObj->match_final_result;
                }

                $formattedObj->put('batting_team_state', $battingTeamState);
                $formattedObj->put('bowling_team_state', $bowlingTeamState);
                $formattedObj->put('match_result', $matchFinalResult);

                $formattedChildObj->push($formattedObj);
            }

            $formattedChildCollection->put('matches', $formattedChildObj);
            $formattedCollection->push($formattedChildCollection);
        }

        return [
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'matches_by_date' => $formattedCollection,
        ];
    }

    public function getAllMatchesByGroup($gId, $data)
    {

        $matches = $this->matchQuery->getAllMatchesByGroupQuery($gId, $data);

        $formatedMaches = [];
        foreach ($matches as $key => $value) {

            // $ob = [
            //     'date' => date("j M Y", strtotime($key)),
            //     "matches" => $value
            // ];
            $ob = [];
            if ($key == '1111-11-11') {
                $ob['date'] = "Upcoming";
            } else {
                $ob['date'] = date("j M Y", strtotime($key));
            }

            $ob['matches'] = $value;

            array_push($formatedMaches, $ob);
        }
        return $formatedMaches;
    }

    public function getTournamentMatches($data)
    {

        $matches = $this->matchQuery->getTournamentMatches($data);

        $formatedMaches = [];

        foreach ($matches as $date => $match) {
            $ob = [];

            $ob['day'] = "";
            $ob['date'] = "";

            if ($date == "1111-11-11") {
                $ob['day'] = "--";
                $ob['date'] = "Not decided yet";
            } else {
                $ob['day'] = date('Y-m-d') == $date ? "Today" : date("l", strtotime($date));
                $ob['date'] = date("j M Y", strtotime($date));
            }

            $ob['matchesDetails'] = array();
            foreach ($match as $m) {
                $matchOb = [];
                $matchOb['id'] = $m->id;
                $numberFormatter = new \NumberFormatter('en_US', \NumberFormatter::ORDINAL);
                $match_no = $m->match_no ? $numberFormatter->format($m->match_no) . ' Match, ' : '(Not set yet), ';
                $matchOb['match_status'] = $match_no . ucwords(strtolower(str_replace('-', ' ', $m->round_type))) . ' ' . ($m->leagueGroup ? ', ' . $m->leagueGroup->league_group_name : '') . ' . ' . ($m->ground ? $m->ground->city : '');
                $matchOb['match_date'] = date('Y-m-d', strtotime($m->match_date));
                $matchOb['match_final_results'] = $m->match_final_result;
                $matchOb['start_time'] = date('h:i A', strtotime($m->start_time));

                if ($m->is_match_start == 1) {
                    $t1 = $m->home_team_overs;
                    $t2 = $m->away_team_overs;

                    $t1balls = $t1 - floor($t1);
                    $t2balls = $t2 - floor($t2);

                    $matchOb['home_team_runs'] = $t1 || $t1balls || $m->home_team_runs ? ($m->home_team_runs . '/' . $m->home_team_wickets . ' (' . $m->home_team_overs . ' overs)') : 'Yet to bat';
                    $matchOb['away_team_runs'] = $t2 || $t2balls || $m->away_team_runs ? ($m->away_team_runs . '/' . $m->away_team_wickets . ' (' . $m->away_team_overs . ' overs)') : 'Yet to bat';
                }

                $matchOb['home_team_name'] = $m->home_team;
                $matchOb['away_team_name'] = $m->away_team;

                if ($m->home_team == null && $m->away_team == null) {

                    $matchOb['home_team_name'] = [
                        "id" => null,
                        "team_name" => $m->temp_team_one_name,
                        "team_logo" => null,
                        "team_short_name" => $m->temp_team_one_name,
                    ];
                    $matchOb['away_team_name'] = [
                        "id" => null,
                        "team_name" => $m->temp_team_two_name,
                        "team_logo" => null,
                        "team_short_name" => $m->temp_team_two_name,
                    ];
                }

                array_push($ob['matchesDetails'], $matchOb);
            }

            array_push($formatedMaches, $ob);
        }

        return $formatedMaches;
    }

    public function getTournamentMatchesByStatus($data)
    {

        $matches = $this->matchQuery->getTournamentMatchesByStatus($data);

        $formatedMaches = [];

        foreach ($matches as $date => $match) {
            $ob = [];

            $ob['day'] = "";
            $ob['date'] = "";

            if ($date == "1111-11-11") {
                $ob['day'] = "--";
                $ob['date'] = "Not decided yet";
            } else {
                $ob['day'] = date('Y-m-d') == $date ? "Today" : date("l", strtotime($date));
                $ob['date'] = date("j M Y", strtotime($date));
            }

            $ob['matchesDetails'] = array();
            foreach ($match as $m) {
                $matchOb = [];
                $matchOb['id'] = $m->id;
                $numberFormatter = new \NumberFormatter('en_US', \NumberFormatter::ORDINAL);
                $match_no = $m->match_no ? $numberFormatter->format($m->match_no) . ' Match, ' : '(Not set yet), ';
                $matchOb['match_status'] = $match_no . ucwords(strtolower(str_replace('-', ' ', $m->round_type))) . ' ' . ($m->leagueGroup ? ', ' . $m->leagueGroup->league_group_name : '') . ' . ' . ($m->ground ? $m->ground->city : '');
                $matchOb['match_date'] = date('Y-m-d', strtotime($m->match_date));
                $matchOb['match_final_results'] = $m->match_final_result;
                $matchOb['start_time'] = date('h:i A', strtotime($m->start_time));

                if ($m->is_match_start == 1) {
                    $t1 = $m->home_team_overs;
                    $t2 = $m->away_team_overs;

                    $t1balls = $t1 - floor($t1);
                    $t2balls = $t2 - floor($t2);

                    $matchOb['home_team_runs'] = $t1 || $t1balls || $m->home_team_runs ? ($m->home_team_runs . '/' . $m->home_team_wickets . ' (' . $m->home_team_overs . ' overs)') : 'Yet to bat';
                    $matchOb['away_team_runs'] = $t2 || $t2balls || $m->away_team_runs ? ($m->away_team_runs . '/' . $m->away_team_wickets . ' (' . $m->away_team_overs . ' overs)') : 'Yet to bat';
                }

                $matchOb['flag'] = "UPCOMING";

                if ($m->is_match_start == 1 && $m->is_match_finished == 0) {
                    $matchOb['flag'] = "LIVE";
                } else if ($m->is_match_start == 1 && $m->is_match_finished == 1) {
                    $matchOb['flag'] = "RECENT";
                }

                $matchOb['home_team_name'] = $m->home_team;
                $matchOb['away_team_name'] = $m->away_team;

                if ($m->home_team == null && $m->away_team == null) {

                    $matchOb['home_team_name'] = [
                        "id" => null,
                        "team_name" => $m->temp_team_one_name,
                        "team_logo" => null,
                        "team_short_name" => $m->temp_team_one_name,
                    ];
                    $matchOb['away_team_name'] = [
                        "id" => null,
                        "team_name" => $m->temp_team_two_name,
                        "team_logo" => null,
                        "team_short_name" => $m->temp_team_two_name,
                    ];
                }

                array_push($ob['matchesDetails'], $matchOb);
            }

            array_push($formatedMaches, $ob);
        }

        return $formatedMaches;
    }

    public function formateMatches($collection)
    {
        $formatedMaches = [];
        foreach ($collection as $date => $match) {
            $ob = [];

            $ob['day'] = "";
            $ob['date'] = "";

            if ($date == "1111-11-11") {
                $ob['day'] = "--";
                $ob['date'] = "Not decided yet";
            } else {
                $ob['day'] = date('Y-m-d') == $date ? "Today" : date("l", strtotime($date));
                $ob['date'] = date("j M Y", strtotime($date));
            }

            $ob['matchesDetails'] = array();
            foreach ($match as $m) {
                $matchOb = [];
                $matchOb['id'] = $m->id;
                $numberFormatter = new \NumberFormatter('en_US', \NumberFormatter::ORDINAL);
                $match_no = $m->match_no ? $numberFormatter->format($m->match_no) . ' Match, ' : '(Not set yet), ';
                $matchOb['match_status'] = $match_no . ucwords(strtolower(str_replace('-', ' ', $m->round_type))) . ' ' . ($m->leagueGroup ? ', ' . $m->leagueGroup->league_group_name : '') . ' . ' . ($m->ground ? $m->ground->city : '');
                $matchOb['match_date'] = date('Y-m-d', strtotime($m->match_date));
                $matchOb['match_final_results'] = $m->match_final_result;
                $matchOb['start_time'] = date('h:i A', strtotime($m->start_time));

                if ($m->is_match_start == 1) {
                    $t1 = $m->home_team_overs;
                    $t2 = $m->away_team_overs;

                    $t1balls = $t1 - floor($t1);
                    $t2balls = $t2 - floor($t2);

                    $matchOb['home_team_runs'] = $t1 || $t1balls || $m->home_team_runs ? ($m->home_team_runs . '/' . $m->home_team_wickets . ' (' . $m->home_team_overs . ' overs)') : 'Yet to bat';
                    $matchOb['away_team_runs'] = $t2 || $t2balls || $m->away_team_runs ? ($m->away_team_runs . '/' . $m->away_team_wickets . ' (' . $m->away_team_overs . ' overs)') : 'Yet to bat';
                }


                $matchOb['home_team_name'] = $m->home_team;
                $matchOb['away_team_name'] = $m->away_team;

                if ($m->home_team == null && $m->away_team == null) {

                    $matchOb['home_team_name'] = [
                        "id" => null,
                        "team_name" => $m->temp_team_one_name,
                        "team_logo" => null,
                        "team_short_name" => $m->temp_team_one_name,
                    ];
                    $matchOb['away_team_name'] = [
                        "id" => null,
                        "team_name" => $m->temp_team_two_name,
                        "team_logo" => null,
                        "team_short_name" => $m->temp_team_two_name,
                    ];
                }

                array_push($ob['matchesDetails'], $matchOb);
            }

            array_push($formatedMaches, $ob);
        }

        return $formatedMaches;
    }

    public function getTournamentMatchesTwo($data)
    {
        $data['firstArr'] = 1;
        $data['secondArr'] = 0;

        $formatedMaches = [];

        // $matches = $this->matchQuery->getTournamentMatchesTwo($data);
        // $length = $matches['length'];

        // if ($length > 0) {
        //     $arrOne = $this->formateMatches($matches['collection']);
        //     array_push($formatedMaches, ...$arrOne);
        // }

        // $lastId = $matches['lastId'];

        // if ($length < 10) {

        //     $data['firstArr'] = 0;
        //     $data['secondArr'] = 1;
        //     $matchesTwo = $this->matchQuery->getTournamentMatchesTwo($data);
        //     $arrTwo = $this->formateMatches($matchesTwo['collection']);
        //     array_push($formatedMaches, ...$arrTwo);

        //     $lastId = $matchesTwo['lastId'];
        // }

        // $ob = [
        //     "data" => $formatedMaches,
        //     "lastId" => $lastId,
        // ];

        // return $ob;
    }

    public function getSingleMatch($id)
    {
        // $data['user_id'] = Auth::id();
        $data = $this->matchQuery->getSingleMatchQuery($id);

        $data['events'] = json_decode($data['events']);
        return $data;
    }


    public function getMatchOfficial($data)
    {
        $str = isset($data['str']) ? $data['str'] : '';
        return $this->matchQuery->getMatchOfficialQuery($data['type'], $str);
    }

    public function createAnIndividualMatch($data)
    {
        $attributes = [
            'fixture_type' => $data['fixture_type'],
            'home_team_id' => $data['team_a'],
            'away_team_id' => $data['team_b'],
            'match_date' => now(),
            'start_time' => now(),
            'created_at' =>  now(),
            'updated_at' =>  now(),
        ];

        $fixture = $this->tournamentScheduleQuery->createFixtures($attributes, 'single');

        if ($data['fixture_type'] == 'CLUB_CHALLENGE' and isset($data['challenge_request_id'])) {
            $this->clubQuery->updateClubChallengeRequestQuery($data['challenge_request_id'], ['fixture_id' => $fixture['id']]);
        }

        $match = $this->matchQuery->getGeneratedMatchById($fixture['id']);

        $match['home_team_name'] = $match->homeTeam->team_name ?? "";
        $match['home_team_logo'] = $match->homeTeam->team_logo ?? "";
        $match['away_team_name'] = $match->awayTeam->team_name ?? "";
        $match['away_team_logo'] = $match->awayTeam->team_logo ?? "";

        unset($match['homeTeam']);
        unset($match['awayTeam']);

        return $match;
    }

    public function startAMatch($data)
    {
        $ob = [
            'id' => $data['fixture_id'],
            'match_overs' => isset($data['match_overs']) ? ($data['match_overs']) : 20,
            'match_type' => isset($data['match_type']) ? ($data['match_type']) : 'LIMITED OVERS',
            'overs_per_bowler' => isset($data['overs_per_bowler']) ? ($data['overs_per_bowler']) : 4,
            'ground_id' => $data['ground_id'],
        ];

        $fixture = $this->matchQuery->getFixtureById($data['fixture_id']);

        if ($fixture['match_date'] == '1111-11-11') {
            $ob['match_date'] = now();
        }

        if (!$fixture['start_time']) {
            $ob['start_time'] = now();
        }

        return $this->matchQuery->updateMatchQuery($ob);
    }

    public function endInnings($data)
    {
        $ob = [
            'id' => $data['inning_id'],
            'innings_status' => 'Finished',
        ];
        return $this->matchQuery->endInningsQuery($ob);
    }

    public function getEndMatchStatus($id)
    {
        $fixture = $this->matchQuery->getFixtureById($id, 'innings');
        $flag = 1;
        $ob = ['match_final_result' => ''];
        $innings = $this->matchQuery->getInningsByFixerIdAndteamIdQuery($id, $fixture['away_team_id'], null);
        $fixture['home_team_id'] = $innings['bowling_team_id'];
        $fixture['away_team_id'] = $innings['batting_team_id'];
        if ($innings['is_first_innings'] == 1) {
            $fixture['home_team_id'] = $innings['batting_team_id'];
            $fixture['away_team_id'] = $innings['bowling_team_id'];
        }
        $details = [];
        foreach ($fixture['innings'] as $value) {
            $obb = [
                'team_name' => $value['batting_team']['team_name'],
                'total_runs' => $value['total_runs'],
                'total_wickets' => $value['total_wickets'],
                'total_overs' => $value['total_overs'],
            ];
            array_push($details, $obb);
        }
        $ob = [
            'match_final_result' => '',
            'match_details' => $details
        ];

        if ($fixture['away_team_runs'] > $fixture['home_team_runs']) {

            $tem = $this->matchQuery->getTeamById($fixture['away_team_id']);
            $bowlingTeamWicketsLeft = $this->matchQuery->getBattingTeamWicketsLeft($fixture['id'], $fixture['away_team_id']) ?? 0;
            $ob['match_final_result'] = $tem['team_name'] . ' won by ' . ($bowlingTeamWicketsLeft - 1) . ' wickets';
        } else if ($fixture['away_team_runs'] == $fixture['home_team_runs']) {
            $ob['match_final_result'] = 'Draw';
        } else {
            if ($fixture['away_team_runs'] >= 0) {
                $tem = $this->matchQuery->getTeamById($fixture['home_team_id']);
                $ob['match_final_result'] = $tem['team_name'] . ' won by ' . ((int)($fixture['home_team_runs']) - (int)($fixture['away_team_runs'])) . ' runs';
            }
        }

        return $ob;
    }

    public function endMatch($data)
    {
        $battingTeam = $bowlingTeam = [];

        $fixture = $this->matchQuery->getFixtureById($data['fixture_id'], 'innings');
        $innings = $this->matchQuery->getInningsByFixerIdAndteamIdQuery($data['fixture_id'], $fixture['away_team_id'], null);
        $tournament = null;
        if ($fixture['tournament_id']) {
            $tournament = $this->matchQuery->getTournamentById($fixture['tournament_id']);
        }


        $matchDetails = [
            'id' => $data['fixture_id'],
            'is_match_finished' => 1,
            'match_final_result' => '',
            'is_match_draw' => 0,
            'match_winner_team_id' => NULL,
            'match_loser_team_id' => NULL,
        ];

        $battingTeam = $bowlingTeam = [
            'tournament_type' => $tournament['tournament_type'] ?? null,
            'tournament_id' => $fixture['tournament_id'] ?? null,
            'league_group_id' => $fixture['league_group_id'] ?? null,
            'team_id' => null,
            'fixture_id' => $fixture['id'],
            'matchPlayed' => 1,
            'won' => 0,
            'loss' => 0,
            'draw' => 0,
            'points' => 0,
        ];

        $flag = 1;
        $battingTeam['team_id'] = $innings['bowling_team_id'];
        $bowlingTeam['team_id'] = $innings['batting_team_id'];
        $battingTeam['league_group_team_id'] = $innings['league_group_bowling_team_id'];
        $bowlingTeam['league_group_team_id'] = $innings['league_group_team_id'];

        if ($innings['is_first_innings'] == 1) {
            $flag = 0;
            $battingTeam['team_id'] = $innings['batting_team_id'];
            $bowlingTeam['team_id'] = $innings['bowling_team_id'];
            $battingTeam['league_group_team_id'] = $innings['league_group_team_id'];
            $bowlingTeam['league_group_team_id'] = $innings['league_group_bowling_team_id'];
        }

        if ($fixture['away_team_runs'] > $fixture['home_team_runs']) {
            $team = $this->matchQuery->getTeamById($bowlingTeam['team_id']);
            $bowlingTeamWicketsLeft = $this->matchQuery->getBattingTeamWicketsLeft($fixture['id'], $bowlingTeam['team_id']) ?? 0;
            $matchDetails['match_winner_team_id'] = $bowlingTeam['team_id'];
            $matchDetails['match_loser_team_id'] = $battingTeam['team_id'];
            $bowlingTeam['won'] = 1;
            $battingTeam['loss'] = 1;
            $bowlingTeam['points'] = 2;
            $battingTeam['points'] = 0;
            $matchDetails['match_final_result'] = $team['team_name'] . ' won by ' . ($bowlingTeamWicketsLeft - 1) . ' wickets';
        } else if ($fixture['away_team_runs'] == $fixture['home_team_runs']) {
            $battingTeam['draw'] = 1;
            $battingTeam['draw'] = 1;
            $battingTeam['points'] = 1;
            $battingTeam['points'] = 1;
            $matchDetails['is_match_draw'] = 1;
            $matchDetails['match_final_result'] = 'Draw';
        } else {
            $team = $this->matchQuery->getTeamById($fixture['home_team_id']);
            $matchDetails['match_winner_team_id'] = $battingTeam['team_id'];
            $matchDetails['match_loser_team_id'] = $bowlingTeam['team_id'];
            $battingTeam['won'] = 1;
            $bowlingTeam['loss'] = 1;
            $battingTeam['points'] = 2;
            $bowlingTeam['points'] = 0;
            $matchDetails['match_final_result'] = $team['team_name'] . ' won by ' . ((int)($fixture['home_team_runs']) - (int)($fixture['away_team_runs'])) . ' runs';
        }

        $this->matchQuery->updateMatchQuery($matchDetails);
        // create related match rank row for group matches
        if ($fixture['tournament_id'] and $fixture['league_group_id']) {
            $this->matchQuery->storeRanksQuery($battingTeam);
            $this->matchQuery->storeRanksQuery($bowlingTeam);
        }

        if ($flag == 1) {
            $this->matchQuery->updateInnings([
                "id" => $innings['id'],
                "innings_status" => "Finished"
            ]);
        } else {
            $ing = $this->matchQuery->getInningsByFixerIdAndteamIdQuery($data['fixture_id'], $fixture['home_team_id'], 0);
            $this->matchQuery->updateInnings([
                "id" => $ing['id'],
                "innings_status" => "Finished"
            ]);
        }

        // assigning team to next round fixtures
        if ($fixture['tournament_id']) {
            $this->assignTeamsToNextRoundFixtures($data['fixture_id']);
            if ($fixture['round_type'] == 'FINAL') {
                $this->tournamentScheduleQuery->updateTournamentQuery(['id' => $tournament['id'], 'is_finished' => 1]);
            }
        }

        return $this->matchQuery->getFixtureById($data['fixture_id']);
    }

    public function storeManOftheMatch($data)
    {
        $ob = [
            'id' => $data['fixture_id'],
            'player_of_the_match' => $data['player_id'],
        ];

        $ob1 = [
            'from' => null,
            'to' => $data['player_id'],
            'msg' => 'Congratulations! You have been selected for Man of the Match.',
            'type' => 'man_of_the_match',
            'player_id' => $data['player_id'],
        ];

        $this->notificationQuery->sendNotificationGlobalMethod($ob1);
        return $this->matchQuery->updateMatchQuery($ob);
    }

    public function getEditableMatchDetails($data)
    {
        $timezone = $data['timezone'] ?? 'Asia/Dhaka'; // 'Asia/Dhaka' or 'GMT+6'
        $obj = $this->matchQuery->getEditableMatchDetailsQuery($data);

        $matchDate = $obj->match_date == '1111-11-11' ? Carbon::now('UTC')->format('Y-m-d') : $obj->match_date;
        $startTime = $obj->start_time ?? Carbon::now('UTC')->format('H:i:s');
        $date = $matchDate . ' ' . $startTime;
        $date = Carbon::createFromFormat('Y-m-d H:i:s', $date, 'UTC');
        $date = $date->setTimezone($timezone);
        $obj->match_date = date('D, F d Y', strtotime($date));
        $obj->start_time = date('h:i A', strtotime($date));

        $officials = $obj->othersOfficials;

        if ($officials->count()) {
            foreach ($officials as $official) {
                $official->type = ucfirst(strtolower($official->pivot->official_type));
                unset($official->pivot);
            }
        }

        return $obj;
    }

    public function updateEditableMatchDetails($data)
    {
        $fixtureId = $data['fixture_id'];
        $inningsId = $data['inning_id'] ?? null;
        $power_play_data = isset($data['power_play_data']) ?? null;
        $timezone = $data['timezone'] ?? 'Asia/Dhaka';
        $matchDate = $data['match_date'] ?? Carbon::now($timezone)->format('Y-m-d');
        $startTime = $data['start_time'] ?? Carbon::now($timezone)->format('H:i:s');

        // datetime
        $date = $matchDate . ' ' .$startTime;
        $date = Carbon::createFromFormat('Y-m-d H:i:s', $date, $timezone);
        $date = $date->setTimezone('UTC');
        $data['match_date'] = date('Y-m-d', strtotime($date));
        $data['start_time'] = date('H:i:s', strtotime($date));

        //power play
        if ($power_play_data and $inningsId) {
            $this->matchQuery->updateMatchPowerPlaysQuery($fixtureId, $inningsId, $power_play_data);
        }

        unset($data['fixture_id']);
        unset($data['inning_id']);
        unset($data['match_no']);
        unset($data['timezone']);
        return $this->matchQuery->updateEditableMatchDetailsQuery($fixtureId, Auth::id(), $data);
    }

    public function updateMatchPowerPlays($data)
    {
        // return $data;
        $fixtureId = $data['id'];
        // return $this->matchQuery->updateMatchPowerPlaysQuery($fixtureId);
        $power_play_data = $data['power_play_data'];
        $inningsId = $data['inning_id'] ?? null;

        // Log::channel('slack')->info('d', ['d' => $data]);
        return $this->matchQuery->updateMatchPowerPlaysQuery($fixtureId, $inningsId, $power_play_data);
    }

    public function endAnInnings($data)
    {

        $ob = [
            'id' => $data['fixture_id'],
            'match_overs' => 20,
            'match_type' => 'LIMITED OVERS',
            'is_match_start' => 1,
            // 'power_play' => $data['power_play'],
            // 'ground_id' => $data['ground_id'],
        ];
        if (isset($data['reason'])) {
            $ob['reason'] = $data['reason'];
        }
        return $this->matchQuery->updateMatchQuery($ob);
    }

    public function startAInnings($data)
    {


        $innings_details = $this->matchQuery->getInningsDetails($data['inning_id']);
        $ob = [
            'tournament_id' => $data['tournament_id'],
            'inning_id' => $data['inning_id'],
            'fixture_id' => $data['fixture_id'],
            'league_group_id' => $innings_details['league_group_id'],
            'league_group_team_id' => $innings_details['league_group_team_id'],
            'team_id' => $innings_details['batting_team_id'],
            'batter_id' => $data['striker_id'],
            'is_on_strike' => 1,
        ];
        $bat_one = $this->matchQuery->createNewBatterQuery($ob);
        $ob['batter_id'] = $data['non_striker_id'];
        $ob['is_on_strike'] = 0;
        $bat_two = $this->matchQuery->createNewBatterQuery($ob);
        unset($ob['batter_id']);
        $ob['bowler_id'] = $data['bowler_id'];
        $ob['team_id'] = $innings_details['bowling_team_id'];
        $ob['league_group_team_id'] = $innings_details['league_group_bowling_team_id'];
        $ob['is_on_strike'] = 1;
        $bowler = $this->matchQuery->createNewBowlerQuery($ob);
        $ob = [
            'initial_striker_id' => $data['striker_id'],
            'initial_non_striker_id' => $data['non_striker_id'],
            'initial_keeper_id' => $data['initial_keeper_id'],
            'initial_bowler_id' => $data['bowler_id'],
            'innings_status' => 'Started',
            'id' => $data['inning_id'],
        ];
        $this->matchQuery->updateInnings($ob);
        $overOb = [
            'inning_id' => $innings_details['id'],
            'bowler_id' => $data['bowler_id'],
        ];
        $this->matchQuery->createOver($overOb);
        $wicketOb = [
            'fixture_id' => $innings_details['fixture_id'],
            'team_id' => $innings_details['bowling_team_id'],
            'player_id' => $data['initial_keeper_id'],
            'is_wicket_keeper' => 1,
        ];
        $this->matchQuery->changeWicketkeeperQuery($wicketOb);

        return [
            'batter_one' => $bat_one,
            'batter_two' => $bat_two,
            'bowler' => $bowler,
        ];
    }

    public function changeWicketkeeper($data)
    {
        $wicketOb = [
            'fixture_id' => $data['fixture_id'],
            'team_id' => $data['team_id'],
            'player_id' => $data['player_id'],
            'is_wicket_keeper' => 1,
        ];
        return $this->matchQuery->changeWicketkeeperQuery($wicketOb);
    }

    public function addMatchOfficial($data)
    {
        $len = sizeof($data);
        $array_ob = [];
        if ($len > 0) {
            for ($i = 0; $i < $len; $i++) {
                // return  $data[$i][];
                $ob = [
                    'fixture_id' => $data[$i]['fixture_id'],
                    'official_type' => $data[$i]['official_type'],
                    'user_id' => $data[$i]['user_id'],
                ];
                if (isset($data[$i]['position'])) {
                    $ob['position'] = $data[$i]['position'];
                }
                array_push($array_ob, $ob);
                $this->matchQuery->addMatchOfficialQuery($ob);
            }
        }
        return $array_ob;
    }

    public function getMatchOfficial_by_fixture($id)
    {
        $alldata = $this->matchQuery->getMatchOfficial_by_fixtureQuery($id);
        $formated_values = $alldata->groupBy('official_type');
        $upmper_array = [];
        $temp = [];
        foreach ($formated_values as $key => $val) {
            $upmper_array[$key] = $val;
            if ($key == 'UMPIRE') {
                $position_wise_sort = $formated_values[$key]->groupBy('position');
                for ($i = 1; $i <= 4; $i++) {
                    if (isset($position_wise_sort[$i]))
                        array_push($temp, $position_wise_sort[$i][0]);
                }
            }
        }
        if (!isset($upmper_array["UMPIRE"])) {
            $upmper_array["UMPIRE"] = [];
        }
        if (!isset($upmper_array["SCORER"])) {
            $upmper_array["SCORER"] = [];
        }
        if (!isset($upmper_array["COMMENTATOR"])) {
            $upmper_array["COMMENTATOR"] = [];
        }
        if (!isset($upmper_array["REFEREE"])) {
            $upmper_array["REFEREE"] = [];
        }
        if (!isset($upmper_array["STREAMER"])) {
            $upmper_array["STREAMER"] = [];
        }
        if (isset($upmper_array['UMPIRE'])) {
            $upmper_array['UMPIRE'] = $temp;
        }
        return $upmper_array;
    }

    public function removeMatchOfficial($data)
    {
        $check = $this->matchQuery->isMatchOwnerQuery(Auth::id(), $data['fixture_id']);
        if (!$check) {
            return response()->json(['msg' => "You are not authenticated user."], 401);
        }
        $ob = [
            'fixture_id' => $data['fixture_id'],
            'id' => $data['id'],
        ];
        return $this->matchQuery->removeMatchOfficialQuery($ob);
    }

    public function addMatchToss($data)
    {
        //        {"fixture_id":2427,"toss_winner_team_id":16,"toss_losser_team_id":17,"team_elected_to":"Elected to bat"}
        $toss_winner_team = $this->teamQuery->getTeamByIdQuery($data['toss_winner_team_id']);
        $toss_losser_team = $this->teamQuery->getTeamByIdQuery($data['toss_losser_team_id']);

        if (!$this->checkIsSquadReady($data['toss_winner_team_id'])) {
            $message = $toss_winner_team['team_name'] . "'s needs minimum 3 players in there main squad to play a match!";
            return response()->json(
                ['message' => $message],
                401
            );
        }
        if (!$this->checkIsSquadReady($data['toss_losser_team_id'])) {
            $message = $toss_losser_team['team_name'] . "'s needs minimum 3 players in there main squad to play a match!";
            return response()->json(
                ['message' => $message],
                401
            );
        }

        $ob = [
            'toss_winner_team_id' => $data['toss_winner_team_id'],
            'team_elected_to' => $data['team_elected_to'] != 'Elected to ball' ? 'BAT' : 'BOWL',
            'id' => $data['fixture_id'],
            'is_match_start' => 1,
        ];


        $fixture_data = $this->matchQuery->isMatchOwnerQuery(1, $data['fixture_id']);
        //        Log::channel('slack')->info('add toss api', ['ob' => $ob]);
        // innnings create start
        $first_team = $data['toss_winner_team_id'];
        $second_team = $data['toss_losser_team_id'];
        if ($data['team_elected_to'] == 'Elected to ball') {
            $first_team = $data['toss_losser_team_id'];
            $second_team = $data['toss_winner_team_id'];
        }
        //        Log::channel('slack')->info('add toss api', ['data' => $data]);
        $league_group_teams1 = $league_group_teams2 = null;
        if ($fixture_data['fixture_type'] == 'GROUP') {
            $league_group_teams1 = $this->matchQuery->getLeagueGroupTeamsQuery($fixture_data['league_group_id'], $first_team);
            $league_group_teams2 = $this->matchQuery->getLeagueGroupTeamsQuery($fixture_data['league_group_id'], $second_team);
        }

        $ininngs_data = [
            'tournament_id' => $fixture_data['tournament_id'],
            'league_group_id' => $fixture_data['league_group_id'] ?? null,
            'fixture_id' => $fixture_data['id'],
            'batting_team_id' => $first_team,
            'home_team_id' => $first_team,
            'bowling_team_id' => $second_team,
            'away_team_id' => $second_team,
            'is_first_innings' => 1,
            'league_group_team_id' => $league_group_teams1['id'] ?? null,
            'league_group_bowling_team_id' => $league_group_teams2['id'] ?? null,
        ];
        $ininngs2nd_data = [
            'tournament_id' => $fixture_data['tournament_id'],
            'league_group_id' => $fixture_data['league_group_id'] ?? null,
            'fixture_id' => $fixture_data['id'],
            'batting_team_id' => $second_team,
            'home_team_id' => $second_team,
            'bowling_team_id' => $first_team,
            'away_team_id' => $first_team,
            'is_first_innings' => 0,
            'league_group_team_id' => $league_group_teams2['id'] ?? null,
            'league_group_bowling_team_id' => $league_group_teams1['id'] ?? null
        ];


        //  $this->matchQuery->updateMatchQuery($ob);

        $first_innings = $this->matchQuery->createInningsQuery($ininngs_data);
        $second_innings = $this->matchQuery->createInningsQuery($ininngs2nd_data);
        // $ininngs_data['is_first_innings'] = 0;
        // $ininngs_data['league_group_team_id'] = $league_group_teams2['id'];
        //  $this->matchQuery->createInningsQuery($ininngs_data);

        // innnings create end
        $this->matchQuery->addMatchTossQuery($ob);
        $this->matchQuery->updateMatchPowerPlaysQuery($data['fixture_id']);
        $this->matchQuery->addPlayingXIFromMainSquad($data['toss_winner_team_id'], $toss_winner_team, $data['fixture_id']);
        $this->matchQuery->addPlayingXIFromMainSquad($data['toss_losser_team_id'], $toss_losser_team, $data['fixture_id']);

        if ($fixture_data['tournament_id']) {
            $tournament = $this->matchQuery->getTournamentById($fixture_data['tournament_id']);
            if ($tournament and !$tournament['is_start']) {
                $this->tournamentScheduleQuery->updateTournamentQuery(['id' => $fixture_data['tournament_id'], 'is_start' => 1]);
            }
        }

        return $first_innings;
    }

    public function getPlyaingEleven($data)
    {
        return $this->matchQuery->getPlyaingElevenQuery($data);
    }

    public function getAllPlayerOfMatch($data)
    {
        return $this->matchQuery->getAllPlayerOfMatchQuery($data);
    }


    public function getSingleMatchWithAllDetails($data)
    {
        $timezone = $data['timezone'] ?? 'Asia/Dhaka';
        $messyObj = $this->matchQuery->getSingleMatchWithAllDetailsQuery($data['fixture_id']);
        $formattedObj = collect();

        // formatting object
        $formattedObj->put('home_team_id', $messyObj->home_team_id);
        $formattedObj->put('away_team_id', $messyObj->away_team_id);
        $formattedObj->put('home_team_name', $messyObj->home_team->team_name ?? "");
        $formattedObj->put('away_team_name', $messyObj->away_team->team_name ?? "");
        $formattedObj->put('home_team_short_name', $messyObj->home_team->team_short_name ?? "");
        $formattedObj->put('away_team_short_name', $messyObj->away_team->team_short_name ?? "");
        $formattedObj->put('tournament', $messyObj->tournament->tournament_name ?? "");
        // formatting date time
        $matchDate = $messyObj->match_date;
        $startTime = $messyObj->start_time ?? Carbon::now('UTC')->format('H:i:s');
        $date = $matchDate . ' ' . $startTime;
        $date = Carbon::createFromFormat('Y-m-d H:i:s', $date, 'UTC')->setTimezone($timezone);
        $formattedObj->put('match_date', $date->format('D, F d'));
        $formattedObj->put('match_time', $date->format('h:i A'));
        $formattedObj->put('player_of_the_match', $messyObj->player_of_the_match);

        //venue
        $total_tournament_grounds = $messyObj->tournament->total_grounds ?? 0;
        if ($messyObj->ground) {
            $formattedObj->put('venue', $messyObj->ground->ground_name);
            $formattedObj->put('venue_city', $messyObj->ground->city);
            $formattedObj->put('venue_capacity', $messyObj->ground->capacity);
        } else if ($total_tournament_grounds  == 1) {
            $ground = $messyObj->tournament->grounds->first();
            $formattedObj->put('venue', $ground->ground_name);
            $formattedObj->put('venue_city', $ground->city);
            $formattedObj->put('venue_capacity', $ground->capacity);
        } else {
            $formattedObj->put('venue', "NA");
            $formattedObj->put('venue_city', "NA");
            $formattedObj->put('venue_capacity', "NA");
        }

        // formatting toss result
        $result = 'Not tossed yet';
        if ($messyObj->toss_winner_team_id == $messyObj->home_team_id) {
            $result = $messyObj->home_team->team_name . ' elected to ' . ucwords(strtolower($messyObj->team_elected_to));
        } else if ($messyObj->toss_winner_team_id == $messyObj->away_team_id) {
            $result = $messyObj->away_team->team_name . ' elected to ' . ucwords(strtolower($messyObj->team_elected_to));
        }
        $formattedObj->put('toss_result', $result);

        // formatting total matches number
        $match = 'Individual Match';
        if ($messyObj->fixture_type != 'TEAM_CHALLENGE' and $messyObj->fixture_type != 'CLUB_CHALLENGE') {
            $numberFormatter = new \NumberFormatter('en_US', \NumberFormatter::ORDINAL);
            $match = $numberFormatter->format($messyObj->match_no) . ' Match';
            $match .= ', ' . ucwords(strtolower(str_replace('-', ' ', $messyObj->round_type)));
            if ($messyObj->round_type == 'GROUP LEAGUE' and $messyObj->fixture_type == 'GROUP' and $messyObj->leagueGroup) {
                $match .= ', ' . $messyObj->leagueGroup->league_group_name;
            }
        }

        $formattedObj->put('match', $match);

        // formatting umpires
        $umpiresData = $messyObj->umpires->toArray();

        $umpires = 'NA';
        if (count($umpiresData)) {
            $umpires = '';
            foreach ($umpiresData as $index => $item) {
                $umpires .= ucfirst($item['first_name']) . ' ' . ucfirst($item['last_name']);

                if ($index !== array_key_last($umpiresData)) {
                    $umpires .= ', ';
                }
            }
        }

        $formattedObj->put('umpires', $umpires ?? 'NA');

        return $formattedObj;
    }

    public function delDelivery($data)
    {
        $totalDeliveriesAndOvers = $this->matchQuery->totalBowlByOver($data['over_id'], $data['inning_id']);

        $getLastDelivery = $this->matchQuery->delDeliveryQuery($data['fixture_id'], $data['over_id']);

        if ($getLastDelivery) {
            if ($this->isStrikeChange($getLastDelivery) == true) {

                $ob = [
                    'inning_id' => $getLastDelivery['inning_id'],
                    'batter_id' => $getLastDelivery['batter_id']
                ];

                $this->matchQuery->changeStrikeQuery($ob);
            }

            if ($getLastDelivery['wicket_type'] != null and $getLastDelivery['wicket_type'] != 'RETIRED_OUT') {
                if ($getLastDelivery['wicket_type'] == 'RUN_OUT') {
                    $ob = [
                        'inning_id' => $getLastDelivery['inning_id'],
                        'batter_id' => $getLastDelivery['run_out_batter']
                    ];


                    $this->matchQuery->makeUndoOutAndCalculate($ob);
                } else if ($getLastDelivery['wicket_type'] == 'ACTION_OUT') {
                    $ob = [
                        'inning_id' => $getLastDelivery['inning_id'],
                        'batter_id' => $getLastDelivery['non_striker_id']
                    ];

                    $this->matchQuery->makeUndoOutAndCalculate($ob);
                } else if ($getLastDelivery['wicket_type'] == 'TIME_OUT' && $getLastDelivery['is_time_out'] != null) {
                    $ob = [
                        'inning_id' => $getLastDelivery['inning_id'],
                        'batter_id' => $getLastDelivery['is_time_out'],
                        'type' => 'is_time_out'
                    ];
                    $this->matchQuery->makeUndoOutAndCalculate($ob);
                } else if ($getLastDelivery['wicket_type'] == 'OBSTRUCTING_FIELD' && $getLastDelivery['is_obstructing_field'] != null) {
                    $ob = [
                        'inning_id' => $getLastDelivery['inning_id'],
                        'batter_id' => $getLastDelivery['is_obstructing_field'],
                        'type' => 'is_obstructing_field'
                    ];
                    $this->matchQuery->makeUndoOutAndCalculate($ob);
                }
                else {
                    $ob = [
                        'inning_id' => $getLastDelivery['inning_id'],
                        'batter_id' => $getLastDelivery['batter_id']
                    ];
                    $this->matchQuery->makeUndoOutAndCalculate($ob);
                }
            }
            if ($getLastDelivery['ball_type'] == 'DB') {
                if ($getLastDelivery['is_retired'] != null) {
                    $ob = [
                        'inning_id' => $getLastDelivery['inning_id'],
                        'batter_id' => $getLastDelivery['is_retired'],
                        'type' => 'is_retired'
                    ];
                    $this->matchQuery->makeUndoOutAndCalculateOtherType($ob);
                } else if ($getLastDelivery['is_absent'] != null) {
                    $ob = [
                        'inning_id' => $getLastDelivery['inning_id'],
                        'batter_id' => $getLastDelivery['is_absent'],
                        'type' => 'is_absent'
                    ];
                    $this->matchQuery->makeUndoOutAndCalculateOtherType($ob);
                } else if ($getLastDelivery['is_obstructing_field'] != null) {
                    $ob = [
                        'inning_id' => $getLastDelivery['inning_id'],
                        'batter_id' => $getLastDelivery['is_obstructing_field'],
                        'type' => 'is_obstructing_field'
                    ];
                    $this->matchQuery->makeUndoOutAndCalculateOtherType($ob);
                } else {
                }
            }
        }

        if ($totalDeliveriesAndOvers['delivery_count'] <= 1 && $totalDeliveriesAndOvers['over_count'] > 1) {
            $previousOverDelivery = $this->matchQuery->getPreviousOverDeliveryAndDeleteCurrentOne($data['fixture_id'], $data['inning_id'], $data['over_id']);

            $ob = [
                'inning_id' => $data['inning_id'],
                'batter_id' => $previousOverDelivery['batter_id'],
            ];

            if ($this->isStrikeChange($previousOverDelivery) == true) {
                $ob['batter_id'] = $previousOverDelivery['non_striker_id'];
            }

            $this->matchQuery->changeStrikeQuery($ob);
        }


        $this->calculateDeliveries($data['inning_id']);


        return $this->getMatchLiveScore($data['inning_id']);
    }

    public function changeStrike($data)
    {

        return $this->matchQuery->changeStrikeQuery($data);
        // return $this->getMatchLiveScore($data['inning_id']);


    }

    public function changeABatsman($data)
    {
        $lastBatsman = $this->matchQuery->getLastMatchLivebatsman($data['inning_id'], $data['non_striker_id']);
        $obbb = [
            'tournament_id' => $lastBatsman['tournament_id'],
            'inning_id' => $data['inning_id'],
            'fixture_id' => $lastBatsman['fixture_id'],
            'league_group_id' => $lastBatsman['league_group_id'],
            'league_group_team_id' => $lastBatsman['league_group_team_id'],
            'team_id' => $lastBatsman['team_id'],
            'batter_id' => $data['batter_id'],
        ];

        $this->matchQuery->createNewBatterQuery($obbb);
        if ($data['new_batter_is_on_strike']) {
            $ob = [
                'inning_id' => $data['inning_id'],
                'batter_id' => $data['batter_id'],
            ];

            $this->matchQuery->changeStrikeQuery($ob);
        }
        return $this->getMatchLiveScore($data['inning_id']);
    }

    public function getMatchInnings($fixture_id)
    {

        return $this->matchQuery->getMatchInningsQuery($fixture_id);
    }

    public function getPanaltyOrBonusRuns($data)
    {
        return $this->matchQuery->getPanaltyOrBonusRunsQuery($data);
    }

    public function removePanaltyOrBonusRuns($data)
    {
        return $this->matchQuery->removePanaltyOrBonusRunsQuery($data);
    }

    public function storePanalty($data)
    {
        $innings = $this->matchQuery->getInningsDetails($data['inning_id']);
        $ob1 = [
            'reason' => $data['reason'],
            'inning_id' => $data['inning_id'],
            'type' => $data['type'],
            'runs' => $data['run'],
            'team_id' => $innings->batting_team_id,
            'league_group_id' => $innings->league_group_id,
            'tournament_id' => $innings->tournament_id,
            'league_group_team_id' => $innings->league_group_team_id,
            'fixture_id' => $innings->fixture_id,
        ];
        return $this->matchQuery->storePanalty($ob1);
    }



    public function insertBreakData($data)
    {
        $ob1 = [
            'fixture_id' => $data['fixture_id'],
            'event_name' => $data['events']['event_name'],
            'event_duration' => $data['events']['event_duration'],
            'comment' => $data['events']['comment'],
            'type' => $data['events']['type']
        ];
        $store = $this->matchQuery->addMatchEventQuery($ob1);
        $ob = [
            'events' => json_encode($data['events'], true),
            'id' => $data['fixture_id']
        ];
        if ($data['events'] && $data['events']['type'] == 'break') {
            $ob['is_break'] = 1;
        }
        $update = $this->matchQuery->addMatchTossQuery($ob);
        if ($update == 1) {
            return response()->json(['msg' => "Break start."], 200);
        } else {
            return response()->json(['msg' => "Match not found."], 400);
        }
    }

    public function breakStop($data)
    {
        $ob = [
            'events' => null,
            'id' => $data['fixture_id'],
            'is_break' => 0
        ];
        $update = $this->matchQuery->addMatchTossQuery($ob);
        if ($update == 1) {
            return response()->json(['msg' => "Break stoped."], 200);
        } else {
            return response()->json(['msg' => "Match not found."], 400);
        }
    }

    public function closestCoordinate($data)
    {
        $allCoordinates = $this->matchQuery->allCoordinatesByType($data['batsman_type']);
        return $allCoordinates;
        $ref = array($data['x_coordinate'], $data['y_coordinate']);
        $distances = [];
        foreach ($allCoordinates as $index => $value) {
            $a = array($value->x_coordinate, $value->y_coordinate);
            $res = $this->distance($a, $ref);
            array_push($distances, $res);
        }
        asort($distances);
        // return $allCoordinates[$distances[0][]]
        foreach ($distances as $key => $value) {
            $result = $allCoordinates[$key];
            return $result;
        }
    }

    public function getFieldCoordinate($data)
    {
        $allCoordinates = $this->matchQuery->allCoordinatesByType();
        return $allCoordinates;
    }

    public function distance($a, $b)
    {
        list($lat1, $lon1) = $a;
        list($lat2, $lon2) = $b;

        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        return $miles;
    }

    public function insertScorerNotes($data)
    {
        $ob1 = [
            'fixture_id' => $data['fixture_id'],
            'event_name' => $data['events']['event_name'],
            'event_duration' => $data['events']['event_duration'],
            'comment' => $data['events']['comment'],
            'type' => $data['events']['type']
        ];
        $store = $this->matchQuery->addMatchEventQuery($ob1);
        $ob = [
            'events' => json_encode($data['events'], true),
            'id' => $data['fixture_id']
        ];
        $update = $this->matchQuery->addMatchTossQuery($ob);
        if ($update == 1) {
            return response()->json(['msg' => "Scorer note added."], 200);
        } else {
            return response()->json(['msg' => "Match not found."], 400);
        }
    }

    public function storeFixtureMedia($data)
    {

        return $this->matchQuery->storeFixtureMediaQuery($data);
    }

    public function replaceBowler($data)
    {


        $overOb = [
            'inning_id' => $data['inning_id'],
            'bowler_id' => $data['bowler_id']
        ];
        $this->matchQuery->createReplaceOver($overOb);
        $innings_details = $this->matchQuery->getInningsDetails($data['inning_id']);
        $bowlerOb = [
            'inning_id' => $data['inning_id'],
            'is_on_strike' => 0,
        ];
        $this->matchQuery->updateNewBowlerQuery($bowlerOb);
        $obbb = [
            'tournament_id' => $innings_details['tournament_id'],
            'inning_id' => $data['inning_id'],
            'fixture_id' => $innings_details['fixture_id'],
            'league_group_id' => $innings_details['league_group_id'],
            'league_group_team_id' => $innings_details['league_group_bowling_team_id'],
            'team_id' => $innings_details['bowling_team_id'],
            'bowler_id' => $data['bowler_id'],
            'is_on_strike' => 1,
        ];

        $this->matchQuery->createNewBowlerQuery($obbb);

        // $this->startANewOver($new_over_ob);

        return $this->getMatchLiveScore($data['inning_id']);
    }

    public function startANewOver($data)
    {

        $overOb = [
            'inning_id' => $data['inning_id'],
            'bowler_id' => $data['bowler_id'],
        ];

        $this->matchQuery->createOver($overOb);
        $innings_details = $this->matchQuery->getInningsDetails($data['inning_id']);
        $bowlerOb = [
            'inning_id' => $data['inning_id'],
            'is_on_strike' => 0,
        ];
        $this->matchQuery->updateNewBowlerQuery($bowlerOb);
        $obbb = [
            'tournament_id' => $innings_details['tournament_id'],
            'inning_id' => $data['inning_id'],
            'fixture_id' => $innings_details['fixture_id'],
            'league_group_id' => $innings_details['league_group_id'],
            'league_group_team_id' => $innings_details['league_group_bowling_team_id'],
            'team_id' => $innings_details['bowling_team_id'],
            'bowler_id' => $data['bowler_id'],
            'is_on_strike' => 1,
        ];

        // new added
        if (isset($data['non_striker_id'])) {
            $ob = [
                'inning_id' => $data['inning_id'],
                'batter_id' => $data['non_striker_id']
            ];
            $this->matchQuery->changeStrikeQuery($ob);
        }

        return $this->matchQuery->createNewBowlerQuery($obbb);
    }

    public function setNextBatter($data)
    {
        $innings = $this->matchQuery->getInningsOfBattingTeamById($data['inning_id'], $data['team_id']);

        $newBatterDetails = $this->matchQuery->createNewBatterQuery([
            'tournament_id' => $innings['tournament_id'],
            'fixture_id' => $innings['fixture_id'],
            'league_group_id' => $innings['league_group_id'],
            'league_group_team_id' => $innings['league_group_team_id'],
            'inning_id' => $data['inning_id'],
            'team_id' => $data['team_id'],
            'batter_id' => $data['new_batter_id'],
        ]);

        if (isset($newBatterDetails) and $data['new_batter_is_on_strike']) {
            $this->matchQuery->changeStrikeQuery([
                'inning_id' => $data['inning_id'],
                'batter_id' => $data['new_batter_id'],
            ]);
        }

        return $newBatterDetails;
    }


    public function storeDelivery($data)
    {

        $commentary_text = '';

        $data['match_type'] = 'LIMITED OVERS';
        $new_batter_id = null;
        $new_batter_is_on_strike = null;
        $new_bowler_id = null;
        $wicket_type = $data['wicket_type'];
        $can_coming_from_retired = null;
        $is_innings_end = 0;
        $is_strike_change = 0;

        $innings_details = $this->matchQuery->getInningsDetails($data['inning_id']);


        if (isset($data['new_batter_id'])) {

            $new_batter_id = $data['new_batter_id'];
            if (isset($data['new_batter_is_on_strike'])) {

                $new_batter_is_on_strike = $data['new_batter_is_on_strike'];
                $is_strike_change = 1;
            }
            unset($data['new_batter_id']);
            unset($data['new_batter_is_on_strike']);
        }
        if (isset($data['can_coming_from_retired'])) {
            $can_coming_from_retired = $data['can_coming_from_retired'];
            unset($data['can_coming_from_retired']);
        }


        if (isset($data['new_bowler_id'])) {

            $new_bowler_id = $data['new_bowler_id'];
            unset($data['new_bowler_id']);
        }
        if (isset($data['is_innings_end'])) {

            $is_innings_end = $data['is_innings_end'];
            unset($data['is_innings_end']);
        }

        if (
            $wicket_type == 'RETIRED_HURT' ||
            $wicket_type == 'RETIRED' ||
            $wicket_type == 'ABSENT'
        ) {
            $data['wicket_type'] = null;
        }

        if (!isset($data['ball_type'])) {
            $data['ball_type'] = "LEGAL";
        }

        $delivery_data = $this->matchQuery->storeDeliveryQuery($data);

        $data['league_group_id'] = $innings_details['league_group_id'];
        $data['league_group_team_id'] = $innings_details['league_group_team_id'];
        $data['team_id'] = $innings_details['batting_team_id'];
        $is_batsman_change = 0;
        if ($wicket_type != null) {
            $is_batsman_change = 1;
            if ($wicket_type == 'RETIRED_HURT' || $wicket_type == 'RETIRED' || $wicket_type == 'RETIRED_OUT') {
                $this->matchQuery->makeRetiredOutAndCalculate($data['is_retired'], $can_coming_from_retired, $data, $wicket_type);
                if ($wicket_type == 'RETIRED_HURT' || $wicket_type == 'RETIRED') {
                    $data['retired_type'] = $wicket_type;
                }
            } else if ($wicket_type == 'ABSENT' || $wicket_type == 'TIME_OUT') {

                $this->matchQuery->makeAbsentOutAndCalculate($data, $wicket_type);
            } else if ($wicket_type == 'ACTION_OUT') {

                $this->matchQuery->makeOutAndCalculate($data['inning_id'], $data['non_striker_id'], $data, $wicket_type);
            } else if ($wicket_type == 'OBSTRUCTING_FIELD') {
                $this->matchQuery->makeObstructingFilendOutAndCalculate($data['is_obstructing_field'], $data, $wicket_type);
            } else if ($wicket_type == 'RUN_OUT') {
                $this->matchQuery->makeOutAndCalculate($data['inning_id'], $data['run_out_batter'], $data, $wicket_type);
            } else {

                $this->matchQuery->makeOutAndCalculate($data['inning_id'], $data['batter_id'], $data, $wicket_type);
            }
            if ($new_batter_id) {

                $lastBatsman = $this->matchQuery->getLastMatchLivebatsman($data['inning_id'], $data['batter_id']);
                $obbb = [
                    'tournament_id' => $data['tournament_id'],
                    'inning_id' => $data['inning_id'],
                    'fixture_id' => $data['fixture_id'],
                    'league_group_id' => $lastBatsman['league_group_id'],
                    'league_group_team_id' => $lastBatsman['league_group_team_id'],
                    'team_id' => $lastBatsman['team_id'],
                    'batter_id' => $new_batter_id,
                ];

                $this->matchQuery->createNewBatterQuery($obbb);
                if ($new_batter_is_on_strike == 1) {
                    $ob = [
                        'inning_id' => $data['inning_id'],
                        'batter_id' => $new_batter_id,
                    ];

                    $is_strike_change = 1;

                    $this->matchQuery->changeStrikeQuery($ob);
                }
            }
        }
        if ($this->isStrikeChange($data) == true) {

            $ob = [
                'inning_id' => $data['inning_id'],
                'batter_id' => $data['non_striker_id']
            ];

            $data['non_striker_id'] = $data['batter_id'];
            $data['batter_id'] = $ob['batter_id'];
            $is_strike_change = 1;
            $this->matchQuery->changeStrikeQuery($ob);
        }


        // $totalLEGALBowl = $this->matchQuery->totalLEGALBowl($data['over_id']);
        // if ($totalLEGALBowl > 5 and $is_innings_end == 0) {
        //     $ob = [
        //         'inning_id' => $data['inning_id'],
        //         'batter_id' => $data['non_striker_id']
        //     ];

        //     $this->matchQuery->changeStrikeQuery($ob);

        //     $new_over_ob = [
        //         'inning_id' => $data['inning_id'],
        //         'bowler_id' => $new_bowler_id,
        //     ];

        //     $this->startANewOver($new_over_ob);
        // }
        if ($data['ball_type'] != 'DB') {

            $this->calculateDeliveries($data['inning_id'], $delivery_data['id']);
        }
        if ($is_strike_change == 1) {
            $ob = [
                'id' => $delivery_data['id'],
                'is_strike_change' => 1,
            ];
            $this->matchQuery->updatetDeliveryQuery($ob);
        }

        return $this->getMatchLiveScore($data['inning_id'], $is_batsman_change);
    }


    public function getMatchLiveScore($innings_id, $is_batsman_change = 0)
    {

        $innings_details = $this->matchQuery->getInningsDetails($innings_id);

        $raw_batsmans = $this->matchQuery->getMatchLiveBatsman($innings_id);
        $bowler = $this->matchQuery->getMatchLivebowler($innings_id);
        $batting_team = [];
        $bowling_team = $bowler['team'];
        unset($bowler['team']);
        $batsmans = [];
        foreach ($raw_batsmans as $value) {
            $ob = $value;
            $batting_team = $value['team'];
            unset($ob['team']);
            array_push($batsmans, $ob);
        }


        if ($innings_details['innings_status'] == 'Finished') {
            $bowler['over_id'] = 0;
            $bowler['is_batsman_change'] = $is_batsman_change;
            $bowler['is_over_finished'] = 0;
            $bowler['over_details'] = [];
            $score_ob = [
                "total_runs" => (string)$innings_details['total_runs'],
                "total_wicket" => (string)$innings_details['total_wickets'],
                "total_over" => (string)$innings_details['total_overs'],
            ];

            return [
                'score' => $score_ob,
                'batsmans' => $batsmans,
                'bowler' => $bowler,
                'batting_team' => $batting_team,
                'bowling_team' => $bowling_team,
            ];
        }
        $score = $this->matchQuery->getMatchLiveScoreQuery($innings_id);

        $specialRuns = Panalty::select(DB::raw(
            "SUM(CASE WHEN type = 'BONUS' THEN runs ELSE 0 END) AS total_bonus_runs,
            SUM(CASE WHEN type = 'PENALTY' THEN runs ELSE 0 END) AS total_penalty_runs"
            ))
            ->where('inning_id', $innings_id)
            ->first();

        $bonusRuns = $specialRuns['total_bonus_runs'] ?? 0;
        $penaltyRuns = $specialRuns['total_penalty_runs'] ?? 0;

        $score['total_runs'] = (string)((int)$score['total_runs'] + (int)$bonusRuns - (int)$penaltyRuns);


        $getOver = $this->matchQuery->getOver($innings_id);
        $bowler['over_id'] = $getOver['id'];
        $bowler['over_number'] = $getOver['over_number'];
        // if($bowler['over_id']){

        $raw_over_details = $this->matchQuery->getMatchLiveOver_details($bowler['over_number'], $innings_id);
        $over_details = [];

        foreach ($raw_over_details as $value) {
            // kaj korte hobe
            $ob = [
                'runs' => 0,
                'ball_type' => $value['ball_type'],
                'circle_value' => 0,
                "down_circle_value" => ''
            ];
            $runs = $value['runs'];
            $perona = 0;
            if ($value['ball_type'] == "LEGAL") {

                $runs = $value['runs'] + $value['extras'];
                $perona = $value['runs'] + $value['extras'];
                $total_runs = $value['runs'] + $value['extras'];
                if ($value['wicket_type'] != null) {
                    $runs = "W";
                    $perona = "W";
                    $ob['circle_value'] = 'W';
                    if ($total_runs > 0) {
                        $ob['down_circle_value'] = $total_runs;
                        $perona = "W+$total_runs";
                    }
                }
                $ob['circle_value'] = $runs;
                if ($value['run_type'] == 'LB') {
                    $ob['down_circle_value'] = "LB";
                    if ($value['runs'] > 0) {
                        $runs = $value['runs'] . "+LB " . $value['extras'];
                    } else if ($value['extras'] > 0) {
                        $runs = "LB " . $value['extras'];
                    }
                } else if ($value['run_type'] == 'B') {
                    $ob['down_circle_value'] = "B";
                    if ($value['runs'] > 0) {
                        $runs = $value['runs'] . "+B " . $value['extras'];
                    } else if ($value['extras'] > 0) {
                        $runs = "B " . $value['extras'];
                    }
                }
            } else if ($value['ball_type'] == "WD") {
                $runs = "WD";
                $ob['down_circle_value'] = "WD";
                if ($value['wicket_type'] != null) {
                    $runs = "W";
                    $ob['circle_value'] = 'W';
                    if ($value['extras'] > 1) {


                        $extra = $value['extras'];
                        $extra--;
                        if ($extra > 0) {
                            $runs = $extra . "WD";
                            $ob['down_circle_value'] = $extra . "WD";
                        }
                    }
                } else if ($value['extras'] > 1) {


                    $extra = $value['extras'];
                    $extra--;
                    if ($extra > 0) {
                        $runs = "WD+$extra";
                        $ob['circle_value'] = $extra;
                    }
                }
            } else if ($value['ball_type'] == "NB") {
                $ob['down_circle_value'] = "NB";
                $runs = "NB";
                $total_runs = $value['runs'] + $value['extras'];
                $total_runs--;
                if ($value['wicket_type'] != null) {
                    $runs = "W";
                    $ob['circle_value'] = 'W';
                    if ($total_runs > 0) {
                        $runs = $total_runs . "NB";
                        $ob['down_circle_value'] = $total_runs . "NB";
                    }
                } else if ($total_runs > 0) {
                    $runs = $runs . "NB";
                    $ob['circle_value'] = $total_runs;
                    $ob['down_circle_value'] = "NB";
                }
                // if ($value['runs'] > 1) {
                //     $extra = $value['runs'];
                //     if ($extra > 0) {
                //         $ob['circle_value'] = $extra;
                //         $runs = "NB+$extra";
                //     }
                // }

            } else if ($value['ball_type'] == "DB") {
                if ($value['is_retired']) {
                    $ob['circle_value'] = "RTD";
                    $ob['down_circle_value'] = "W";
                    $runs = "RTD";
                } else if ($value['is_absent']) {
                    $ob['circle_value'] = "ABS";
                    $ob['down_circle_value'] = "W";
                    $runs = "ABS";
                } else if ($value['wicket_type'] == 'ACTION_OUT') {
                    $ob['circle_value'] = "ACO";
                    $ob['down_circle_value'] = "W";
                    $runs = "ABS";
                } else if ($value['is_time_out']) {
                    $ob['circle_value'] = "TOT";
                    $ob['down_circle_value'] = "W";
                    $runs = "TOT";
                } else {
                    $ob['circle_value'] = "DB";
                    $runs = "DB";
                }
                // $ob['down_circle_value'] = "DB";

            }

            // if ($value['wicket_type'] != null) {
            //     $runs = "W";
            //     $ob['down_circle_value'] = 'W';
            //     if ($value['wicket_type'] == 'RUN_OUT') {
            //         $totalRuns = $value['runs'] + $value['extras'];
            //         if ($totalRuns > 0) {
            //             $ob['circle_value'] = $totalRuns;
            //             $runs = "W+" . $totalRuns;
            //         }
            //     }
            // }

            $runs = (string)$runs;
            $ob['circle_value'] = (string)$ob['circle_value'];
            $ob['down_circle_value'] = (string)$ob['down_circle_value'];
            $ob['runs'] = $runs;
            array_push($over_details, $ob);
        }


        $over = floor($score['total_over'] / 6);
        $ball = (int)$score['total_over'] % 6;
        $score['total_over'] = "$over.$ball";
        if ($score['total_runs'] == null) (string)$score['total_runs'] = '0';
        if ($score['total_wicket'] == null) $score['total_wicket'] = '0';

        $bowler['over_details'] = $over_details;
        $totalLEGALBowl = $this->matchQuery->totalLEGALBowl($bowler['over_id']);
        $bowler['over_id'] = $getOver['id'];
        $bowler['is_batsman_change'] = $is_batsman_change;
        $bowler['is_over_finished'] = $totalLEGALBowl > 5 ? 1 : 0;
        $target = 0;
        $is_first_innings = $innings_details['is_first_innings'];
        if ($innings_details['is_first_innings'] == 0) {
            $anotherInnings = $this->matchQuery->getAnotherInningsDetails($innings_details['fixture_id']);
            $target = $anotherInnings['total_runs'];
        }

        $battingTeamWicketsLeft = $this->matchQuery->getBattingTeamWicketsLeft($innings_details['fixture_id'], $innings_details['batting_team_id']);

        $selectNewBatter = 0;

        if (count($batsmans) == 1) {
            $selectNewBatter = 1;
        }

        $res = [
            'batting_team_wickets_left' => $battingTeamWicketsLeft ?? 0,
            'score' => $score,
            'batsmans' => $batsmans,
            'bowler' => $bowler,
            'batting_team' => $batting_team,
            'bowling_team' => $bowling_team,
            'target' => $target,
            'power_play_type' => 'P1',
            'is_first_innings' => $is_first_innings,
            'selectNewBatter' => $selectNewBatter,
        ];

        // Log::channel('slack')->info('res', ['d' => $res]);
        return  $res;
    }

    public function getStreamMatchLiveScore($innings_id, $is_batsman_change = 0)
    {

        $innings_details = $this->matchQuery->getInningsDetails($innings_id, 'fixture');


        $toss_winner_team = $this->teamQuery->getTeamByIdQuery($innings_details['fixture']['toss_winner_team_id']);
        $toss = $toss_winner_team['team_name'] . " win the toss";
        $tourament_name = $innings_details['fixture']['tournament']['tournament_name'];
        $match_overs = $innings_details['fixture']['match_overs'];
        $raw_batsmans = $this->matchQuery->getMatchLiveBatsman($innings_id);
        $bowler = $this->matchQuery->getMatchLivebowler($innings_id);
        $batting_team = [];
        $bowling_team = $bowler['team'];
        unset($bowler['team']);
        $target = 0;
        $is_first_innings = $innings_details['is_first_innings'];
        if ($innings_details['is_first_innings'] == 0) {
            $anotherInnings = $this->matchQuery->getAnotherInningsDetails($innings_details['fixture_id']);
            $target = $anotherInnings['total_runs'];
        }
        $batsmans = [];
        foreach ($raw_batsmans as $value) {
            $ob = $value;
            $batting_team = $value['team'];
            unset($ob['team']);
            array_push($batsmans, $ob);
        }


        if ($innings_details['innings_status'] == 'Finished') {
            $bowler['over_id'] = 0;
            $bowler['is_batsman_change'] = $is_batsman_change;
            $bowler['is_over_finished'] = 0;
            $bowler['over_details'] = [];
            $score_ob = [
                "total_runs" => (string)$innings_details['total_runs'],
                "total_wicket" => (string)$innings_details['total_wickets'],
                "total_over" => (string)$innings_details['total_overs'],
            ];
            return [
                'score' => $score_ob,
                'batsmans' => $batsmans,
                'bowler' => $bowler,
                'batting_team' => $batting_team,
                'bowling_team' => $bowling_team,
                'target' => $target,
                'power_play_type' => 'P1',
                'toss' => $toss,
                'match_overs' => $match_overs,
                'tourament_name' => $tourament_name,
                'is_first_innings' => $is_first_innings,
            ];
        }
        $score = $this->matchQuery->getMatchLiveScoreQuery($innings_id);
        // if($s)


        $getOver = $this->matchQuery->getOver($innings_id);
        $bowler['over_id'] = $getOver['id'];
        $bowler['over_number'] = $getOver['over_number'];
        // if($bowler['over_id']){

        $raw_over_details = $this->matchQuery->getMatchLiveOver_details($bowler['over_number'], $innings_id);
        $over_details = [];

        foreach ($raw_over_details as $value) {
            $ob = [
                'runs' => 0,
                'ball_type' => $value['ball_type'],
                'circle_value' => 0,
                "down_circle_value" => ''
            ];
            $runs = $value['runs'];
            $perona = 0;
            if ($value['ball_type'] == "LEGAL") {

                $runs = $value['runs'] + $value['extras'];
                $perona = $value['runs'] + $value['extras'];
                $total_runs = $value['runs'] + $value['extras'];
                if ($value['wicket_type'] != null) {
                    $runs = "W";
                    $perona = "W";
                    $ob['circle_value'] = 'W';
                    if ($total_runs > 0) {


                        $ob['circle_value'] = $total_runs;
                        $ob['down_circle_value'] = 'W';
                        $perona = "W+$total_runs";
                    }
                }
                // $ob['circle_value'] = $runs;
                if ($value['run_type'] == 'LB') {
                    $ob['down_circle_value'] = "LB";
                    if ($value['runs'] > 0) {
                        $runs = $value['runs'] . "+LB " . $value['extras'];
                    } else if ($value['extras'] > 0) {
                        $runs = "LB " . $value['extras'];
                    }
                } else if ($value['run_type'] == 'B') {
                    $ob['down_circle_value'] = "B";
                    if ($value['runs'] > 0) {
                        $runs = $value['runs'] . "+B " . $value['extras'];
                    } else if ($value['extras'] > 0) {
                        $runs = "B " . $value['extras'];
                    }
                }
            } else if ($value['ball_type'] == "WD") {
                $runs = "WD";
                $ob['down_circle_value'] = "WD";
                if ($value['wicket_type'] != null) {
                    $runs = "W";
                    $ob['circle_value'] = 'W';
                    if ($value['extras'] > 1) {


                        $extra = $value['extras'];
                        $extra--;
                        if ($extra > 0) {
                            $runs = $extra . "WD";
                            $ob['down_circle_value'] = $extra . "WD";
                        }
                    }
                } else if ($value['extras'] > 1) {


                    $extra = $value['extras'];
                    $extra--;
                    if ($extra > 0) {
                        $runs = "WD+$extra";
                        $ob['circle_value'] = $extra;
                    }
                }
            } else if ($value['ball_type'] == "NB") {
                $ob['down_circle_value'] = "NB";
                $runs = "NB";
                $total_runs = $value['runs'] + $value['extras'];
                $total_runs--;
                if ($value['wicket_type'] != null) {
                    $runs = "W";
                    $ob['circle_value'] = 'W';
                    if ($total_runs > 0) {
                        $runs = $total_runs . "NB";
                        $ob['down_circle_value'] = $total_runs . "NB";
                    }
                } else if ($total_runs > 0) {
                    $runs = $runs . "NB";
                    $ob['circle_value'] = $total_runs;
                    $ob['down_circle_value'] = "NB";
                }
                // if ($value['runs'] > 1) {
                //     $extra = $value['runs'];
                //     if ($extra > 0) {
                //         $ob['circle_value'] = $extra;
                //         $runs = "NB+$extra";
                //     }
                // }

            } else if ($value['ball_type'] == "DB") {
                if ($value['is_retired']) {
                    $ob['circle_value'] = "RTD";
                    $ob['down_circle_value'] = "W";
                    $runs = "RTD";
                } else if ($value['is_absent']) {
                    $ob['circle_value'] = "ABS";
                    $ob['down_circle_value'] = "W";
                    $runs = "ABS";
                } else if ($value['wicket_type'] == 'ACTION_OUT') {
                    $ob['circle_value'] = "ACO";
                    $ob['down_circle_value'] = "W";
                    $runs = "ABS";
                } else if ($value['is_time_out']) {
                    $ob['circle_value'] = "TOT";
                    $ob['down_circle_value'] = "W";
                    $runs = "TOT";
                } else {
                    $ob['circle_value'] = "DB";
                    $runs = "DB";
                }
                // $ob['down_circle_value'] = "DB";

            }

            // if ($value['wicket_type'] != null) {
            //     $runs = "W";
            //     $ob['down_circle_value'] = 'W';
            //     if ($value['wicket_type'] == 'RUN_OUT') {
            //         $totalRuns = $value['runs'] + $value['extras'];
            //         if ($totalRuns > 0) {
            //             $ob['circle_value'] = $totalRuns;
            //             $runs = "W+" . $totalRuns;
            //         }
            //     }
            // }

            $runs = (string)$runs;
            $ob['circle_value'] = (string)$ob['circle_value'];
            $ob['down_circle_value'] = (string)$ob['down_circle_value'];
            $ob['runs'] = $runs;
            array_push($over_details, $ob);
        }


        $over = floor($score['total_over'] / 6);
        $ball = (int)$score['total_over'] % 6;
        $score['total_over'] = "$over.$ball";
        if ($score['total_runs'] == null) $score['total_runs'] = '0';
        if ($score['total_wicket'] == null) $score['total_wicket'] = '0';

        $bowler['over_details'] = $over_details;
        $totalLEGALBowl = $this->matchQuery->totalLEGALBowl($bowler['over_id']);
        $bowler['over_id'] = $getOver['id'];
        $bowler['is_batsman_change'] = $is_batsman_change;
        $bowler['is_over_finished'] = $totalLEGALBowl > 5 ? 1 : 0;

        return [
            'score' => $score,
            'raw_over_details' => $raw_over_details,
            'batsmans' => $batsmans,
            'bowler' => $bowler,
            'batting_team' => $batting_team,
            'bowling_team' => $bowling_team,
            'target' => $target,
            'power_play_type' => 'P1',
            'toss' => $toss,
            'match_overs' => $match_overs,
            'tourament_name' => $tourament_name,
            'is_first_innings' => $is_first_innings,
        ];
        // }


    }

    public function getStreamMatchLiveScore_kamran($innings_id, $is_batsman_change = 0)
    {

        $innings_details = $this->matchQuery->getInningsDetails($innings_id, 'fixture');

        $toss_winner_team = $this->teamQuery->getTeamByIdQuery($innings_details['fixture']['toss_winner_team_id']);
        $toss = $toss_winner_team['team_name'] . " win the toss";
        $tourament_name = $innings_details['fixture']['tournament']['tournament_name'];
        $match_overs = $innings_details['fixture']['match_overs'];
        $raw_batsmans = $this->matchQuery->getMatchLiveBatsman($innings_id);
        $bowler = $this->matchQuery->getMatchLivebowler($innings_id);
        $batting_team = [];
        $bowling_team = $bowler['team'];
        unset($bowler['team']);
        $target = 0;
        $is_first_innings = $innings_details['is_first_innings'];
        if ($innings_details['is_first_innings'] == 0) {
            $anotherInnings = $this->matchQuery->getAnotherInningsDetails($innings_details['fixture_id']);
            $target = $anotherInnings['total_runs'] + 1;
        }
        $batsmans = [];
        foreach ($raw_batsmans as $value) {
            $ob = $value;
            $batting_team = $value['team'];
            unset($ob['team']);
            array_push($batsmans, $ob);
        }
        $pwor_plays = $this->matchQuery->getInningsPowerPlay($innings_id);
        $power_play_type = null;
        if(sizeof($pwor_plays)>0){
            foreach($pwor_plays as $k => $val){
                if($val['start']>=$innings_details['total_overs'] && $val['end']<=$innings_details['total_overs']){
                    if($val['type']=='Power Play 1'){
                        $power_play_type = 'P1';
                    }
                    if($val['type']=='Power Play 2'){
                        $power_play_type = 'P2';
                    }
                    if($val['type']=='Power Play 3'){
                        $power_play_type = 'P3';
                    }
                    if($val['type']=='Power Play 4'){
                        $power_play_type = 'P4';
                    }
                    break;
                }
            }

        }


        if ($innings_details['innings_status'] == 'Finished') {
            $bowler['over_id'] = 0;
            $bowler['is_batsman_change'] = $is_batsman_change;
            $bowler['is_over_finished'] = 0;
            $bowler['over_details'] = [];
            $score_ob = [
                "total_runs" => (string)$innings_details['total_runs'],
                "total_wicket" => (string)$innings_details['total_wickets'],
                "total_over" => (string)$innings_details['total_overs'],
            ];


            return [
                'score' => $score_ob,
                'batsmans' => $batsmans,
                'bowler' => $bowler,
                'batting_team' => $batting_team,
                'bowling_team' => $bowling_team,
                'target' => $target,
                'power_play_type' => $power_play_type,
                'toss' => $toss,
                'match_overs' => $match_overs,
                'tourament_name' => $tourament_name,
                'is_first_innings' => $is_first_innings,
            ];
        }
        $score = $this->matchQuery->getMatchLiveScoreQuery($innings_id);
        // if($s)


        $getOver = $this->matchQuery->getOver($innings_id);
        $bowler['over_id'] = $getOver['id'];
        $bowler['over_number'] = $getOver['over_number'];
        // if($bowler['over_id']){

        $raw_over_details = $this->matchQuery->getMatchLiveOver_details($bowler['over_number'], $innings_id);
        $over_details = [];

        foreach ($raw_over_details as $value) {
            $ob = [
                'ball_type' => $value['ball_type'],
                'boundary_type' => $value['boundary_type'],
                'circle_value' => 0,
                "down_circle_value" => '',
                'is_wicket' => 0
            ];
            $total_runs = $value['runs'] + $value['extras'];

            if ($value['ball_type'] == "LEGAL") {
                $total_runs = $value['runs'] + $value['extras'];
                if ($value['wicket_type'] != null) {
                    $ob['is_wicket'] = 1;
                    if ($value['runs'] > 0) {
                        $ob['circle_value'] = $value['runs'] . 'W';
                    } else $ob['circle_value'] = 'W';
                } else {
                    $ob['circle_value'] = $value['runs'];
                }

                if ($value['extras'] > 0) {
                    $ob['down_circle_value'] = $value['extras'] . $value['run_type'];
                }
            } else if ($value['ball_type'] == "WD") {

                $ob['down_circle_value'] = "WD";
                if ($value['wicket_type'] != null) {
                    $ob['circle_value'] = 'W';
                    $ob['is_wicket'] = 1;
                    $ob['down_circle_value'] = "WD";
                    if ($value['extras'] > 1) {
                        $ob['down_circle_value'] = "WD+" . ($value['extras'] - 1) . $value['run_type'];
                    }
                } else if ($value['boundary_type'] == 'FOUR') {
                    $ob['circle_value'] = '4';
                    $ob['down_circle_value'] = 'WD';
                } else if ($value['boundary_type'] == 'SIX') {
                    $ob['circle_value'] = '6';
                    $ob['down_circle_value'] = 'WD';
                } else if ($value['extras'] > 1) {
                    $ob['circle_value'] = 'WD';
                    $ob['down_circle_value'] = ($value['extras'] - 1) . $value['run_type'];
                }
            } else if ($value['ball_type'] == "NB") {
                $ob['down_circle_value'] = 'NB';
                $ob['circle_value'] = 0;
                if ($value['wicket_type'] != null) {
                    $ob['circle_value'] = 'W';
                    $ob['is_wicket'] = 1;
                    $ob['down_circle_value'] = 'NB';
                    if ($value['runs'] > 0) {
                        $ob['circle_value'] = $value['runs'] . 'W';
                    }
                    if ($value['extras'] > 1) {
                        $ob['circle_value'] = 'W';
                        $ob['down_circle_value'] = 'NB' . '+' . ($value['extras'] - 1) . $value['run_type'];
                    }
                } else if ($total_runs > 0) {
                    if ($value['runs'] > 0 && $value['extras'] > 1) {
                        $ob['circle_value'] = $value['runs'];
                        $ob['down_circle_value'] = 'NB' . '+' . ($value['extras'] - 1) . $value['run_type'];
                    }
                    if ($value['runs'] > 0) {
                        $ob['circle_value'] = $value['runs'];
                        $ob['down_circle_value'] = 'NB';
                    }
                    if ($value['extras'] > 1) {
                        $ob['circle_value'] = 'NB';
                        $ob['down_circle_value'] = ($value['extras'] - 1) . $value['run_type'];
                    }
                }
            } else if ($value['ball_type'] == "DB") {
                if ($value['is_retired']) {
                    $ob['circle_value'] = "RTD";
                    $ob['down_circle_value'] = "W";
                    // $runs = "RTD";
                } else if ($value['is_absent']) {
                    $ob['circle_value'] = "ABS";
                    $ob['down_circle_value'] = "W";
                    // $runs = "ABS";
                } else if ($value['wicket_type'] == 'ACTION_OUT') {
                    $ob['circle_value'] = "ACO";
                    $ob['down_circle_value'] = "W";
                    // $runs = "ABS";
                } else if ($value['is_time_out']) {
                    $ob['circle_value'] = "TOT";
                    $ob['down_circle_value'] = "W";
                    // $runs = "TOT";
                } else {
                    $ob['circle_value'] = "DB";
                    // $runs = "DB";
                }
                // $ob['down_circle_value'] = "DB";

            }

            // if ($value['wicket_type'] != null) {
            //     $runs = "W";
            //     $ob['down_circle_value'] = 'W';
            //     if ($value['wicket_type'] == 'RUN_OUT') {
            //         $totalRuns = $value['runs'] + $value['extras'];
            //         if ($totalRuns > 0) {
            //             $ob['circle_value'] = $totalRuns;
            //             $runs = "W+" . $totalRuns;
            //         }
            //     }
            // }

            // $runs = (string)$runs;
            $ob['circle_value'] = (string)$ob['circle_value'];
            $ob['down_circle_value'] = (string)$ob['down_circle_value'];
            // $ob['runs'] = $runs;
            array_push($over_details, $ob);
        }


        $over = floor($score['total_over'] / 6);
        $ball = (int)$score['total_over'] % 6;
        $score['total_over'] = "$over.$ball";
        if ($score['total_runs'] == null) $score['total_runs'] = '0';
        if ($score['total_wicket'] == null) $score['total_wicket'] = '0';

        $bowler['over_details'] = $over_details;
        $totalLEGALBowl = $this->matchQuery->totalLEGALBowl($bowler['over_id']);
        $bowler['over_id'] = $getOver['id'];
        $bowler['is_batsman_change'] = $is_batsman_change;
        $bowler['is_over_finished'] = $totalLEGALBowl > 5 ? 1 : 0;

        return [
            'score' => $score,
            'batsmans' => $batsmans,
            'bowler' => $bowler,
            'batting_team' => $batting_team,
            'bowling_team' => $bowling_team,
            'target' => $target,
            'power_play_type' => $power_play_type,
            'toss' => $toss,
            'match_overs' => $match_overs,
            'tourament_name' => $tourament_name,
            'is_first_innings' => $is_first_innings,
        ];
        // }


    }

    public function singleMatchScored($id)
    {

        $id = isset($id) ? $id : 0;
        $singleMatch = $fixture = $this->matchQuery->getRunningMatchById($id);

        $status = [];
        $status['match_status'] = "";
        if ($singleMatch && $singleMatch->is_match_start == 1 && $singleMatch->is_match_finished == 0 && $singleMatch->is_match_no_result == 0) {
            $status['match_status'] = "Runnig";
        }
        if ($singleMatch && $singleMatch->is_match_start == 1 && $singleMatch->is_match_finished == 1) {
            $status['match_status'] = "Finished";
        }
        if ($singleMatch && $singleMatch->is_match_no_result == 1) {
            $status['match_status'] = "Abandoned";
        }
        if ($singleMatch && $singleMatch->is_match_start == 0 && $singleMatch->is_match_finished == 0 && $singleMatch->is_match_no_result == 0) {
            $status['match_status'] = "Upcoming";
        }


        if ($singleMatch && $singleMatch->is_match_start == 1) {
            $match = $this->matchQuery->singleMatchScoredQuery($id);
            // return $match;
            $obj = [];
            $obj['match_status'] = $status['match_status'];
            $obj['match_final_result'] = isset($match->match_final_result) ? $match->match_final_result : '';
            if (isset($match->player_of_the_match) && $match->player_of_the_match != null) {
                $obj['best_player_id'] = isset($match->player_of_the_match) ? $match->player_of_the_match : 0;
                $obj['best_player'] = isset($match->playerOftheMatch) ? $match->playerOftheMatch->first_name . ' ' . $match->playerOftheMatch->last_name : '';
                $obj['profile_pic'] = isset($match->playerOftheMatch) ? $match->playerOftheMatch->profile_pic : '';
                $obj['best_player_runs'] = isset($match->best_player_runs) ? $match->best_player_runs : '';
                $obj['best_player_balls_faced'] = isset($match->best_player_balls_faced) ? $match->best_player_balls_faced : '';
                $obj['best_player_bowled'] = isset($match->best_player_balls_bowled) ? $match->best_player_balls_bowled : '';
                $obj['best_player_wicket_achieved'] = isset($match->best_player_wickets) ? $match->best_player_wickets : '';
                $obj['best_player_runs_gave'] = isset($match->best_player_runs_gave) ? $match->best_player_runs_gave : '';
                $obj['best_player_caught'] = isset($match->best_player_caught) ? ($match->best_player_caught + $match->best_player_bowled_caught) : '';
            }

            $matchInnings = array();
            if (isset($match->innings) && $match->innings->count() > 0) {
                foreach ($match->innings as $i) {
                    $i->team_id = $i->batting_team->id;
                    $i->team_name = $i->batting_team->team_name;
                    $i->team_short_name = $i->batting_team->team_short_name;
                    $i->is_win = $match->match_winner_team_id == $i->batting_team_id ? 1 : 0;
                    array_push($matchInnings, $i);
                    unset($i->batting_team);
                }
            }

            $firstInningsFinished = 0;
            $secondInningsStart = 0;
            $secondInningsFinished = 0;

            if ($fixture->total_innings) {
                $firstInnings = $fixture->innings->where('is_first_innings', 1)->first();
                $secondInnings = $fixture->innings->where('is_first_innings', 0)->first();
                $firstInningsFinished = $firstInnings->innings_status == 'Finished' ? 1 : 0;
                $secondInningsStart = $secondInnings->innings_status == 'Started' ? 1 : 0;
                $secondInningsFinished = $secondInnings->innings_status == 'Finished' ? 1 : 0;
            }

            $obj['match_final_result'] = ( $fixture->tossWinnerTeam->team_name ?? '') . ' elected to ' . ucfirst(strtolower($fixture->team_elected_to));

            if ($firstInningsFinished and !$secondInningsStart and !$secondInningsFinished) {
                $obj['match_final_result'] = "Innings Break";
            } else if($firstInningsFinished and ($secondInningsStart or $secondInningsFinished)) {
                $secondInnings = $this->matchQuery->getLiveScoreByInningsId($firstInnings['id']);
                $runsNeed = ($fixture['home_team_runs'] + 1) - $fixture['away_team_runs'];
                $ballsLeft = (floor($fixture->match_overs * 6) - $this->overstoBall($fixture->away_team_overs));
                $battingTeamWicketsLeft = $this->matchQuery->getBattingTeamWicketsLeft($fixture['id'], $secondInnings['batting_team_id']) ?? 0;
                $matchStatement = "";

                if ($ballsLeft > 0 and $runsNeed > 0 and $battingTeamWicketsLeft > 1) {
                    $matchStatement = $secondInnings->batting_team->team_name . ' needs ' . $runsNeed . ' runs from ' . $ballsLeft . ' balls.';
                } else if ($fixture['away_team_runs'] > $fixture['home_team_runs']) {
                    $matchStatement = $secondInnings->batting_team->team_name . ' won by ' . ($battingTeamWicketsLeft - 1) . ' wickets';
                } else if ($fixture['away_team_runs'] == $fixture['home_team_runs']) {
                    $matchStatement = 'Draw';
                } else {
                    $matchStatement = $secondInnings->bowling_team->team_name . ' won by ' . ((int)($fixture['home_team_runs']) - (int)($fixture['away_team_runs'])) . ' runs';
                }

                $obj['match_final_result'] = $matchStatement;
            }

            if($fixture['events']){
                $event = json_decode($fixture['events']);
                $obj['match_final_result'] = $event->event_name . ' Break';
            }


            return ["match_status" => $status['match_status'], "match_details" => (object)$obj, "innings" => $matchInnings];
        }

        return ["match_status" => $status['match_status']];
    }

    public function oversFormate($over)
    {
        // if(fmod($over, 1)){
        //     return true;
        // }else{
        //     return false;
        // }
        $overs = floor($over);
        $oversBall = $over - $overs;

        $formated = round(($oversBall / 6) * 10, 2);

        $total_overs = $overs + $formated;
        return $total_overs;
        // $runRate = number_format($runs/$total_overs, 2, ".", "");
        // return $runRate;
    }

    public function overstoBall($overs)
    {
        $over = $overs - floor($overs);
        $totalBalls = (floor($overs) * 6) + ($over * 10);
        return $totalBalls;
    }

    public function getInningsLiveScore($data)
    {
        $id = $data['fixture_id'];
        $fixture = $this->matchQuery->getRunningMatchById($id);


        if ($fixture && $fixture->total_innings and $fixture->is_match_start == 1 && $fixture->is_match_finished == 0 && $fixture->is_match_no_result == 0) {

            $firstInningsStart = 0;
            $firstInningsFinished = 0;
            $secondInningsStart = 0;
            $secondInningsFinished = 0;

            if ($fixture->total_innings) {
                $firstInnings = $fixture->innings->where('is_first_innings', 1)->first();
                $secondInnings = $fixture->innings->where('is_first_innings', 0)->first();
                $firstInningsStart = $firstInnings->innings_status == 'Started' ? 1 : 0;
                $firstInningsFinished = $firstInnings->innings_status == 'Finished' ? 1 : 0;
                $secondInningsStart = $secondInnings->innings_status == 'Started' ? 1 : 0;
                $secondInningsFinished = $secondInnings->innings_status == 'Finished' ? 1 : 0;
            }

            $obj = [];
            $obj['match_status'] = "Runnig";

            $obj['match_statement'] = ( $fixture->tossWinnerTeam->team_name ?? '') . ' elected to ' . ucfirst(strtolower($fixture->team_elected_to));
            $liveMatch = $this->matchQuery->getLiveScoreByInningsId($firstInnings['id']);
            if ($firstInningsFinished and !$secondInningsStart and !$secondInningsFinished) {
                $obj['match_statement'] = "Innings Break";
            } else if($firstInningsFinished and ($secondInningsStart or $secondInningsFinished)) {
                $liveMatch = $this->matchQuery->getLiveScoreByInningsId($secondInnings['id']);
                $runsNeed = ($fixture['home_team_runs'] + 1) - $fixture['away_team_runs'];
                $ballsLeft = (floor($fixture->match_overs * 6) - $this->overstoBall($fixture->away_team_overs));
                $battingTeamWicketsLeft = $this->matchQuery->getBattingTeamWicketsLeft($fixture['id'], $liveMatch['batting_team_id']) ?? 0;
                $matchStatement = "";

                if ($ballsLeft > 0 and $runsNeed > 0 and $battingTeamWicketsLeft > 1) {
                    $matchStatement = $liveMatch->batting_team->team_name . ' needs ' . $runsNeed . ' runs from ' . $ballsLeft . ' balls.';
                } else if ($fixture['away_team_runs'] > $fixture['home_team_runs']) {
                    $matchStatement = $liveMatch->batting_team->team_name . ' won by ' . ($battingTeamWicketsLeft - 1) . ' wickets';
                } else if ($fixture['away_team_runs'] == $fixture['home_team_runs']) {
                    $matchStatement = 'Draw';
                } else {
                    $matchStatement = $liveMatch->bowling_team->team_name . ' won by ' . ((int)($fixture['home_team_runs']) - (int)($fixture['away_team_runs'])) . ' runs';
                }

                $obj['match_statement'] = $matchStatement;
            }


            $obj['team_id'] = $liveMatch->batting_team->id;
            $obj['team_name'] = $liveMatch->batting_team->team_name;
            $obj['team_short_name'] = $liveMatch->batting_team->team_short_name;
            $obj['current_run_rate'] = $liveMatch->total_runs && (floor($liveMatch->total_overs) || fmod($liveMatch->total_overs, 1)) ? number_format($liveMatch->total_runs / $this->oversFormate($liveMatch->total_overs), 2, ".", "") : 0;
            $obj['total_runs'] = $liveMatch->total_runs . '/' . $liveMatch->total_wickets;
            $obj['total_overs'] = $liveMatch->total_overs;
            $obj['partnership_by_run'] = $liveMatch->currentStriker && $liveMatch->currentNonStriker ? $liveMatch->currentStriker->runs_achieved + $liveMatch->currentNonStriker->runs_achieved . '(' . ($liveMatch->currentStriker->balls_faced + $liveMatch->currentNonStriker->balls_faced) . ')' : null;
            $obj['target'] = $liveMatch->is_first_innings == 0 ? ($fixture->home_team_runs + 1) : 0;
            $obj['required_run_rate'] = $liveMatch->is_first_innings == 0 && $firstInnings ? round(($obj['target'] - $liveMatch->total_runs) / (floor($fixture->match_overs * 6) - $this->overstoBall($liveMatch->total_overs)) * 6, 2) : 0;

            if ($liveMatch->currentStriker) {
                $striker = [];
                $striker['batter_id'] = $liveMatch->currentStriker->batter ? $liveMatch->currentStriker->batter->id : 0;
                $striker['batter_name'] = $liveMatch->currentStriker->batter ? $liveMatch->currentStriker->batter->first_name . '' . $liveMatch->currentStriker->batter->last_name : '';
                $striker['balls_faced'] = $liveMatch->currentStriker->balls_faced;
                $striker['runs_achieved'] = $liveMatch->currentStriker->runs_achieved;
                $striker['is_on_strike'] = $liveMatch->currentStriker->is_on_strike;
                $striker['fours'] = $liveMatch->currentStriker->fours;
                $striker['sixes'] = $liveMatch->currentStriker->sixes;
                $striker['strike_rate'] = number_format($liveMatch->currentStriker->strike_rate, 1, ".", "");
            }


            if ($liveMatch->currentNonStriker) {
                $nonStriker = [];
                $nonStriker['batter_id'] = $liveMatch->currentNonStriker->batter ? $liveMatch->currentNonStriker->batter->id : 0;
                $nonStriker['batter_name'] = $liveMatch->currentNonStriker->batter ? $liveMatch->currentNonStriker->batter->first_name . '' . $liveMatch->currentNonStriker->batter->last_name : 0;
                $nonStriker['balls_faced'] = $liveMatch->currentNonStriker->balls_faced;
                $nonStriker['runs_achieved'] = $liveMatch->currentNonStriker->runs_achieved;
                $nonStriker['is_on_strike'] = $liveMatch->currentNonStriker->is_on_strike;
                $nonStriker['fours'] = $liveMatch->currentNonStriker->fours;
                $nonStriker['sixes'] = $liveMatch->currentNonStriker->sixes;
                $nonStriker['strike_rate'] = number_format($liveMatch->currentNonStriker->strike_rate, 1, ".", "");
            }

            if (isset($striker) && isset($nonStriker)) {
                $obj['batsman'] = array($striker, $nonStriker);
            } else {
                $obj['batsman'] = [];
            }

            if ($liveMatch->currentBowler) {
                $obj['bowler'] = [
                    "bowler_id" => $liveMatch->currentBowler->bowler ? $liveMatch->currentBowler->bowler->id : 0,
                    "bowler_name" => $liveMatch->currentBowler->bowler ? $liveMatch->currentBowler->bowler->first_name . ' ' . $liveMatch->currentBowler->bowler->last_name : 0,
                    "runs_gave" => $liveMatch->currentBowler->runs_gave,
                    "overs_bowled" => $liveMatch->currentBowler->overs_bowled,
                    "maiden_overs" => $liveMatch->currentBowler->maiden_overs,
                    "wickets" => $liveMatch->currentBowler->wickets,
                    "economy" => number_format($liveMatch->currentBowler->economy, 2, ".", ""),
                ];
            }

            if($fixture['events']){
                $event = json_decode($fixture['events']);
                $obj['match_statement'] = $event->event_name . ' Break';
            }

            return (object)$obj;
        } else {
            return $this->singleMatchScored($id);
        }
    }

    public function getCurrentInningsLive($data)
    {
        $messyObj = $this->matchQuery->getCurrentInningsLiveQuery($data);

        $formattedObj = collect();
        $formattedObj->put('team_name', $messyObj->batting_team->team_short_name);
        $formattedObj->put('runs_took', $messyObj->runs_take);
        $formattedObj->put('overs_faced', (string)$messyObj->overs_faced);
        $formattedObj->put('wickets_gave', $messyObj->wickets_gave);
        $formattedObj->put('current_run_rate', (string)$messyObj->current_run_rate);
        $formattedObj->put('current_bowler', (string)$messyObj->currentBowler->bowler->full_name);
        $formattedObj->put('current_bowler_runs_gave', (string)$messyObj->currentBowler->runs_gave);
        $formattedObj->put('current_bowler_over', (string)$messyObj->currentBowler->overs_bowled);
        $formattedObj->put('current_bowler_wickets', (string)$messyObj->currentBowler->wickets);
        $formattedObj->put('current_striker', $messyObj->currentStriker->batter->full_name);
        $formattedObj->put('current_non_striker', $messyObj->currentNonStriker->batter->full_name);
        $formattedObj->put('current_striker_runs', (string)$messyObj->currentStriker->runs_achieved);
        $formattedObj->put('current_non_striker_runs', (string)$messyObj->currentNonStriker->runs_achieved);
        $formattedObj->put('current_striker_balls_faced', (string)$messyObj->currentStriker->balls_faced);
        $formattedObj->put('current_non_striker_balls_faced', (string)$messyObj->currentNonStriker->balls_faced);
        $formattedObj->put('is_match_finished', $messyObj->fixture->is_match_finished);
        $formattedObj->put('match_final_result', (string)$messyObj->fixture->match_final_result);

        if (!$messyObj->is_first_innings) {
            // getting opposite team target from previous innings
            $obj = $this->matchQuery->getPreviousInningsResult($data);
            $formattedObj->put('opposite_team_target', (string)$obj ? $obj->target : 0);

            // calculating required run rate
            $matchOvers = $messyObj->fixture->match_overs;
            $runsNeed = $formattedObj->get('opposite_team_target') - $formattedObj->get('runs_took');
            $oversLeft = $matchOvers - $formattedObj->get('overs_faced');
            if ($runsNeed and $oversLeft) {
                $rrr = round($runsNeed / $oversLeft, 2);
                $formattedObj->put('required_run_rate', (string)$rrr);
            }
        }

        //      formatting over deliveries
        $overs = collect();
        foreach ($messyObj->innings_overs as $over) {
            $deliveries = collect();
            foreach ($over->oversDelivery as $delivery) {
                $runs = $delivery['runs'];
                if ($delivery['ball_type'] == "LEGAL") {

                    $runs = $delivery['runs'] + $delivery['extras'];
                    if ($delivery['run_type'] == 'LB') {
                        if ($delivery['runs'] > 0) {
                            $runs = $delivery['runs'] . "+LB " . $delivery['extras'];
                        } else if ($delivery['extras'] > 0) {
                            $runs = "LB " . $delivery['extras'];
                        }
                    } else if ($delivery['run_type'] == 'B') {
                        if ($delivery['runs'] > 0) {
                            $runs = $delivery['runs'] . "+B " . $delivery['extras'];
                        } else if ($delivery['extras'] > 0) {
                            $runs = "B " . $delivery['extras'];
                        }
                    }
                } else if ($delivery['ball_type'] == "WD") {
                    $runs = "WD";
                    if ($delivery['extras'] > 1) {


                        $extra = $delivery['extras'];
                        $extra--;
                        if ($extra > 0) {
                            $runs = "WD+$extra";
                        }
                    }
                } else if ($delivery['ball_type'] == "NB") {
                    $runs = "NB";
                    if ($delivery['extras'] > 1) {
                        $extra = $delivery['extras'];
                        $extra--;
                        if ($extra > 0) {
                            $runs = "NB+$extra";
                        }
                    }
                    if ($delivery['runs'] > 1) {
                        $extra = $delivery['runs'];
                        if ($extra > 0) {
                            $runs = "NB+$extra";
                        }
                    }
                } else if ($delivery['ball_type'] == "DB") {
                    $runs = "DB";
                } else if ($delivery['ball_type'] == "IB") {
                    $runs = "IB";
                }

                if ($delivery['wicket_type'] != null) {
                    $runs = "W";
                    if ($delivery['wicket_type'] == 'RUN_OUT') {
                        $totalRuns = $delivery['runs'] + $delivery['extras'];
                        if ($totalRuns > 0) {
                            $runs = "W+" . $totalRuns;
                        }
                    }
                }
                $runs = (string)$runs;
                $deliveries->push($runs);
            }
            if ($deliveries->count()) {
                $overs->push($deliveries);
            }
        }

        $formattedObj->put('over_deliveries', $overs);

        return $formattedObj;
    }

    public function getCurrentInningsLiveScore($data)
    {
        $currentFixture = $this->matchQuery->getFixtureById($data);
        $currentInnings = $this->matchQuery->getLiveScorebyInnings($data['fixture_id']);
        $obj = [];

        $obj['team_id'] = $currentInnings->batting_team->id;
        $obj['team_name'] = $currentInnings->batting_team->team_name;
        $obj['team_short_name'] = $currentInnings->batting_team->team_short_name;

        $obj['current_run_rate'] = (string)($currentInnings->total_runs && (floor($currentInnings->total_overs) || fmod($currentInnings->total_overs, 1)) ? number_format($currentInnings->total_runs / $this->oversFormate($currentInnings->total_overs), 2, ".", "") : 0);

        $obj['total_runs'] = $currentInnings->total_runs . '/' . $currentInnings->total_wickets;
        $obj['total_overs'] = (string)$currentInnings->total_overs;
        // $obj['partnership_by_run'] = $currentInnings->currentStriker && $currentInnings->currentNonStriker ? $currentInnings->currentStriker->runs_achieved + $currentInnings->currentNonStriker->runs_achieved . '(' . ($currentInnings->currentStriker->balls_faced + $currentInnings->currentNonStriker->balls_faced) . ')' : null;
        $obj['target'] = (string)($currentInnings->is_first_innings == 0 && $currentInnings->previous_innings ? $currentInnings->previous_innings->total_runs + 1 : 0);
        if ($currentInnings->is_first_innings == 0 && $currentInnings->previous_innings) {
            $obj['match_statement'] = $obj['team_name'] . ' Need ' . ($obj['target'] - $currentInnings->total_runs) . ' runs in ' . (floor($currentFixture->match_overs * 6) - $this->overstoBall($currentInnings->total_overs)) . ' balls';
        } else {
            $obj['match_statement'] = ($currentInnings->fixture && $currentInnings->fixture->tossWinnerTeam ? $currentInnings->fixture->tossWinnerTeam->team_name : '') . ' elected to ' . ucfirst(strtolower($currentFixture->team_elected_to));
        }
        $obj['required_run_rate'] = (string)($currentInnings->is_first_innings == 0 && $currentInnings->previous_innings ? round(($obj['target'] - $currentInnings->total_runs) / (floor($currentFixture->match_overs * 6) - $this->overstoBall($currentInnings->total_overs)) * 6, 2) : 0);

        if ($currentInnings->currentStriker) {
            $strikeRate = number_format($currentInnings->currentStriker->strike_rate, 1, ".", "");
            $striker = [];
            $striker['batter_id'] = $currentInnings->currentStriker->batter ? $currentInnings->currentStriker->batter->id : 0;
            $striker['batter_name'] = $currentInnings->currentStriker->batter ? $currentInnings->currentStriker->batter->first_name . '' . $currentInnings->currentStriker->batter->last_name : '';
            $striker['balls_faced'] = (string)$currentInnings->currentStriker->balls_faced;
            $striker['runs_achieved'] = (string)$currentInnings->currentStriker->runs_achieved;
            $striker['is_on_strike'] = (string)$currentInnings->currentStriker->is_on_strike;
            $striker['fours'] = (string)$currentInnings->currentStriker->fours;
            $striker['sixes'] = (string)$currentInnings->currentStriker->sixes;
            $nonStriker['strike_rate'] = (string)$strikeRate;
        }


        if ($currentInnings->currentNonStriker) {
            $strikeRate = number_format($currentInnings->currentNonStriker->strike_rate, 1, ".", "");
            $nonStriker = [];
            $nonStriker['batter_id'] = $currentInnings->currentNonStriker->batter ? $currentInnings->currentNonStriker->batter->id : 0;
            $nonStriker['batter_name'] = $currentInnings->currentNonStriker->batter ? $currentInnings->currentNonStriker->batter->first_name . '' . $currentInnings->currentNonStriker->batter->last_name : 0;
            $nonStriker['balls_faced'] = (string)$currentInnings->currentNonStriker->balls_faced;
            $nonStriker['runs_achieved'] = (string)$currentInnings->currentNonStriker->runs_achieved;
            $nonStriker['is_on_strike'] = (string)$currentInnings->currentNonStriker->is_on_strike;
            $nonStriker['fours'] = (string)$currentInnings->currentNonStriker->fours;
            $nonStriker['sixes'] = (string)$currentInnings->currentNonStriker->sixes;
            $nonStriker['strike_rate'] = (string)$strikeRate;
        }

        if (isset($striker) && isset($nonStriker)) {
            $obj['batters'] = array($striker, $nonStriker);
        } else {
            $obj['batters'] = [];
        }


        if ($currentInnings->currentBowler) {
            $economy = number_format($currentInnings->currentBowler->economy, 2, ".", "");
            $obj['bowler'] = [
                "bowler_id" => $currentInnings->currentBowler->bowler ? $currentInnings->currentBowler->bowler->id : 0,
                "bowler_name" => $currentInnings->currentBowler->bowler ? $currentInnings->currentBowler->bowler->first_name . ' ' . $currentInnings->currentBowler->bowler->last_name : 0,
                "runs_gave" => (string)$currentInnings->currentBowler->runs_gave,
                "overs_bowled" => (string)$currentInnings->currentBowler->overs_bowled,
                "maiden_overs" => (string)$currentInnings->currentBowler->maiden_overs,
                "wickets" => (string)$currentInnings->currentBowler->wickets,
                "economy" => (string)$economy
            ];
        }

        $data = $this->matchQuery->getDeliveriesByInningsQuery($currentInnings['id']);
        $deliveriesByOver = $data->groupBy('over_number');

        $formattedOvers = collect();
        foreach ($deliveriesByOver as $overDeliveries) {
            $formattedDeliveries = collect();
            foreach ($overDeliveries as $delivery) {
                $runs = $delivery['runs'];
                if ($delivery['ball_type'] == "LEGAL") {

                    $runs = $delivery['runs'] + $delivery['extras'];
                    if ($delivery['run_type'] == 'LB') {
                        if ($delivery['runs'] > 0) {
                            $runs = $delivery['runs'] . "+LB " . $delivery['extras'];
                        } else if ($delivery['extras'] > 0) {
                            $runs = "LB " . $delivery['extras'];
                        }
                    } else if ($delivery['run_type'] == 'B') {
                        if ($delivery['runs'] > 0) {
                            $runs = $delivery['runs'] . "+B " . $delivery['extras'];
                        } else if ($delivery['extras'] > 0) {
                            $runs = "B " . $delivery['extras'];
                        }
                    }
                } else if ($delivery['ball_type'] == "WD") {
                    $runs = "WD";
                    if ($delivery['extras'] > 1) {


                        $extra = $delivery['extras'];
                        $extra--;
                        if ($extra > 0) {
                            $runs = "WD+$extra";
                        }
                    }
                } else if ($delivery['ball_type'] == "NB") {
                    $runs = "NB";
                    if ($delivery['extras'] > 1) {
                        $extra = $delivery['extras'];
                        $extra--;
                        if ($extra > 0) {
                            $runs = "NB+$extra";
                        }
                    }
                    if ($delivery['runs'] > 1) {
                        $extra = $delivery['runs'];
                        if ($extra > 0) {
                            $runs = "NB+$extra";
                        }
                    }
                } else if ($delivery['ball_type'] == "DB") {
                    $runs = "DB";
                    if ($delivery['is_retired']) {
                        $runs = "RTD";
                    } else if ($delivery['is_absent']) {
                        $runs = "ABS";
                    } else if ($delivery['wicket_type'] == 'ACTION_OUT') {
                        $runs = "ABS";
                    } else if ($delivery['is_time_out']) {
                        $runs = "TOT";
                    }
                } else if ($delivery['ball_type'] == "IB") {
                    $runs = "IB";
                }

                if ($delivery['wicket_type'] != null) {
                    $runs = "W";
                    if ($delivery['wicket_type'] == 'RUN_OUT') {
                        $totalRuns = $delivery['runs'] + $delivery['extras'];
                        if ($totalRuns > 0) {
                            $runs = "W+" . $totalRuns;
                        }
                    }
                }

                $runs = (string)$runs;

                $formattedDeliveries->push($runs);
            }

            if ($formattedDeliveries->count()) {
                $formattedOvers->push($formattedDeliveries);
            }
        }

        $obj['deliveries_by_over'] = $formattedOvers ?? [];

        return $obj;
    }

    public function ballsFormatToOver($balls)
    {
        return ($balls ? floor($balls / 6) . '.' . ($balls % 6) : 0);
    }

    public function singleTeamScored($data)
    {
        $match = $this->matchQuery->singleTeamScoredQuery($data);

        if (isset($match->did_not_bat) && $match->did_not_bat->count() > 0) {
            foreach (isset($match->did_not_bat) ? $match->did_not_bat : [] as $d) {
                $d->first_name = $d->player->first_name;
                $d->last_name = $d->player->last_name;
                unset($d->player);
            }
        }
        if ($match->fall_of_wicket->count() > 0) {
            foreach ($match->fall_of_wicket as $d) {
                $d->in_which_over = (int)$this->ballsFormatToOver($d->in_which_over);
            }
        }

        if ($match->powerplay->count() > 0) {
            foreach ($match->powerplay as $p) {
                $p->runs = $this->matchQuery->powerplay_overs($p->inning_id, $p->start, $p->end);
                $p->overs = "";
                if ($p->start == $p->end) {
                    $p->overs = $p->start - 1 . '.1 -' . $p->end;
                } else {
                    $p->overs = $p->start . ' - ' . $p->end;
                }
            }
        }

        return $match;
    }

    public function deliveriesOverFormate($Overs)
    {
        $arr = [];
        foreach ($Overs as $O) {
            $Ov = [];
            $Ov["over_number"] = $O->over_number;
            $Ov["inning_id"] = $O->inning_id;
            $Ov["bowler"] = $O->bowler ? $O->bowler->first_name . ' ' . $O->bowler->last_name : '';
            $Ov["total_runs"] = 0;
            $Ov["over_details"] = array();
            foreach ($O->oversDeliveries as $value) {

                $Ov["total_runs"] = $Ov["total_runs"] + ($value->runs + $value->extras);

                $ob = [
                    'runs' => 0,
                    'ball_type' => $value['ball_type'],
                    'circle_value' => 0,
                    "down_circle_value" => ''
                ];

                $runs = $value['runs'];
                if ($value['ball_type'] == "LEGAL") {
                    $runs = $value['runs'] + $value['extras'];
                    $total_runs = $value['runs'] + $value['extras'];
                    if ($value['wicket_type'] != null) {
                        $runs = "W";
                        $ob['circle_value'] = 'W';
                        if ($total_runs > 0) {
                            $runs = "WD";
                            $ob['circle_value'] = $total_runs;
                            $ob['down_circle_value'] = 'W';
                        }
                    }
                    $ob['circle_value'] = $runs;
                    if ($value['run_type'] == 'LB') {
                        $ob['down_circle_value'] = "LB";
                        if ($value['runs'] > 0) {
                            $runs = $value['runs'] . "+LB " . $value['extras'];
                        } else if ($value['extras'] > 0) {
                            $runs = "LB " . $value['extras'];
                        }
                    } else if ($value['run_type'] == 'B') {
                        $ob['down_circle_value'] = "B";
                        if ($value['runs'] > 0) {
                            $runs = $value['runs'] . "+B " . $value['extras'];
                        } else if ($value['extras'] > 0) {
                            $runs = "B " . $value['extras'];
                        }
                    }
                } else if ($value['ball_type'] == "WD") {
                    $runs = "WD";
                    $ob['down_circle_value'] = "WD";
                    if ($value['wicket_type'] != null) {
                        $runs = "W";
                        $ob['circle_value'] = 'W';
                        if ($value['extras'] > 1) {
                            $extra = $value['extras'];
                            $extra--;
                            if ($extra > 0) {
                                $runs = $extra . "WD";
                                $ob['down_circle_value'] = $extra . "WD";
                            }
                        }
                    } else if ($value['extras'] > 1) {
                        $extra = $value['extras'];
                        $extra--;
                        if ($extra > 0) {
                            $runs = "WD+$extra";
                            $ob['circle_value'] = $extra;
                        }
                    }
                } else if ($value['ball_type'] == "NB") {
                    $ob['down_circle_value'] = "NB";
                    $runs = "NB";
                    $total_runs = $value['runs'] + $value['extras'];
                    $total_runs--;
                    if ($value['wicket_type'] != null) {
                        $runs = "W";
                        $ob['circle_value'] = 'W';
                        if ($total_runs > 0) {
                            $runs = $total_runs . "NB";
                            $ob['down_circle_value'] = $total_runs . "NB";
                        }
                    } else if ($total_runs > 0) {
                        $runs = $runs . "NB";
                        $ob['circle_value'] = $total_runs;
                        $ob['down_circle_value'] = "NB";
                    }
                } else if ($value['ball_type'] == "DB") {
                    if ($value['is_retired']) {
                        $ob['circle_value'] = "RTD";
                        $ob['down_circle_value'] = "W";
                        $runs = "RTD";
                    } else if ($value['is_absent']) {
                        $ob['circle_value'] = "ABS";
                        $ob['down_circle_value'] = "W";
                        $runs = "ABS";
                    } else if ($value['wicket_type'] == 'ACTION_OUT') {
                        $ob['circle_value'] = "ACO";
                        $ob['down_circle_value'] = "W";
                        $runs = "ABS";
                    } else if ($value['is_time_out']) {
                        $ob['circle_value'] = "TOT";
                        $ob['down_circle_value'] = "W";
                        $runs = "TOT";
                    } else {
                        $ob['circle_value'] = "DB";
                        $runs = "DB";
                    }
                    // $ob['down_circle_value'] = "DB";
                }
                $runs = (string)$runs;
                $ob['circle_value'] = (string)$ob['circle_value'];
                $ob['down_circle_value'] = (string)$ob['down_circle_value'];
                $ob['runs'] = $runs;
                $ob['bowler_id'] = $value->bowler_id;
                array_push($Ov['over_details'], $ob);
            }
            array_push($arr, $Ov);
        }
        return $arr;
    }


    public function deliveriesByOver($data)
    {
        $arr = array();
        $inning_id = isset($data['inning_id']) ? $data['inning_id'] : 0;
        $ln = isset($data['last_over_number']) ? $data['last_over_number'] : 0;
        $fId = isset($data['fixture_id']) ? $data['fixture_id'] : 0;
        $innings = $this->matchQuery->getInnings($fId);


        if ($innings->count() < 1) {
            return $arr;
        }
        $first_inning_id = $innings && $innings[0] && $innings[0]->id ? $innings[0]->id : 0;
        $first_innings_overs_count = $this->matchQuery->countOvers($first_inning_id);

        $second_inning_id = 0;
        $second_innings_overs_count = 0;
        if ($innings->count() > 1) {
            $second_inning_id = $innings[1] && $innings[1]->id ? $innings[1]->id : 0;
            $second_innings_overs_count = $this->matchQuery->countOvers($second_inning_id);
        }

        if (!$inning_id && !$ln) {

            $data['inning_id'] = $first_inning_id;

            $first_innings = $this->matchQuery->deliveriesByOverQuery($data);

            if ($first_innings->count() < 1 && $second_innings_overs_count > 1) {
                return $arr;
            }

            if ($first_innings->count() < 10 && $second_innings_overs_count > 0) {

                $inningsOvers = $this->deliveriesOverFormate($first_innings);
                array_push($arr, ...$inningsOvers);


                $data['inning_id'] = $second_inning_id;
                $second_innings = $this->matchQuery->deliveriesByOverQuery($data);

                $secondInningsOvers = $this->deliveriesOverFormate($second_innings);
                array_push($arr, ...$secondInningsOvers);
            } else {
                $data['inning_id'] = $first_inning_id;
                $Overs = $this->matchQuery->deliveriesByOverQuery($data);
                if ($Overs->count() > 0) {
                    $inningsOvers = $this->deliveriesOverFormate($Overs);
                    array_push($arr, ...$inningsOvers);
                }
            }
        }

        if (($inning_id == $first_inning_id) && $ln > 2) {
            $Overs = $this->matchQuery->deliveriesByOverQuery($data);

            if ($Overs->count() > 0) {
                $inningsOvers = $this->deliveriesOverFormate($Overs);
                array_push($arr, ...$inningsOvers);
            }
        }

        if (($inning_id == $first_inning_id) && $ln < 3) {

            $first_innings = $this->matchQuery->deliveriesByOverQuery($data);
            if ($first_innings->count() > 0) {
                $inningsOvers = $this->deliveriesOverFormate($first_innings);
                array_push($arr, ...$inningsOvers);
            }

            $data['inning_id'] = $second_inning_id;
            $data['last_over_number'] = 0;

            $second_innings = $this->matchQuery->deliveriesByOverQuery($data);
            // return $second_innings;
            if ($second_innings->count() > 0) {

                $secondInningsOvers = $this->deliveriesOverFormate($second_innings);
                array_push($arr, ...$secondInningsOvers);
            }
        }

        if ($inning_id == $second_inning_id && $ln) {
            $Overs = $this->matchQuery->deliveriesByOverQuery($data);
            if ($Overs->count() > 0) {
                $inningsOvers = $this->deliveriesOverFormate($Overs);
                array_push($arr, ...$inningsOvers);
            }
        }

        return $arr;
    }

    public function getNotOutBatsman($data)
    {
        return $this->matchQuery->getNotOutBatsmanQuery($data);
    }

    public function unexpectedEndMatch($data)
    {
        $updated_data = [];
        $updated_data['id'] = $data['id'];
        $updated_data['is_match_finished'] = 1;
        if (isset($data['match_winner_team_id']) && isset($data['match_loser_team_id'])) {
            $updated_data['match_winner_team_id'] = $data['match_winner_team_id'];
            $updated_data['match_loser_team_id'] = $data['match_loser_team_id'];
            $team = $this->matchQuery->getTeamById($updated_data['match_winner_team_id']);
            $updated_data['match_final_result'] = $team['team_name'] . " won!";
        }
        if (isset($data['is_match_no_result'])) {
            $updated_data['is_match_no_result'] = $data['is_match_no_result'];
        }
        if (isset($data['is_run_rate_count'])) {
            $updated_data['is_run_rate_count'] = $data['is_run_rate_count'];
        }
        if (isset($data['reason'])) {
            $updated_data['reason'] = $data['reason'];
        }
        if (isset($data['inning_id'])) {
            $ob = [
                'id' => $data['inning_id'],
                'innings_status' => 'Finished',
            ];
            $this->matchQuery->endInningsQuery($ob);
        }

        // assigning team to next round fixtures
        $this->assignTeamsToNextRoundFixtures($data['id']);

        return $this->matchQuery->addMatchTossQuery($updated_data);
    }

    public function matchCommentaryText($value)
    {

        $text = '';

        if (isset($value['bowler']) && $value['batter']) {
            $text = $value['bowler']['first_name'] . $value['bowler']['last_name'] . ' to ' .
                $value['batter']['first_name'] . $value['batter']['last_name'];
        }

        if ($value['wicket_type'] || $value['wicket_type'] != null || $value['wicket_type'] != '') {
            $parts = explode("_", $value['wicket_type']);
            for ($i = 0; $i < sizeof($parts); $i++) {
                $text .= ' ' . $parts[$i];
            }
        } else if ($value['ball_type'] == 'LEGAL') {
            if ($value['runs'] + $value['extras'] == 0) $text .= ' no runs';
            else {
                if ($value['boundary_type'] == 'FOUR') {
                    $text .= ' FOUR';
                }
                if ($value['boundary_type'] == 'SIX') {
                    $text .= ' SIX';
                }
                if (!($value['boundary_type'] == 'FOUR' || $value['boundary_type'] == 'SIX')) {
                    $text .= ' ' . ($value['runs'] + $value['extras']) . ' runs';
                }
                if ($value['run_type'] == 'LB' || $value['run_type'] == 'B') {
                    if (!($value['boundary_type'] == 'FOUR') || !($value['boundary_type'] == 'SIX')) {
                        $text .= ' +' . ($value['extras']) . ' runs';
                        $text .= $value['run_type'];
                    } else {
                        $text .= ' runs';
                    }
                }
            }
        } else if ($value['ball_type'] == 'WD') {

            $text .= ' ' . $value['extras'] . ' Wide Ball';
        } else if ($value['ball_type'] == 'NB') {
            $text .= ' ' . ($value['runs'] + $value['extras']) . ' No Ball';
        } else if ($value['ball_type'] == 'DB') {
            $text .= ' Dead ball';
        }

        if ($value['wicket_type'] || $value['wicket_type'] != null || $value['wicket_type'] != '') {
            $del = $this->matchQuery->singleDelivery($value->id, $value->inning_id);

            $wicket_f = $del->wicket_type ? strtolower(str_replace("_", "", $del->wicket_type)) : '';
            if ($del->wicket_type == "BOWLED") {
                $bowler = $value['bowler']['first_name'] . ' ' . $value['bowler']['last_name'] . ' ' . $wicket_f;
            } else if ($del->wicket_type == "CAUGHT" && $del->caughtBy) {
                $bowler = $del->caughtBy->first_name . ' ' . $del->caughtBy->last_name . ' c ' . $value['bowler']['first_name'] . ' ' . $value['bowler']['last_name'] . ' b ';
            } else if ($del->wicket_type == "RUN_OUT" && $del->runOutBy) {
                $bowler = $del->runOutBy->first_name . ' ' . $del->runOutBy->last_name . ' ' . $wicket_f;
            } else if ($del->wicket_type == "STUMPED" && $del->stumpBy) {
                $bowler = $del->stumpBy->first_name . ' ' . $del->stumpBy->last_name . ' ' . $wicket_f;
            }

            // else if($del->wicket_type == "HIT_BALL_TWICE" || $del->wicket_type == "ABSENT"  || $del->wicket_type == "TIME_OUT" || $del->wicket_type == "RETIRED_HURT" || $del->wicket_type == "RETIRED" || $del->wicket_type == "RETIRED_OUT"){
            //     $bowler = $del->stumpBy->first_name.' '.$del->stumpBy->last_name.' '. $wicket_f;
            // }
            // HIT_BALL_TWICE,ABSENT,TIME_OUT,RETIRED_HURT,RETIRED,RETIRED_OUT

            else {
                $bowler = "";
            }

            $wicket = $bowler . ' (' . $value['batter']['first_name'] . ' ' . $value['batter']['last_name']
                . ') '
                . ($del->singleBatter ? ($del->singleBatter->runs_achieved . ' ('
                    . ' ' . $del->singleBatter->balls_faced . 'b '
                    . $del->singleBatter->sixes . 'x6 '
                    . $del->singleBatter->fours . 'x4 '
                    . ')') : '');
            // $wicket = $del->singleBatter;
        } else {
            $wicket = "";
        }
        if ($value['shot_position'] || $value['shot_position'] != '' || $value['shot_position'] != null) {
            $text .= `\n`;
            $text .= ' Towards ' . $value['shot_position'];
        }

        return ['text' => $text, 'wickets' => $wicket];
    }

    public function matchComentaryHighlight($data)
    {

        $commentary = $this->matchQuery->matchComentaryHighlightQuery($data);
        // return  $commentary ;
        $arr = array();
        if ($commentary->count() < 1) {
            return $arr;
        }

        if (sizeof($commentary) > 0)
            foreach ($commentary as $value) {
                $text = '';
                if (isset($value['bowler']) && $value['batter']) {
                    $text = $value['bowler']['first_name'] . $value['bowler']['last_name'] . ' to ' .
                        $value['batter']['first_name'] . $value['batter']['last_name'];
                }

                $obj = [];
                $obj['id'] = $value->id;
                $obj['fixture_id'] = $value->fixture_id;
                $obj['over_number'] = $value->over_number;
                $obj['ball_number'] = $value->ball_number;

                $text = $this->matchCommentaryText($value);

                $obj['commentary'] = $text;


                $status = isset($data['status']) && $data['status'];
                if ($status) {
                    if ($value->boundary_type && $value->boundary_type == "SIX") {
                        $obj['action'] = "6";
                    }
                    if ($value->boundary_type && $value->boundary_type == "FOUR") {
                        $obj['action'] = "4";
                    }
                    if ($value->wicket_type) {
                        $obj['action'] = "W";
                    }
                }
                array_push($arr, $obj);
            }

        return $arr;
        // $matchSummery = array();
        // foreach ($commentary as $c) {

        //     $c->overcount = $formatedCount--;
        //     foreach ($c->oversDelivery as $d) {

        //         $obj = [];
        //         $obj['id'] = $d->id;
        //         $obj['over_id'] = $d->over_id;
        //         $obj['deliveries_over'] = $c->overcount;
        //         $obj['commentary'] = $d->commentary;

        //         if ($d->ball_type == "LEGAL") {
        //             $obj['balls_count'] = ($c->overcount - 1) . '.' . $c->bowling_count--;
        //         } else {
        //             $obj['balls_count'] = (string)round(($c->overcount - 1) . '.' . $c->bowling_count + .10, 1);
        //         }

        // if ($d->boundary_type) {
        //     $obj['action'] = (string)$d->runs;
        // }
        // if ($d->wicket_type) {
        //     $obj['action'] = $d->wicket_type;
        // }
        //         $type = isset($data['type']) && $data['type'] == "highlight";
        //         if ($type && isset($obj['action'])) {
        //             array_push($matchSummery, $obj);
        //         }
        //         if (!$type) {
        //             array_push($matchSummery, $obj);
        //         }

        //     }


        // }

        return $commentary;
    }

    public function matchLiveCommentarty($id)
    {
        $commentary = $this->matchQuery->matchLiveCommentarty($id);
        return $commentary;

        $matchSummery = array();
        foreach ($commentary as $key => $c) {
            // $ob = $value->count();
            $legal_balls = $c && $c[0] ? $c[0]->legal_count : 0;
            $over_number = $c && $c[0] ? $c[0]->over_number : 0;
            foreach ($c as $d) {
                $obj = [];
                $obj['id'] = $d->id;
                // $obj['deliveries_over'] = $d->over_number;
                $obj['ball_type'] = $d->ball_type;
                $obj['commentary'] = $d->commentary;

                if ($d->ball_type == "LEGAL") {
                    $obj['balls_count'] = ($over_number - 1) . '.' . $legal_balls--;
                } else {
                    $obj['balls_count'] = (string)round(($over_number - 1) . '.' . $legal_balls + .10, 1);
                }

                $obj['action'] = $d->runs + $d->extras;
                // if ($d->boundary_type) {
                //     $obj['action'] = (string)$d->runs;
                // }
                // if ($d->wicket_type) {
                //     $obj['action'] = $d->wicket_type;
                // }
                // $type = isset($data['type']) && $data['type'] == "highlight";
                // if ($type && isset($obj['action'])) {
                //     array_push($matchSummery, $obj);
                // }
                // if (!$type) {
                array_push($matchSummery, $obj);
                // }
                // $d->balls_count = 0;
                // if($ob > 0){
                //     $d->balls_count = $ob --;
                // }
                // array_push($matchSummery, $d);
            }

            // foreach($value as $value2){
            //     foreach($value2->oversDelivery as $d){
            //         $ob = [];
            //         $ob['deliveries'] = $d;
            //         array_push($matchSummery, $d);
            //     }

            // }

            // $ob['arr_length'] = $value->count();
            // array_push($matchSummery, $value);
            // $c->overcount = $formatedCount--;
            // foreach ($value as $d) {

            //     $obj = [];
            //     $obj['id'] = $d->id;
            //     $obj['over_id'] = $d->over_id;
            //     $obj['deliveries_over'] = $c->over_number;
            //     $obj['commentary'] = $d->commentary;

            //     if ($d->ball_type == "LEGAL") {
            //         $obj['balls_count'] =  ($c->over_number-1) . '.' .  $c->bowling_count--;
            //     } else {
            //         $obj['balls_count'] = (string)round(($c->over_number-1) . '.' . $c->bowling_count + .10, 1);
            //     }

            //     if ($d->boundary_type) {
            //         $obj['action'] = (string)$d->runs;
            //     }
            //     if ($d->wicket_type) {
            //         $obj['action'] = $d->wicket_type;
            //     }
            //     $type = isset($data['type']) && $data['type'] == "highlight";
            //     if ($type && isset($obj['action'])) {
            //         array_push($matchSummery, $obj);
            //     }
            //     if (!$type) {
            //         array_push($matchSummery, $obj);
            //     }

            // }


        }

        return $matchSummery;
    }


    public function calculateDeliveries($innings_id, $delivery_id = null)
    {
        return $total_run = $this->matchQuery->calculateDeliveriesQuery($innings_id, $delivery_id);
    }

    public function checkIsSquadReady($team_id)
    {
        $number = $this->matchQuery->countMainSquad([
            'team_id' => $team_id,
            'squad_type' => 'MAIN'
        ]);
        // Log::channel('slack')->info('number', ['d' => $number]);
        return $number >= 3;
    }

    public function isStrikeChange($data)
    {
        if (
            $data['runs'] == 1 ||
            $data['runs'] == 3 ||
            (
                ($data['runs'] == 4 ||
                    $data['runs'] == 5 ||
                    $data['runs'] == 6 ||
                    $data['runs'] == 7
                ) &&
                $data['boundary_type'] == null

            ) ||
            (
                ($data['run_type'] == 'LB' ||
                    $data['run_type'] == 'B'
                ) &&
                ($data['extras'] == 1 ||
                    $data['extras'] == 3
                )
            ) ||
            ($data['ball_type'] == 'WD' &&
                ($data['extras'] == 2 ||
                    $data['extras'] == 4 ||
                    ($data['extras'] == 5 &&
                        $data['boundary_type'] == null
                    )
                )
            ) ||
            ($data['ball_type'] == 'NB' &&
                ($data['extras'] == 2 ||
                    $data['extras'] == 4 ||
                    ($data['extras'] == 5 &&
                        $data['boundary_type'] == null
                    ) ||
                    ($data['extras'] == 6
                    ) ||
                    ($data['extras'] == 8
                    )
                )
            )
        ) {

            return true;
        } else false;
    }

    public function getManOftheMatch($id)
    {

        // return

        $data = $this->matchQuery->getWinningTeamByFixtureId($id);

        $team_innigs = $this->matchQuery->getInningsByTeamIdFixtureId($data['match_winner_team_id'], $id, 1);
        $team_balling_innigs = $this->matchQuery->getInningsByTeamIdFixtureId($data['match_winner_team_id'], $id, 2);

        $playing_eleven = $this->matchQuery->getPlayingEleven($data['match_winner_team_id'], $id);
        $player_group = $playing_eleven->groupBy('player_id');

        foreach ($player_group as $key => $val) {
            $val[0]['poinits'] = 0;
            $val[0]['assist_by'] = 0;
            $val[0]['caught_by'] = 0;
            $val[0]['stumped_by'] = 0;
            $val[0]['runs'] = 0;
            $val[0]['balls_faced'] = 0;
            $val[0]['wickets'] = 0;
            $val[0]['runs_gave'] = 0;
            $val[0]['maiden_overs'] = 0;
            $val[0]['balls_bowled'] = 0;
        }
        $n = isset($team_innigs['total_overs']) ? $team_innigs['total_overs'] : 0;

        $whole = floor($n);      // 1
        $fraction = $n - $whole;
        $total_ball = $fraction * 10 + ($whole * 6);
        $total_ball_c = $total_ball;
        if ($total_ball <= 0) {
            $total_ball_c = 1;
        }
        $n2 = isset($team_balling_innigs['total_overs']) ? $team_balling_innigs['total_overs'] : 0;

        $whole2 = floor($n2);      // 1
        $fraction2 = $n2 - $whole2;
        $total_ball2 = $fraction2 * 10 + ($whole2 * 6);
        $total_ball_c2 = $total_ball2;
        if ($total_ball2 <= 0) {
            $total_ball_c2 = 1;
        }
        $team_sr = isset($team_innigs['total_runs']) ? ($team_innigs['total_runs'] / $total_ball_c) * 100 : 0;
        $team_ball_sr = isset($team_balling_innigs['total_runs']) ? ($team_balling_innigs['total_runs'] / $total_ball_c2) : 0;

        $batting_innings = $this->matchQuery->getAllPlayersWithBattingInnings($data['match_winner_team_id'], $id);
        $batting_innings_group = $batting_innings->groupBy('batter_id');
        foreach ($batting_innings_group as $key => $val) {
            if (isset($player_group[$key])) {
                $runs_point = $val[0]['runs_achieved'] * .1;
                if ($val[0]['runs_achieved'] > 50) {
                    $runs_point += ($runs_point * 10) / 100;
                }
                $balls_faced = $val[0]['balls_faced'];
                $balls_faced_c = $val[0]['balls_faced'];
                if ($balls_faced <= 0) {
                    $balls_faced_c = 1;
                }
                $player_sr = ($val[0]['runs_achieved'] / $balls_faced_c) * 100;
                $sr_point = (($player_sr) / ($team_sr)) * ($player_sr) - ($team_sr) * .10;

                $player_group[$key][0]['runs'] = $val[0]['runs_achieved'];
                $player_group[$key][0]['balls_faced'] = $balls_faced;
                $player_group[$key][0]['poinits'] = round(($sr_point + $runs_point), 2);
            }
        }


        $bowlling_innings = $this->matchQuery->getAllPlayersWithBollingInnings($data['match_winner_team_id'], $id);
        $bowlling_innings_group = $bowlling_innings->groupBy('bowler_id');
        foreach ($bowlling_innings_group as $key => $val) {
            if (isset($player_group[$key])) {
                $wicket_point = $val[0]['wickets'] * 2.5;
                if ($val[0]['wickets'] > 1) {
                    $wicket_point += .1 * $val[0]['wickets'];
                }
                if ($val[0]['maiden_overs'] > 1) {
                    $wicket_point += 1 * $val[0]['maiden_overs'];
                }

                $assist_by = $batting_innings->where('assist_by', $key)->count();
                $caught_by = $batting_innings->where('caught_by', $key)->count();
                $stumped_by = $batting_innings->where('stumped_by', $key)->count();
                $wicket_point += ($assist_by + $caught_by + $stumped_by) * 1.25;
                $player_group[$key][0]['wickets'] = $val[0]['wickets'];
                $player_group[$key][0]['assist_by'] = $assist_by;
                $player_group[$key][0]['caught_by'] = $caught_by;
                $player_group[$key][0]['stumped_by'] = $stumped_by;
                $player_group[$key][0]['runs_gave'] = $val[0]['runs_gave'];
                $player_group[$key][0]['maiden_overs'] = $val[0]['maiden_overs'];
                $player_group[$key][0]['balls_bowled'] = $val[0]['balls_bowled'];
                $player_group[$key][0]['poinits'] = round(($player_group[$key][0]['poinits'] + $wicket_point), 2);
                // return $player_group[$key];
            }
        }

        $new_array = [];
        foreach ($player_group as $key => $val) {
            $val[0]['id'] = $val[0]['player_id'];
            array_push($new_array, $val[0]);
        }
        for ($i = 0; $i < sizeof($new_array); $i++) {
            $temp = $new_array[$i];

            $flag = $i;
            for ($j = $i + 1; $j < sizeof($new_array); $j++) {
                if ($temp['poinits'] < $new_array[$j]['poinits']) {
                    $temp = $new_array[$j];
                    $flag = $j;
                }
            }

            if ($flag != $i) {
                $temp2 = $new_array[$i];
                $new_array[$flag] = $temp2;
                $new_array[$i] = $temp;
            }
        }
        return $new_array;
    }

    public function shareInnings($id)
    {
        $data = $this->matchQuery->getInnigsWithFixtureQuery($id);

        $text = '';

        if ($data['is_first_innings'] == 1 && isset($data['fixture']) && isset($data['batting_team'])) {

            if (isset($data['batting_team']) && isset($data['bowling_team'])) {
                $text .= $data['batting_team']['team_name'] . ' vs ' . $data['bowling_team']['team_name'];
            }
            if (isset($data['fixture']) && isset($data['fixture']['ground'])) {
                $text .= ' at ' . $data['fixture']['ground']['ground_name'] . ' (' . $data['fixture']['match_overs'] . ')Ov.';
            }
            $text .= $data['batting_team']['team_name'] . ': ' . $data['total_runs'] . '/' . $data['total_wickets'];
            $text .= ' in at (' . $data['total_overs'] . ')Ov. Toss: ';


            if ($data['fixture']['toss_winner_team_id'] == $data['batting_team_id']) {
                $text .= $data['batting_team']['team_name'] . ' opt to bat';
            }
            if ($data['fixture']['toss_winner_team_id'] == $data['bowling_team_id']) {
                $text .= $data['bowling_team']['team_name'] . ' opt to ball';
            }
        } else {
            $text .= $data['batting_team']['team_name'] . ': ' . $data['total_runs'] . '/' . $data['total_wickets'];
            $text .= ' in at (' . $data['total_overs'] . ')Ov. ' . $data['batting_team']['team_name'] . ' required ';
            $runs = $data['fixture']['home_team_runs'] - $data['total_runs'];
            $total_match_ball = $data['fixture']['match_overs'] * 6;
            $n = $data['total_overs'];
            $whole = floor($n);      // 1
            $fraction = $n - $whole;
            $total_ball = $fraction * 10 + ($whole * 6);
            $remaining_ball = $total_match_ball - $total_ball;;
            $text .= $runs . ' runs in ' . $remaining_ball . ' balls';
        }
        return [
            'msg' => $text
        ];
    }

    public function assignTeamsToNextRoundFixtures($fixtureId)
    {
        $fixture = $this->matchQuery->getFixtureById($fixtureId) ?? null;
        //        Log::channel('slack')->info('fixture', ['data' => $fixture]);
        if (isset($fixture) and $fixture['tournament_id'] and $fixture['round_type'] != 'FINAL' and $fixture['round_type'] != 'THIRD PLACE') {
            $tournament = $this->matchQuery->getTournamentById($fixture['tournament_id']);
            //            Log::channel('slack')->info('tournament', ['data' => $tournament]);
            if ($fixture['fixture_type'] == 'GROUP') {
                //                Log::channel('slack')->info('test');
                $totalUnfinishedMatches = $this
                    ->matchQuery
                    ->countTournamentMatchesByRound($fixture['tournament_id'], null, $fixture['league_group_id'], $isMatchFinished = 0);
                if (!$totalUnfinishedMatches) {

                    $nextRoundType = 'KNOCK OUT';
                    $groupWinners = $tournament['group_winners'];
                    $nextRound = $nextRoundGroup = null;

                    if ($tournament['league_format'] == 'SUPER LEAGUE') {
                        $currentRoundIndex = array_search($fixture['round_type'], array_column((array)$tournament['group_settings'], 'round_type'));
                        $nextRoundType = $tournament['group_settings'][$currentRoundIndex + 1]['type'];
                        $nextRound = $tournament['group_settings'][$currentRoundIndex + 1]['round_type'];
                        $groupWinners = $this->universalService->getRoundInfo($nextRound);
                        // Log::channel('slack')->info('testing');
                    }

                    // Log::channel('slack')->info('group_settings', ['d' => $tournament['group_settings']]);
                    // Log::channel('slack')->info('nextRoundType', ['d' => $nextRoundType]);
                    // Log::channel('slack')->info('nextRound', ['d' => $nextRound]);
                    // Log::channel('slack')->info('groupWinners', ['d' => $groupWinners]);

                    $teams = $this
                        ->tournamentService
                        ->tournamentPointsTable([
                            'tournament_id' => $fixture['tournament_id'],
                            'league_group_id' => $fixture['league_group_id'],
                            'team_limit' => $groupWinners,
                        ])
                        ->first();

                    $teams = count($teams->teams_results) ? collect($teams->teams_results)->pluck('team_id') : [];
                    // Log::channel('slack')->info('teams', ['d' => $teams]);

                    foreach ($teams as $index => $team) {
                        $n = $index + 1;
                        $tempTeam = "G-{$fixture['league_group_id']}-{$n}";

                        if ($nextRoundType == 'KNOCK OUT') {
                            $nextKnockOutFixture = $this->matchQuery->getNextFixture($fixture['tournament_id'], $tempTeam);
                            if (isset($nextKnockOutFixture)) {

                                $data = [];
                                $data['id'] = $nextKnockOutFixture['id'];

                                if ($nextKnockOutFixture['temp_team_one'] == $tempTeam) {
                                    $data['home_team_id'] = $team;
                                } else if ($nextKnockOutFixture['temp_team_two'] == $tempTeam) {
                                    $data['away_team_id'] = $team;
                                }

                                $this->matchQuery->updateMatchQuery($data);
                            }
                        } else if ($nextRoundType == 'LEAGUE') {
                            // Log::channel('slack')->info('LEAGUE');
                            if (!isset($nextRoundGroup)) {
                                $nextRoundGroup = $this->tournamentQuery->getNextGroupByTournament($tournament['id'], $nextRound);
                            }
                            // Log::channel('slack')->info('nextRoundGroup', ['d' => $nextRoundGroup]);
                            $this->tournamentQuery->addTeamToGroupQuery([
                                'tournament_id' => $tournament['id'],
                                'league_group_id' => $nextRoundGroup['id'],
                                'team_id' => $team,
                            ]);

                            $nextGroupRoundFixtures = $this->matchQuery->getNextFixtures($fixture['tournament_id'], $tempTeam);
                            // Log::channel('slack')->info('nextGroupRoundFixtures', ['d' => $nextGroupRoundFixtures]);
                            if (isset($nextGroupRoundFixtures)) {
                                // Log::channel('slack')->info('nextGroupRoundFixtures', ['d' => $nextGroupRoundFixtures]);
                                foreach ($nextGroupRoundFixtures as $nextGroupRoundFixture) {
                                    $data = [];
                                    $data['id'] = $nextGroupRoundFixture['id'];

                                    if ($nextGroupRoundFixture['temp_team_one'] == $tempTeam) {
                                        $data['home_team_id'] = $team;
                                    } else if ($nextGroupRoundFixture['temp_team_two'] == $tempTeam) {
                                        $data['away_team_id'] = $team;
                                    }

                                    $this->matchQuery->updateMatchQuery($data);
                                }
                            }
                        }
                    }
                }
            } else if (
                $tournament['tournament_type'] == 'IPL SYSTEM'
                and $fixture['round_type'] == 'PLAY OFF'
                and $fixture['knockout_round'] == 4
            ) {
                // for ipl playoff 4
                //                Log::channel('slack')->info('hello');
                $tempWinnerTeam = "{$fixtureId}-W";
                $tempLoserTeam = "{$fixtureId}-L";

                $winnerNextFixture = $this->matchQuery->getNextFixture($fixture['tournament_id'], $tempWinnerTeam);
                $loserNextFixture = $this->matchQuery->getNextFixture($fixture['tournament_id'], $tempLoserTeam);
                //                Log::channel('slack')->info('$winnerNextFixture', ['data' => $winnerNextFixture]);
                //                Log::channel('slack')->info('$loserNextFixture', ['data' => $loserNextFixture]);

                if (isset($winnerNextFixture)) {
                    $data = [];
                    $data['id'] = $winnerNextFixture['id'];

                    if ($winnerNextFixture['temp_team_one'] == $tempWinnerTeam) {
                        $data['home_team_id'] = $fixture['match_winner_team_id'];
                    } else if ($winnerNextFixture['temp_team_two'] == $tempWinnerTeam) {
                        $data['away_team_id'] = $fixture['match_winner_team_id'];
                    }

                    //                    Log::channel('slack')->info('$data', ['data' => $data]);

                    $this->matchQuery->updateMatchQuery($data);
                }

                if (isset($loserNextFixture)) {
                    $data = [];
                    $data['id'] = $loserNextFixture['id'];

                    if ($loserNextFixture['temp_team_one'] == $tempLoserTeam) {
                        $data['home_team_id'] = $fixture['match_loser_team_id'];
                    } else if ($loserNextFixture['temp_team_two'] == $tempLoserTeam) {
                        $data['away_team_id'] = $fixture['match_loser_team_id'];
                    }

                    $this->matchQuery->updateMatchQuery($data);

                    //                    Log::channel('slack')->info('$data', ['data' => $data]);
                }
            } else if ($fixture['fixture_type'] == 'KNOCKOUT') {
                $tempTeam = "{$fixtureId}-W";

                $nextFixture = $this->matchQuery->getNextFixture($fixture['tournament_id'], $tempTeam);
                $data = [];
                $data['id'] = $nextFixture['id'];

                if ($nextFixture['temp_team_one'] == $tempTeam) {
                    $data['home_team_id'] = $fixture['match_winner_team_id'];
                } else if ($nextFixture['temp_team_two'] == $tempTeam) {
                    $data['away_team_id'] = $fixture['match_winner_team_id'];
                }

                $this->matchQuery->updateMatchQuery($data);

                if ($fixture['round_type'] == 'SEMI-FINAL' and $tournament['third_position'] == 'YES') {
                    $tempTeam = "{$fixtureId}-L";

                    $nextFixture = $this->matchQuery->getNextFixture($fixture['tournament_id'], $tempTeam);
                    $data = [];
                    $data['id'] = $nextFixture['id'];

                    if ($nextFixture['temp_team_one'] == $tempTeam) {
                        $data['home_team_id'] = $fixture['match_loser_team_id'];
                    } else if ($nextFixture['temp_team_two'] == $tempTeam) {
                        $data['away_team_id'] = $fixture['match_loser_team_id'];
                    }

                    $this->matchQuery->updateMatchQuery($data);
                }
            }
        }
    }
}
