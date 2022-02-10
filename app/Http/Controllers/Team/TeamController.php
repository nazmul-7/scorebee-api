<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TeamController extends Controller
{

    private $teamService;

    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }

//  ============================================== Team CRUD Start ================================================
    public function getTeamById(Request $request){
        $validator = Validator::make($request->all(), [
            'team_id' => 'nullable|exists:teams,id',
        ], [
            'team_id.exists' => "Team doesn't exist.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->teamService->getTeamById($request->input('team_id'));
    }

    public function getOwnerTeamsList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'last_id' => 'nullable|exists:teams,id',
        ], [
            'last_id.exists' => "Invalid last id.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->teamService->getOwnerTeamsList($request->all());
    }

    public function createTeam(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'team_name' => 'required|string|max:191',
            'team_unique_name' => 'required|string|unique:teams|max:191',
            'team_short_name' => 'nullable|string|max:4',
            'city' => 'required|string|max:191',
            'team_banner' => 'nullable|image',
            'team_logo' => 'nullable|image',
        ],
            [
                'team_name.required' => 'Team name is required.',
                'team_unique_name.required' => 'Team unique name is required.',
                'team_unique_name.unique' => 'Team unique name must be unique.',
                'city.required' => 'City is required.',
                'team_banner.image' => 'Team banner must be an image.',
                'team_logo.image' => 'Team banner must be an image.'
            ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        $isCreated = $this->teamService->createTeam($request->all());
        if ($isCreated) {
            return $isCreated;
        }

        return response()->json([
            'messages' => 'You cannot perform that action.'
        ], 402);
    }

    public function updateTeam(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'team_id' => 'required|exists:teams,id',
            'team_name' => 'required|string|max:191',
            'team_unique_name' => 'required|string|max:191|unique:teams,team_unique_name,' . $request->team_id,
            'team_short_name' => 'nullable|string|max:4',
            'city' => 'required|string|max:90',
            'team_banner' => 'nullable|image',
            'team_logo' => 'nullable|image',
        ],
            [
                'team_id.exists' => "Team doesn't exists.",
                'team_name.required' => 'Team name is required.',
                'team_unique_name.required' => 'Team unique name is required.',
                'team_unique_name.unique' => 'Team name must be unique.',
                'city.required' => 'City is required.',
                'team_banner.image' => 'Team banner must be an image.',
                'team_logo.image' => 'Team banner must be an image.'
            ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->teamService->updateTeam($request->all());
    }

    public function deleteTeam(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'team_id' => 'required|exists:teams,id',
        ],
            [
                'team_id.exists' => "Team doesn't exists.",
            ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        $isDeleted = $this->teamService->deleteTeam($request->all());;

        if ($isDeleted == 'DELETED') {
            return response()->json([
                'messages' => 'Team deleted successfully.'
            ], 200);
        } else if ($isDeleted == 'CANT_DELETE') {
            return response()->json([
                'messages' => "Team can't be delete because it some matches record."
            ], 200);
        }

        return response()->json([
            'messages' => 'You cannot perform that action.'
        ], 402);
    }

//  ============================================== Team CRUD End ================================================

//  ======================================== Team Players CRUD Start ================================================
    public function searchClubPlayers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'team_id' => 'required|exists:teams,id',
            'term' => 'nullable|string|max:191',
            'last_id' => 'nullable|exists:users,id',
        ], [
            'team_id.required' => "Team is required.",
            'team_id.exists' => "Team doesn't exist.",
            'last_id.exists' => "Invalid last id.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->teamService->searchClubPlayers($request->all());
    }

    public function getTeamPlayersList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'team_id' => 'required|exists:teams,id',
            'squad_type' => 'nullable|in:MAIN,EXTRA,BENCH',
            'last_id' => 'nullable|exists:users,id',
        ], [
            'team_id.exists' => "Team doesn`t exist.",
            'last_id.exists' => "Invalid last id.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->teamService->getTeamPlayersList($request->all());
    }

    public function addTeamPlayer(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'team_id' => 'required|integer|exists:teams,id',
            'player_id' => 'required|integer|exists:users,id',
        ], [
            'team_id.exists' => "Team doesn't exist.",
            'player_id.exists' => "Player doesn't exist.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        $isAdded = $this->teamService->addTeamPlayer($request->all());
        if ($isAdded) {
            return response()->json([
                'message' => 'Player added successfully.'
            ], 200);
        }

        return response()->json([
            'messages' => 'You cannot perform that action.'
        ], 402);
    }

    public function updateTeamPlayer(Request $request): JsonResponse{
        $validator = Validator::make($request->all(), [
            'team_id' => 'required|integer|exists:teams,id',
            'player_id' => 'required|integer|exists:users,id',
            'player_role' => 'required|in:CAPTAIN,WICKET_KEEPER'
        ], [
            'team_id.exists' => "Team doesn't exist.",
            'player_id.exists' => "Player doesn't exist.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        $message = 'You cannot perform that action.';
        $isSet = $this->teamService->updateTeamPlayer($request->all());

        if ($isSet) {
            if ($request->input('player_role') == 'CAPTAIN') {
                $message = 'Captain updated successfully.';
            }else if($request->input('player_role') == 'WICKET_KEEPER'){
                $message = 'Wicket Keeper updated successfully.';
            }

            return response()->json([
                'messages' => $message
            ], 200);
        }

        return response()->json([
            'messages' => $message
        ], 402);
    }

    public function removeTeamPlayer(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'team_id' => 'required|exists:teams,id',
            'player_id' => 'required|exists:users,id',
        ], [
            'team_id.exists' => "Team doesn't exist.",
            'player_id.exists' => "Player doesn't exist.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        $isRemoved = $this->teamService->removeTeamPlayer($request->all());

        if ($isRemoved) {
            if ($request->input('status') == 'PENDING'){
                $message = 'Request cancelled successfully.';
            }else {
                $message = 'Player removed successfully.';
            }

            return response()->json([
                'message' => $message,
            ], 200);
        }

        return response()->json([
            'message' => 'You cannot perform that action.'
        ], 402);
    }
//  ======================================== Team Players CRUD End ================================================

//  ======================================== Team Squads start ==================================================
    public function getTeamSquadList(Request $request){
        $validator = Validator::make($request->all(), [
            'team_id' => 'required|exists:teams,id',
        ], [
            'team_id.exists' => "Team doesn`t exist.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->teamService->getTeamSquadList($request->all());
    }

    public function updateTeamSquad(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'team_id' => 'required|integer|exists:teams,id',
            'player_id' => 'required|integer|exists:users,id',
            'squad_type' => 'required|in:MAIN,EXTRA,BENCH'
        ], [
            'team_id.exists' => "Team doesn't exist.",
            'player_id.exists' => "Player doesn't exist.",
            'squad_type.required' => 'Type is required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        $res = $this->teamService->updateTeamSquad($request->all());

        return response()->json([
            'messages' => $res['message']
        ], $res['status_code']);
    }
//  ======================================== Team Squads end ====================================================
    public function getTeamCurrentFormInsights(Request $request, $teamId)
    {
//        return $this->teamService->getTeamMatchesList($teamId, $request->all(), $matchStatus = 'FINISHED');
    }

    public function getTeamTossInsights(Request $request, $teamId)
    {
        return $this->teamService->getTeamTossInsights($teamId, $request->all());
    }

    public function getTeamOverallInsights(Request $request, $teamId)
    {
        return $this->teamService->getTeamOverallInsights($teamId, $request->all());
    }

}
