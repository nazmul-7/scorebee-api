<?php

namespace App\Http\Controllers\Match;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Log;

class MatchController extends Controller
{

    private $matchService;

    public function __construct(MatchService $matchService)
    {
        $this->matchService = $matchService;
    }

    public function getChallengedMatchesList(Request $request) {

        $validator = Validator::make($request->all(), [
            'club_owner_id' => 'required',
            'limit' => 'nullable',
        ], [
            'club_owner_id.required' => "Club is required.",
            'club_owner_id.exist' => "Club doesn't exist.",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        return $this->matchService->getChallengedMatchesList($request->all());
    }

    public function getMyMatchesList(Request $request)
    {
        return $this->matchService->getMyMatchesList($request->all());
    }
    public function getMatchesByRound($tounament_id, $round_type)
    {
        return $this->matchService->getMatchesByRound($tounament_id, $round_type);
    }

    public function getAllMatchesList(Request $request)
    {
        return $this->matchService->getAllMatchesList($request->all());
    }

    public function getAllMatchesListV2(Request $request){
        return $this->matchService->getAllMatchesListV2($request->all());
    }

    public function getAllLiveMatchesList(Request $request)
    {
        return $this->matchService->getAllLiveMatchesList($request->all());
    }

    public function getMatchesListByType(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'match_type' => 'required|in:ALL,UPCOMING,LIVE,RECENT',
        ], [
            'match_type.required' => "Match Type is required.",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        return $this->matchService->getMatchesListByType($request->all());
    }

    public function getAllMatchesByGroup($gId, Request $request)
    {
        return $this->matchService->getAllMatchesByGroup($gId, $request->all());
    }

    public function getTournamentMatches($tid, Request $request)
    {
        $data = $request->all();
        $data['id'] = $tid;
        $validator = Validator::make($data, [
            'id' => 'required|exists:tournaments,id',
        ], [
            'id.required' => "Tournament is required.",
            'id.exists' => "Tournament doesn`t exist.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->matchService->getTournamentMatches($data);
    }

    public function getTournamentMatchesByStatus($tid, Request $request)
    {
        $data = $request->all();
        $data['id'] = $tid;
        $validator = Validator::make($data, [
            'id' => 'required|exists:tournaments,id',
            'status' => 'required|in:LIVE,RECENT,UPCOMING',
        ], [
            'id.required' => "Tournament is required.",
            'id.exists' => "Tournament doesn`t exist.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->matchService->getTournamentMatchesByStatus($data);
    }

    public function getTournamentMatchesTwo($tid, Request $request)
    {
        $data = $request->all();
        $data['id'] = $tid;
        $validator = Validator::make($data, [
            'id' => 'required|exists:tournaments,id',
        ], [
            'id.required' => "Tournament is required.",
            'id.exists' => "Tournament doesn`t exist.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->matchService->getTournamentMatchesTwo($data);
    }

    public function getSingleMatch($id)
    {
        return $this->matchService->getSingleMatch($id);
    }

    public function getMatchOfficial(Request $request)
    {
        return $this->matchService->getMatchOfficial($request->all());
    }

    public function createAnIndividualMatch(Request $request){
        $validator = Validator::make($request->all(), [
            'team_a' => ['required', 'exists:teams,id'],
            'team_b' => ['required', 'exists:teams,id'],
            'fixture_type' => ['required', 'in:CLUB_CHALLENGE,TEAM_CHALLENGE'],
            'challenge_request_id' => ['required_if:fixture_type,CLUB_CHALLENGE', 'integer', 'exists:individual_club_challenges,id']
        ], [
            'team_a.required' => "Team A is required.",
            'team_a.exists' => "Team A doesn't exist.",
            'team_b.required' => "Team B is required.",
            'team_b.exists' => "Team B doesn't exist.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->matchService->createAnIndividualMatch($request->all());
    }

    public function startAMatch(Request $request)
    {

        // $validator = Validator::make($request->all(),[
        //     'fixture_id' => 'required|number',
        //     'match_overs' => 'required|number',
        //     'match_type' => 'required|string|max:50',
        //     'power_play' => 'required|string',
        //     'ground_id' => 'required|number',
        // ],[
        //     'fixture_id.required_if' => "fixture id is required.",
        //     'match_type.required_if' => "match type is required.",
        //     'match_overs.required_if' => "match overs is required.",
        //     'power_play.required_if' => "power play is required.",
        //     'ground_id.required_if' => "select a groud is required.",
        // ]);

        // if($validator->fails()){
        //     return response()->json($validator->errors(), 422);
        // }

        return $this->matchService->startAMatch($request->all());
    }

    public function getEditableMatchDetails(Request $request)
    {
        // Log::channel('slack')->info('request', ['d' => $request->all()]);
        $validator = Validator::make($request->all(), [
            'fixture_id' => 'required|integer|exists:fixtures,id',
        ], [
            'fixture_id.required' => "Fixture is required.",
            'fixture_id.exists' => "Fixture doesn't exists.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->matchService->getEditableMatchDetails($request->all());
    }

    public function updateMatchPowerPlays(Request $request)
    {

        return $this->matchService->updateMatchPowerPlays($request->all());
    }
    public function updateEditableMatchDetails(Request $request)
    {
        if (isset($request['match_date'])) $request['match_date'] = date('Y-m-d', strtotime($request->input('match_date')));
        if (isset($request['start_time'])) $request['start_time'] = date('H:i:s', strtotime($request->input('start_time')));

        $validator = Validator::make($request->all(), [
            'fixture_id' => 'required|integer|exists:fixtures,id',
            // 'match_overs' => 'required|numeric|min:1|max:50',
            // 'overs_per_bowler' => 'required|numeric|min:1|max:50',
            // 'ground_id' => 'required|integer|exists:grounds,id',
            'match_date' => 'nullable|date_format:Y-m-d',
            'start_time' => 'nullable|date_format:H:i:s',
        ], [
            'fixture_id.required' => "Fixture is required.",
            // 'fixture_id.exists' => "Fixture doesn't exists.",
            // 'match_overs.required' => "Overs is required.",
            // 'overs_per_bowler.required' => "Overs per bowler is required.",
            // 'ground_id.required' => "Ground is required.",
            // 'ground_id.exists' => "Ground doesn't exists.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->matchService->updateEditableMatchDetails($request->all());
    }

    public function startAInnings(Request $request)
    {

        return $this->matchService->startAInnings($request->all());
    }

    public function endAnInnings(Request $request)
    {

        return $this->matchService->endAnInnings($request->all());
    }

    public function addMatchOfficial(Request $request)
    {

        // $validator = Validator::make($request->all(), [
        //     'fixture_id' => 'required|number',
        //     'official_type' => 'required|string|max:191',
        //     'user_id' => 'required|number',
        // ], [
        //     'fixture_id.required_if' => "fixture id is required.",
        //     'official_type.required_if' => "official type is required.",
        //     'user_id.required_if' => "user is required.",
        // ]);

        // if ($validator->fails()) {
        //     return response()->json($validator->errors(), 422);
        // }

        return $this->matchService->addMatchOfficial($request->all());
    }
    public function getMatchOfficial_by_fixture($id)
    {
        return $this->matchService->getMatchOfficial_by_fixture($id);
    }
    public function endInnings(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'inning_id' => 'required',
        ], [
            'inning_id.required_if' => "Innings id is required.",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        return $this->matchService->endInnings($request->all());
    }
    public function endMatch(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'fixture_id' => 'required',
        ], [
            'fixture_id.required_if' => "Innings id is required.",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        return $this->matchService->endMatch($request->all());
    }
    public function getEndMatchStatus($id)
    {

        return $this->matchService->getEndMatchStatus($id);
    }

    public function storeManOftheMatch(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'fixture_id' => 'required',
            'player_id' => 'required',
        ], [
            'fixture_id.required_if' => "Fixture id is required.",
            'player_id.required_if' => "Player id is required.",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        return $this->matchService->storeManOftheMatch($request->all());
    }

    public function removeMatchOfficial(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'id' => 'required|number',
            'fixture_id' => 'required|number',
        ], [
            'fixture_id.required_if' => "fixture id is required.",
            'id.required_if' => "id  is required.",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        return $this->matchService->removeMatchOfficial($request->all());
    }

    public function addMatchToss(Request $request)
    {

        // $validator = Validator::make($request->all(),[
        //     'fixture_id' => 'required|number',
        //     'toss_winner_team_id' => 'required|number',
        //     'team_elected_to' => 'required|string|max:191',
        //     'toss_losser_team_id' => 'required|number',
        // ],[
        //     'fixture_id.required_if' => "fixture id is required.",
        //     'toss_winner_team_id.required_if' => "toss winner is required.",
        //     'toss_losser_team_id.required_if' => "toss losser is required.",
        //     'team_elected_to.required_if' => "elected is required.",
        // ]);

        // if($validator->fails()){
        //     return response()->json($validator->errors(), 422);
        // }

        return $this->matchService->addMatchToss($request->all());
    }

    public function getPlyaingEleven(Request $request)
    {
        // $validator = Validator::make($request->all(),[
        //     'fixture_id' => 'required|number',
        //     'team_id' => 'required|number',
        // ],[
        //     'fixture_id.required_if' => "fixture id is required.",
        //     'team_id.required_if' => "team id is required.",
        // ]);
        // if($validator->fails()){
        //     return response()->json($validator->errors(), 422);
        // }
        return $this->matchService->getPlyaingEleven($request->all());
    }
    public function getAllPlayerOfMatch(Request $request)
    {
        return $this->matchService->getAllPlayerOfMatch($request->all());
    }
    public function changeWicketkeeper(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fixture_id' => 'required',
            'team_id' => 'required',
            'player_id' => 'required',
        ], [
            'fixture_id.required_if' => "Fixture id is required.",
            'team_id.required_if' => "Team id is required.",
            'player_id.required_if' => "Player id is required.",
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        return $this->matchService->changeWicketkeeper($request->all());
    }
    public function replaceBowler(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'inning_id' => 'required',
            // 'team_id' => 'required',
            'bowler_id' => 'required',
        ], [
            'inning_id.required_if' => "Innings id is required.",
            // 'team_id.required_if' => "Team id is required.",
            'bowler_id.required_if' => "Bowler id is required.",
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        return $this->matchService->replaceBowler($request->all());
    }
    public function getPlyaingElevenWithSubs(Request $request)
    {
        // $validator = Validator::make($request->all(),[
        //     'fixture_id' => 'required|number',
        //     'team_id' => 'required|number',
        // ],[
        //     'fixture_id.required_if' => "fixture id is required.",
        //     'team_id.required_if' => "team id is required.",
        // ]);
        // if($validator->fails()){
        //     return response()->json($validator->errors(), 422);
        // }
        // return $this->matchService->getPlyaingElevenWithSubs($request->all());
    }

    public function startNextOver(Request $request){

        $validator = Validator::make($request->all(),[
            'inning_id' => 'required|integer',
            'bowler_id' => 'required|integer',
            'non_striker_id' => 'required|integer',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 422);
        }

        return $this->matchService->startANewOver($request->all());
    }

    public function setNextBatter(Request $request){
        $validator = Validator::make($request->all(),[
            'inning_id' => 'required|integer',
            'team_id' => 'required|integer',
            'new_batter_id' => 'required|integer',
            'new_batter_is_on_strike' => 'required|boolean',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 422);
        }

        return $this->matchService->setNextBatter($request->all());
    }

    public function storeDelivery(Request $request)
    {
        // $validator = Validator::make($request->all(),[
        //     'fixture_id' => 'required|number',
        //     'tournament_id' => 'required|number',
        //     'inning_id' => 'required|number',
        //     'over_id' => 'required|number',
        //     'bowler_id' => 'required|number',
        //     'batter_id' => 'required|number',
        //     'non_striker_id' => 'required|number',
        //     // 'extras' => 'required|number',
        //     // 'runs' => 'required|number',
        //     // 'ball_type' => 'required|string',
        //     // 'run_type' => 'required|string',
        //     // 'shot_x' => 'required|number',
        //     // 'shot_y' => 'required|number',
        //     // 'boundary_type' => 'required|number',
        //     'match_type' => 'required|string',

        // ],[
        //     'fixture_id.required_if' => "fixture is required.",
        //     'tournament_id.required_if' => "ournament is required.",
        //     'inning_id.required_if' => "inning is required.",
        //     'over_id.required_if' => "over is required.",
        //     'bowler_id.required_if' => "bowler is required.",
        //     'batter_id.required_if' => "batter is required.",
        //     'non_striker_id.required_if' => "non striker is required.",
        //     // 'extras.required_if' => "extras is required.",
        //     // 'runs.required_if' => "runs is required.",
        //     // 'ball_type.required_if' => "ball type is required.",
        //     // 'run_type.required_if' => "run type is required.",
        //     // 'shot_x.required_if' => "shot x is required.",
        //     // 'shot_y.required_if' => "shot y is required.",
        //     // 'boundary_type.required_if' => "boundary type is required.",
        //     'match_type.required_if' => "match type is required.",
        // ]);
        // if($validator->fails()){
        //     return response()->json($validator->errors(), 422);
        // }
        return $this->matchService->storeDelivery($request->all());
    }

    public function getSingleMatchWithAllDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fixture_id' => 'required|exists:fixtures,id',
        ], [
            'fixture_id.exists' => "Match doesn't exists.",
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        return $this->matchService->getSingleMatchWithAllDetails($request->all());
    }

    public function getMatchLiveScore(Request $request, $innings_id)
    {
        // $validator = Validator::make($request->all(),[
        //     'innings_id' => 'required|number',
        // ],[
        //     'fixture_id.required_if' => "fixture is required.",
        // ]);
        // if($validator->fails()){
        //     return response()->json($validator->errors(), 422);
        // }
        return $this->matchService->getMatchLiveScore($innings_id);
    }
    public function getStreamMatchLiveScore(Request $request, $innings_id)
    {

        return $this->matchService->getStreamMatchLiveScore($innings_id);
    }
    public function getStreamMatchLiveScore_kamran(Request $request, $innings_id)
    {

        return $this->matchService->getStreamMatchLiveScore_kamran($innings_id);
    }

    public function delDelivery(Request $request)
    {
        // $validator = Validator::make($request->all(),[
        //     'innings_id' => 'required|number',
        // ],[
        //     'fixture_id.required_if' => "fixture is required.",
        // ]);
        // if($validator->fails()){
        //     return response()->json($validator->errors(), 422);
        // }
        return $this->matchService->delDelivery($request->all());
    }

    public function getMatchInnings($fixture_id)
    {

        return $this->matchService->getMatchInnings($fixture_id);
    }
    public function getPanaltyOrBonusRuns(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'innings_id' => 'required|exists:innings,id',
            'type' => 'required|in:PENALTY,BONUS',

        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        return $this->matchService->getPanaltyOrBonusRuns($request->all());
    }

    public function removePanaltyOrBonusRuns(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:panalties,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        return $this->matchService->removePanaltyOrBonusRuns($request->all());
    }

    public function storePanalty(Request $request)
    {
        return $this->matchService->storePanalty($request->all());
    }
    public function insertBreakData(Request $request)
    {
        return $this->matchService->insertBreakData($request->all());
    }
    public function insertScorerNotes(Request $request)
    {
        return $this->matchService->insertScorerNotes($request->all());
    }

    public function breakStop(Request $request)
    {
        return $this->matchService->breakStop($request->all());
    }
    public function closestCoordinate(Request $request)
    {
        return $this->matchService->closestCoordinate($request->all());
    }
    public function getFieldCoordinate(Request $request)
    {
        return $this->matchService->getFieldCoordinate($request->all());
    }


    public function startANewOver(Request $request)
    {

        return $this->matchService->startANewOver($request->all());
    }

    public function changeStrike(Request $request)
    {

        return $this->matchService->changeStrike($request->all());
    }

    public function changeABatsman(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'inning_id' => 'required',
            'batter_id' => 'required',
            'non_striker_id' => 'required',
        ], [
            'inning_id.required_if' => "Innings id is required.",
            'batter_id.required_if' => "New Batsman id is required.",
            'non_striker_id.required_if' => "Non Striker Batsman id is required.",
        ]);

        return $this->matchService->changeABatsman($request->all());
    }
    public function storeFixtureMedia(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fixture_id' => 'required',
            'type' => 'required',
            'url' => 'required',
            'extension_type' => 'required',
        ], [
            'fixture_id.required' => "Fixture id is required.",
            'type.required' => "Type is required.",
            'url.required' => "Url is required.",
            'extension_type.required' => "Extension type is required.",
        ]);

        return $this->matchService->storeFixtureMedia($request->all());
    }
    public function retiredBatsman(Request $request)
    {

        // return $this->matchService->retiredBatsman($request->all());
    }

    public function calculateDeliveries($innings_id)
    {


        return $this->matchService->calculateDeliveries($innings_id);
    }

    public function getNotOutBatsman(Request $request)
    {


        return $this->matchService->getNotOutBatsman($request->all());
    }
    public function unexpectedEndMatch(Request $request)
    {


        return $this->matchService->unexpectedEndMatch($request->all());
    }

    public function singleMatchScored($id)
    {
        return $this->matchService->singleMatchScored($id);
    }

    public function getInningsLiveScore(Request $request, $id)
    {
        $data = $request->all();
        $data['fixture_id'] = isset($id) ? $id : 0;

        $validator = Validator::make($data, [
            'fixture_id' => 'required|exists:fixtures,id',
        ], [
            'fixture_id.required' => "Fixture is required.",
            'fixture_id.exists' => "Fixture doesn`t exist.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->matchService->getInningsLiveScore($data);
    }

    public function getCurrentInningsLiveScore(Request $request){
        $validator = Validator::make($request->all(), [
            'fixture_id' => 'required|exists:fixtures,id',
        ], [
            'fixture_id.required' => "Fixture is required.",
            'fixture_id.exists' => "Fixture doesn't exist.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->matchService->getCurrentInningsLiveScore($request->all());
    }



    public function singleTeamScored(Request $request, $id)
    {
        //    $request->merge(['inning_id' => $request->route('id')]);
        $data = $request->all();
        $data['inning_id'] = isset($id) ? $id : 0;

        $validator = Validator::make($data, [
            'inning_id' => 'required|exists:innings,id',
        ], [
            'inning_id.required' => "Inning is required.",
            'inning_id.exists' => "Inning doesn`t exist.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->matchService->singleTeamScored($data);
    }

    public function deliveriesByOver(Request $request, $id)
    {

        $data = $request->all();
        $data['fixture_id'] = isset($id) ? $id : 0;
        $validator = Validator::make($data, [
            'fixture_id' => 'required|exists:fixtures,id',
            'last_over_number' => 'nullable|max:20',
            'inning_id' => 'required_with:last_over_number|exists:innings,id|max:20',
        ], [
            'fixture_id.required' => "Fixture is required.",
            'fixture_id.exists' => "Fixture doesn`t exist.",
            'last_id.exists' => "Overs doesn`t exist.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->matchService->deliveriesByOver($data);
    }

    public function getCurrentInningsLive(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fixture_id' => 'required|exists:fixtures,id',
        ], [
            'fixture_id.required' => "Fixture is required.",
            'fixture_id.exists' => "Fixture doesn't exists.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->matchService->getCurrentInningsLive($request->all());
    }


    public function matchComentaryHighlight(Request $request, $id)
    {

        $data = $request->all();
        $data['fixture_id'] = $id;
        $validator = Validator::make($data, [
            'fixture_id' => 'required|exists:fixtures,id',
            'last_id' => 'nullable|max:20',
        ], [
            'fixture_id.required' => "Fixture is required.",
            'fixture_id.exists' => "Fixture doesn`t exist.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->matchService->matchComentaryHighlight($data);
    }

    public function matchLiveCommentarty(Request $request, $id)
    {
        return $this->matchService->matchLiveCommentarty($id);
    }
    public function getManOftheMatch($id)
    {
        return $this->matchService->getManOftheMatch($id);
    }
    public function shareInnings($id)
    {
        return $this->matchService->shareInnings($id);
    }
}
