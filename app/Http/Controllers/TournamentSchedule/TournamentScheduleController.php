<?php

namespace App\Http\Controllers\TournamentSchedule;

use App\Http\Controllers\Tournament\TournamentService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TournamentScheduleController extends Controller
{

    private $tournamentScheduleService;

    // private $tournamentService;
    public function __construct(TournamentScheduleService $tournamentScheduleService, TournamentService $tournamentService)
    {
        $this->tournamentScheduleService = $tournamentScheduleService;
        // $this->tournamentService = $tournamentService;
    }

    public function getRounds($tournament_id)
    {
        return $this->tournamentScheduleService->getRounds($tournament_id);
    }

    public function storeRounds(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'total_groups' => 'required',
            'group_winners' => 'required',
            'third_position' => 'required',
            'league_format' => 'required',
            'group_settings' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        return $this->tournamentScheduleService->storeRounds($request->all());
    }

    public function resetRounds(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tournaments',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $this->tournamentScheduleService->resetRounds($request->all());

        return response()->json([
            'message' => 'Rounds reset successfully.'
        ], 200);
    }

    public function storeGroups(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tournament_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        return $this->tournamentScheduleService->storeGroups($request->all());
    }

    public function getGroups($tournament_id)
    {
        return $this->tournamentScheduleService->getGroups($tournament_id);
    }

    public function getGroupListWithTeam($tournament_id)
    {
        return $this->tournamentScheduleService->getGroupListWithTeam($tournament_id);
    }

    public function getGroupDetails($tournament_id)
    {
        return $this->tournamentScheduleService->getGroupDetails($tournament_id);
    }


    public function autoGroupCompleteTournamentDraw(Request $request)
    {
        return $this->tournamentScheduleService->autoGroupCompleteTournamentDraw($request->all());
    }

    public function makeTournamentDraw(Request $request)
    {
        return $this->tournamentScheduleService->makeTournamentDraw($request->all());
    }

    public function tournamentTeamList($tournament_id, Request $request)
    {
        return $this->tournamentScheduleService->tournamentTeamList($tournament_id, $request->all());
    }

    public function getGlobalTeamList($tournament_id, Request $request)
    {
        return $this->tournamentScheduleService->getGlobalTeamList($tournament_id, $request->all());
    }

    public function tournamentAvailableTeamList($tournament_id, Request $request)
    {
        return $this->tournamentScheduleService->tournamentAvailableTeamList($tournament_id, $request->all());
    }

    public function sendRequestToTeam(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'team_id' => 'required|exists:teams,id',
            'tournament_id' => 'required|exists:tournaments,id',
        ], [
            'team_id.required' => "Team is required.",
            'team_id.exists' => "Team doesn`t exist.",
            'tournament_id.required' => "Tournament is required.",
            'tournament_id.exists' => "Tournament doesn`t exist.",
        ]);


        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        return $this->tournamentScheduleService->sendRequestToTeam($request->all());
    }


    public function sendRequestToTournament(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'team_id' => 'required|exists:teams,id',
            'tournament_id' => 'required|exists:tournaments,id',
        ], [
            'team_id.required' => "Team is required.",
            'team_id.exists' => "Team doesn`t exist.",
            'tournament_id.required' => "Tournament is required.",
            'tournament_id.exists' => "Tournament doesn`t exist.",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        return $this->tournamentScheduleService->sendRequestToTournament($request->all());
    }

    public function acceptOrCancelTeamRequest(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'team_id' => 'required',
            'tournament_id' => 'required',
            'status' => 'required|string|max:15',
        ], [
            'id.required' => "Tournament Team id is required!",
            'tournament_id.required' => "Tournament  id is required!.",
            'team_id.required' => "Team id is required!.",
            'status.required' => "Status is required!.",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        return $this->tournamentScheduleService->acceptOrCancelTeamRequest($request->all());
    }

    public function addGroupsTeamsAndInfo(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'league_group_name' => 'required',
            'tournament_id' => 'required',
            'teams' => 'nullable',
        ], [
            'id.required' => "Tournament Team id is required!",
            'league_group_name.required' => "Group Name  is required!.",
            'tournament_id.required' => "Tournament   id is required!.",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        return $this->tournamentScheduleService->addGroupsTeamsAndInfo($request->all());
    }


}
