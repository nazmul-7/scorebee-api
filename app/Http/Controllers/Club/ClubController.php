<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Auth;
use Illuminate\Validation\Rule;

class ClubController extends Controller
{
    private $clubService;

    public function __construct(ClubService $clubService)
    {
        $this->clubService = $clubService;
    }

    //  ======================================== Club CRUD Start ===========================================================
    public function getClubById(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'club_owner_id' => 'required|integer|exists:users,id',
        ], [
            'club_owner_id.exists' => "Club doesn't exist.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->clubService->getClubById($request->input('club_owner_id'));
    }
    //  ======================================== Club CRUD end =============================================================

    //  ======================================== Club to Player request Start ==============================================
    public function sentPlayerRequest(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'player_id' => 'required|integer|exists:users,id',
        ], [
            'player_id.exists' => "Player doesn`t exist.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        $isSend = $this->clubService->sentPlayerRequest($request->all());

        if ($isSend) {
            return response()->json([
                'messages' => 'Request sent successfully.'
            ], 200);
        }

        return response()->json([
            'messages' => 'You cannot perform that action.'
        ], 401);
    }

    public function acceptPlayerRequest(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'player_id' => 'required|integer|exists:users,id',
        ], [
            'player_id.exists' => "Player doesn`t exist.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        $isAccepted = $this->clubService->acceptPlayerRequest($request->all());
        if ($isAccepted) {
            return response()->json([
                'message' => 'Request accepted successfully.'
            ], 200);
        }

        return response()->json([
            'message' => 'You cannot perform that action.'
        ], 401);
    }

    public function removePlayerRequest(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'player_id' => 'required|exists:users,id',
            'status' => 'required|string|in:PENDING,ACCEPTED',
        ], [
            'player_id.exists' => "Player doesn`t exist.",
            'status.required' => "Status is required.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        $isCancelled = $this->clubService->removePlayerRequest($request->all());

        if ($isCancelled) {
            if ($request->input('status') == 'PENDING') {
                $message = 'Request cancelled successfully.';
            } else {
                $message = 'Player removed successfully.';
            }

            return response()->json([
                'message' => $message,
            ], 200);
        }

        return response()->json([
            'message' => 'You cannot perform that action.'
        ], 401);
    }

    public function getPlayerRequestsList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:PENDING,ACCEPTED',
        ], [
            'status.required' => "Status is required.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->clubService->getPlayerRequestsList($request->all());
    }

    public function getPlayerRequestsListV2(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:PENDING,ACCEPTED',
            'last_id' => 'nullable|exists:users,id',
            'term' => 'nullable|string'
        ], [
            'status.required' => "Status is required.",
            'last_id.exists' => "Invalid last id.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->clubService->getPlayerRequestsListV2($request->all());
    }

    public function searchPlayers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'term' => 'nullable|string|max:191',
            'last_id' => 'nullable|exists:users,id',
        ], [
            'term.required' => "Search query is required.",
            'last_id.exists' => "Invalid last id.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->clubService->searchPlayers($request->all());
    }
    //  ======================================== Club to Player request End ================================================

    //  ====================================== Club matches list start =====================================================
    public function getClubMatchesListByFilter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'club_owner_id' => 'required|exists:users,id',
        ], [
            'club_owner_id.required' => "Club is required.",
            'club_owner_id.exists' => "Club doesn't exist.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->clubService->getClubMatchesListByFilter($request->all());
    }
    //  ====================================== Club matches list end =======================================================

    //  ====================================== Club matches list by filter start ===========================================
    public function getClubMembersListByFilter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'club_owner_id' => 'required|exists:users,id',
            'team_id' => 'nullable|exists:teams,id'
        ], [
            'club_owner_id.required' => "Club is required.",
            'club_owner_id.exists' => "Club doesn't exist.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->clubService->getClubMembersListByFilter($request->all());
    }
    //  ====================================== Club matches list by filter end==============================================

    //  ====================================== Club stats list by filter start =============================================
    public function getClubStatsListByFilter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'club_owner_id' => 'required|exists:users,id',
        ], [
            'club_owner_id.required' => "Club is required.",
            'club_owner_id.exists' => "Club doesn't exist.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->clubService->getClubStatsListByFilter($request->all());
    }
    //  ====================================== Club stats list by filter end================================================

    //  ====================================== Club leaderboard by filter start ============================================
    public function getClubBattingLeaderboardByFilter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'club_owner_id' => 'required|exists:users,id',
        ], [
            'club_owner_id.required' => "Club is required.",
            'club_owner_id.exists' => "Club doesn't exist.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->clubService->getClubBattingLeaderboardByFilter($request->all());
    }

    public function getClubBowlingLeaderboardByFilter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'club_owner_id' => 'required|exists:users,id',
        ], [
            'club_owner_id.required' => "Club is required.",
            'club_owner_id.exists' => "Club doesn't exist.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->clubService->getClubBowlingLeaderboardByFilter($request->all());
    }

    public function getClubFieldingLeaderboardByFilter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'club_owner_id' => 'required|exists:users,id',
        ], [
            'club_owner_id.required' => "Club is required.",
            'club_owner_id.exists' => "Club doesn't exist.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->clubService->getClubFieldingLeaderboardByFilter($request->all());
    }

    public function getClubFilterAttributes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'club_owner_id' => 'required|exists:users,id',
            'team_id' => 'nullable|exists:teams,id',
        ], [
            'club_owner_id.exists' => "Club doesn't exists.",
            'team_id.exists' => "Team doesn't exists.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->clubService->getClubFilterAttributes($request->all());
    }
    //  ====================================== Club leaderboard by filter end ==============================================


    //  ======================================== Club to Club Challenge request Start ==============================================
    public function getClubChallengeRequests()
    {
        return $this->clubService->getClubChallengeRequests();
    }

    public function sentClubChallengeRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'opponent_id' => ['required', 'integer', Rule::exists('users', 'id')->where(function ($q) {
                return $q->where('registration_type', 'CLUB_OWNER');
            })],
        ], [
            'opponent_id.required' => "Opponent is required.",
            'opponent_id.integer' => "Opponent is invalid.",
            'opponent_id.exists' => "Opponent doesn't exist.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        $res = $this->clubService->sentClubChallengeRequest($request->all());

        if ($res == 'SEND') {
            return response()->json([
                'message' => 'Challenge Request sent successfully.'
            ], 200);
        } else if ($res == 'PENDING') {
            return response()->json([
                'message' => 'You already have a pending challenge with this club.'
            ], 401);
        } else if ($res == 'UNFINISHED') {
            return response()->json([
                'message' => 'You already have a unfinished challenge with this club.'
            ], 401);
        }

        return response()->json([
            'message' => "You can't perform that action."
        ], 401);
    }

    public function acceptClubChallengeRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'challenge_request_id' => ['required', 'integer'],
        ], [
            'challenge_request_id.required' => "Challenge request is required.",
            'challenge_request_id.integer' => "Challenge request is invalid."
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        $res = $this->clubService->acceptClubChallengeRequest($request->all());

        if ($res == 'ACCEPTED') {
            return response()->json([
                'messages' => 'Challenge Request accepted successfully.'
            ], 200);
        }

        return response()->json([
            'messages' => "You can't perform that action."
        ], 401);
    }


    public function cancelClubChallengeRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'challenge_request_id' => ['required', 'integer'],
            'status' => ['required', 'in:ACCEPTED,PENDING']
        ], [
            'challenge_request_id.required' => "Challenge request is required.",
            'challenge_request_id.integer' => "Challenge request is invalid."
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        $res = $this->clubService->cancelClubChallengeRequest($request->all());

        if ($res == 'CANCELLED') {
            return response()->json([
                'messages' => 'Challenge Request cancelled successfully.'
            ], 200);
        } else if ($res == 'REMOVED') {
            return response()->json([
                'messages' => 'Challenge removed successfully.'
            ], 200);
        }

        return response()->json([
            'messages' => "You can't perform that action."
        ], 401);
    }

    public function getTeamsListByClub(Request $request){
        $validator = Validator::make($request->all(), [
            'club_owner_id' => 'required|integer',
            'last_id' => 'nullable|exists:teams,id',
        ], [
            'club_owner_id.required' => "Club is required.",
            'last_id.exists' => "Invalid last id.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->clubService->getTeamsListByClub($request->all());
    }

    public function myTeams(Request $request){
        $validator = Validator::make($request->all(), [
            'status' => 'required|string',
            'last_id' => 'nullable|exists:teams,id',
        ], [
            'status.required' => "Params is required.",
            'last_id.exists' => "Invalid last id.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->clubService->myTeams($request->all());
    }
}
