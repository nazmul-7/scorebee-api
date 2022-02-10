<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PlayerController extends Controller
{

    private $playerService;

    public function __construct(PlayerService $playerService)
    {
        $this->playerService = $playerService;
    }

    public function forgotPassword(Request $request){

        $validator = Validator::make($request->all(), [
            'email' => 'required|string|exists:users,email',
        ], [
            'email.exists' => "Invalid Creadentials",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->playerService->forgotPassword($request->all());
    }

    public function verifyEmail(Request $request){

        $validator = Validator::make($request->all(), [
            'email' => 'required|string|max:191|exists:users,email',
            'verify_code' => 'required|max:30|min:2|string',
        ], [
            'verify_code.required' => "Verification code is required.",
            'email.exists' => "Invalid Creadentials",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->playerService->verifyEmail($request->all());
    }

    public function resetPassword(Request $request){

        $validator = Validator::make($request->all(), [
            'email' => 'required|string|max:191|exists:users,email',
            'password' => 'required|max:191|min:6|string',
            'verify_code' => 'required|string|exists:users,forgot_code',
        ], [
            'verify_code.required' => "Verification code is required.",
            'verify_code.exists' => "Invalid Creadentials.",
            'password.required' => "Password is required.",
            'email.exists' => "Invalid Creadentials.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->playerService->resetPassword($request->all());
    }

    public function updateUserInfo(Request $request)
    {
        $data = $request->all();
        // $data['social_accounts'] = json_decode($data['social_accounts'], true);
        // return $data;
        if (isset($data['email']) || isset($data['password']) || isset($data['phone'])) {
            return response()->json(['msg' => 'Unauthorised.'], 401);
        }
        if (isset($data['date_of_birth']) && $data['date_of_birth']) {
            $date = str_replace('/', '-', $data['date_of_birth']);
            $data['date_of_birth'] = date("Y-m-d", strtotime($date));
        }


        $validator = Validator::make($data, [
            'username' => 'nullable|string|max:191|unique:users,username,' . Auth::id(),
            'country' => 'nullable|string|max:191',
            'state' => 'nullable|string|max:191',
            'city' => 'nullable|string|max:191',
            'date_of_birth' => 'nullable|date',
            'birth_place' => 'nullable|string|max:191',
            'playing_role' => 'nullable|string|max:20',
            'batting_style' => 'nullable|string|max:20',
            'bowling_style' => 'nullable|string|max:20',
            'profile_pic' => 'nullable|image',
            'cover' => 'nullable|image',
            'nid_pic' => 'nullable|image',
            'about' => 'nullable|string|max:1000',
            'hire_info' => 'nullable|string|max:500',
            'social_accounts' => 'nullable|max:1000',
            'bio' => 'nullable|string|max:1000',
            'registration_type' => 'nullable|string|max:15',
        ], [
            "date_of_birth.date" => "The date format is invalid.",
            "birth_place.required" => "The birth place must not be greater than 191 characters.",
            "playing_role.required" => "The Role must not be greater than 191 characters.",
            "batting_style.required" => "The Batting style must not be greater than 191 characters.",
            "bowling_style.required" => "The bowling style must not be greater than 191 characters.",
            "profile_pic.required" => "The Profile Picture must not be greater than 191 characters.",
            "social_accounts.required" => "The Social link must not be greater than 191 characters.",
        ]);

        // if (isset($data['social_accounts']) && $data['social_accounts'] != '') {
        //     $data['social_accounts'] = json_decode($data['social_accounts']);
        // }

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        return $this->playerService->updateUserInfo($data);
    }

    public function playerBattingStats(Request $request, $id)
    {
        $data = $request->all();
        $data['player_id'] = isset($id) ? $id : 0;
        $validator = Validator::make($data, [
            'player_id' => 'required|exists:users,id'
        ], [
            'player_id.exists' => "Player doesn't exist.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->playerService->playerBattingStats($data);
    }

    public function playerBowlingStats(Request $request, $id)
    {
        $data = $request->all();
        $data['player_id'] = isset($id) ? $id : 0;
        $validator = Validator::make($data, [
            'player_id' => 'required|exists:users,id'
        ], [
            'player_id.exists' => "Player doesn't exist.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->playerService->playerBowlingStats($data);
    }

    public function playerFieldingStats(Request $request, $id)
    {
        $data = $request->all();
        $data['player_id'] = isset($id) ? $id : 0;
        $validator = Validator::make($data, [
            'player_id' => 'required|exists:users,id'
        ], [
            'player_id.exists' => "Player doesn't exist.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->playerService->playerFieldingStats($data);
    }

    public function playerCaptainStats(Request $request, $id)
    {
        $data = $request->all();
        $data['player_id'] = isset($id) ? $id : 0;
        $validator = Validator::make($data, [
            'player_id' => 'required|exists:users,id'
        ], [
            'player_id.exists' => "Player doesn't exist.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->playerService->playerCaptainStats($data);
    }

    public function singlePlayerDetails($id)
    {
        return $this->playerService->singlePlayerDetails($id);
    }

    //    Club to player request start
    public function getClubRequestsList(Request $request)
    {
        return $this->playerService->getClubRequestsList($request->all());
    }

    public function sentClubRequest(Request $request): JsonResponse
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

        $isSend = $this->playerService->sentClubRequest($request->all());

        if ($isSend) {
            return response()->json([
                'messages' => 'Request sent successfully.'
            ], 200);
        }

        return response()->json([
            'messages' => 'You cannot perform that action.'
        ], 402);
    }

    public function acceptClubRequest(Request $request): JsonResponse
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

        $isAccepted = $this->playerService->acceptClubRequest($request->all());
        if ($isAccepted) {
            return response()->json([
                'message' => 'Request accepted successfully.'
            ], 200);
        }

        return response()->json([
            'message' => 'You cannot perform that action.'
        ], 402);
    }

    public function removeClubRequest(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'club_owner_id' => 'required|integer|exists:users,id',
            'status' => 'required|string|in:PENDING,ACCEPTED',
        ], [
            'club_owner_id.exists' => "Club doesn't exist.",
            'status.required' => "Status is required.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        $isCancelled = $this->playerService->removeClubRequest($request->all());

        if ($isCancelled) {
            if ($request->input('status') == 'PENDING') {
                $message = 'Request cancelled successfully.';
            } else {
                $message = 'Club Leave successfully.';
            }

            return response()->json([
                'message' => $message,
            ], 200);
        }

        return response()->json([
            'message' => 'You cannot perform that action.'
        ], 402);
    }
    //    Club to player request end

    //Players-leaderboard-start
    public function highestBattingByRun(Request $request)
    {
        return $this->playerService->highestBattingByRun($request->all());
    }

    public function highestBowlingByRun(Request $request)
    {
        return $this->playerService->highestBowlingByRun($request->all());
    }

    public function highestFieldingByDismissal(Request $request)
    {
        return $this->playerService->highestFieldingByDismissal($request->all());
    }
    //Players-leaderboard-end


    public function awardInMatches(Request $request, $id)
    {
        $data = $request->all();
        $data['player_id'] = isset($id) ? $id : 0;
        $validator = Validator::make($data, [
            'player_id' => 'required|exists:users,id',
        ], [
            'player_id.required' => "Player is required.",
            'player_id.exists' => "Player doesn't exist.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->playerService->awardInMatches($data);
    }

    public function awardInTournaments(Request $request, $id)
    {
        $data = $request->all();
        $data['player_id'] = isset($id) ? $id : 0;
        $validator = Validator::make($data, [
            'player_id' => 'required|exists:users,id',
        ], [
            'player_id.required' => "Player is required.",
            'player_id.exists' => "Player doesn't exist.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->playerService->awardInTournaments($data);
    }

    public function awardsLike(Request $request)
    {
        $data = $request->all();
        if(isset($data['tournament_id']) && isset($data['fixture_id'])){
            return response()->json(["message" => "Invalid information!"], 401);
        }

        $validator = Validator::make($data, [
            'player_id' => 'required|exists:users,id|numeric',
            'type' => 'required|in:LIKE,SHARE|string',
            'fixture_id' => 'required_without:tournament_id|exists:fixtures,id|numeric',
            'tournament_id' => 'required_without:fixture_id|exists:tournaments,id|numeric'
        ],[
            'player_id.required' => "Player is required.",
            'player_id.exists' => "Player doesn't exist.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->playerService->awardsLike($data);
    }

    //Player Batting insights-start
    public function playerCurrentFormAndInnings(Request $request, $id)
    {
        $data = $request->all();
        $data['player_id'] = isset($id) ? $id : 0;
        $validator = Validator::make($data, [
            'player_id' => 'required|exists:users,id',
        ], [
            'player_id.required' => "Player is required.",
            'player_id.exists' => "Player doesn't exist.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->playerService->playerCurrentFormAndInnings($data);
    }

    public function battingWagon(Request $request, $id)
    {
        $data = $request->all();
        $data['player_id'] = isset($id) ? $id : 0;
        $validator = Validator::make($data, [
            'player_id' => 'required|exists:users,id',
        ], [
            'player_id.required' => "Player is required.",
            'player_id.exists' => "Player doesn't exist.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->playerService->battingWagon($data);
    }

    public function testingWagon(Request $request, $id)
    {
        $data = $request->all();
        $data['player_id'] = isset($id) ? $id : 0;
        $validator = Validator::make($data, [
            'player_id' => 'required|exists:users,id',
        ], [
            'player_id.required' => "Player is required.",
            'player_id.exists' => "Player doesn't exist.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->playerService->testingWagon($data);
    }

    public function bowlingWagon(Request $request, $id)
    {
        $data = $request->all();
        $data['player_id'] = isset($id) ? $id : 0;
        $validator = Validator::make($data, [
            'player_id' => 'required|exists:users,id',
        ], [
            'player_id.required' => "Player is required.",
            'player_id.exists' => "Player doesn't exist.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->playerService->bowlingWagon($data);
    }

    public function playerCurrentBowlingFormAndInnings(Request $request, $id)
    {
        $data = $request->all();
        $data['player_id'] = isset($id) ? $id : 0;
        $validator = Validator::make($data, [
            'player_id' => 'required|exists:users,id',
        ], [
            'player_id.required' => "Player is required.",
            'player_id.exists' => "Player doesn't exist.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        $data['limit'] = 5;
        return $this->playerService->playerCurrentBowlingFormAndInnings($data);
    }

    public function playerBowlingOverallStats(Request $request, $id)
    {

        $data = $request->all();
        $data['player_id'] = isset($id) ? $id : 0;
        $validator = Validator::make($data, [
            'player_id' => 'required|exists:users,id',
        ], [
            'player_id.required' => "Player is required.",
            'player_id.exists' => "Player doesn't exist.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->playerService->playerBowlingOverallStats($data);
    }

    public function playerBattingComparison(Request $request, $id)
    {
        $data = $request->all();
        $data['player_id'] = isset($id) ? $id : 0;
        $validator = Validator::make($data, [
            'player_id' => 'required|exists:users,id',
            'compare_id' => 'nullable|different:player_id|exists:users,id',
        ], [
            'player_id.required' => "Player is required.",
            'player_id.exists' => "Player doesn't exist.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all()),
            ], 422);
        }
        return $this->playerService->playerBattingComparison($data);
    }

    public function compareBattingWagon(Request $request, $id)
    {
        $data = $request->all();
        $data['player_id'] = isset($id) ? $id : 0;
        $validator = Validator::make($data, [
            'player_id' => 'required|exists:users,id',
            'compare_id' => 'nullable',
        ], [
            'player_id.required' => "Player is required.",
            'player_id.exists' => "Player doesn't exist.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all()),
            ], 422);
        }
        return $this->playerService->compareBattingWagon($data);
    }

    public function compareBowlingWagon(Request $request, $id)
    {
        $data = $request->all();
        $data['player_id'] = isset($id) ? $id : 0;
        $validator = Validator::make($data, [
            'player_id' => 'required|exists:users,id',
            'compare_id' => 'nullable|different:player_id|exists:users,id',
        ], [
            'player_id.required' => "Player is required.",
            'player_id.exists' => "Player doesn't exist.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all()),
            ], 422);
        }
        return $this->playerService->compareBowlingWagon($data);
    }

    public function playerBowlingComparison(Request $request, $id)
    {
        $data = $request->all();
        $data['player_id'] = isset($id) ? $id : 0;
        $validator = Validator::make($data, [
            'player_id' => 'required|exists:users,id',
            'compare_id' => 'nullable|different:player_id|exists:users,id',
        ], [
            'player_id.required' => "Player is required.",
            'player_id.exists' => "Player doesn't exist.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all()),
            ], 422);
        }

        return $this->playerService->playerBowlingComparison($data);
    }

    public function fieldingCompare(Request $request, $id)
    {

        $data = $request->all();
        $data['player_id'] = isset($id) ? $id : 0;
        $validator = Validator::make($data, [
            'player_id' => 'required|exists:users,id',
            'compare_id' => 'nullable|different:player_id|exists:users,id',
        ], [
            'player_id.required' => "Player is required.",
            'player_id.exists' => "Player doesn't exist.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all()),
            ], 422);
        }

        return $this->playerService->fieldingCompare($data);
    }


    public function playerOutTypeComparison(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'player_id' => 'required|exists:users,id',
            'compare_id' => 'required|different:player_id|exists:users,id',
        ], [
            'player_id.required' => "Player is required.",
            'player_id.exists' => "Player doesn't exist.",
            'compare_id.required' => "Compare player is required.",
            'compare_id.exists' => "Compare player doesn't exist.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all()),
            ], 422);
        }

        $firstPlayer = $this->playerService->playerOutTypeComparison($request->input('player_id'));
        $secondPlayer = $this->playerService->playerOutTypeComparison($request->input('compare_id'));
        return [
            'first_player' => $firstPlayer,
            'second_player' => $secondPlayer
        ];
    }

    public function getPlayerList($id, Request $request)
    {
        $data = $request->all();
        $data['player_id'] = isset($id) ? $id : 0;
        $validator = Validator::make($data, [
            'player_id' => 'required',
        ], [
            'player_id.required' => "Player is required.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->playerService->getPlayerList($data);
    }

    public function battingsAvgByPosition($id, Request $request)
    {
        $data = $request->all();
        $data['player_id'] = isset($id) ? $id : 0;
        $validator = Validator::make($data, [
            'player_id' => 'required',
        ], [
            'player_id.required' => "Player is required.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->playerService->battingsAvgByPosition($data);
    }

    public function battingFaceOff($id, Request $request)
    {
        $data = $request->all();
        $data['player_id'] = isset($id) ? $id : 0;
        $validator = Validator::make($data, [
            'player_id' => 'required|exists:users,id',
            'face_off_player_id' => 'required|different:player_id',
        ], [
            'player_id.required' => "Player is required.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->playerService->battingFaceOff($data);
    }

    public function faceOffWagon($id, Request $request)
    {
        $data = $request->all();
        $data['player_id'] = isset($id) ? $id : 0;
        $validator = Validator::make($data, [
            'player_id' => 'required|exists:users,id',
            'face_off_player_id' => 'required|different:player_id',
            'type' => 'required',
        ], [
            'player_id.required' => "Player is required.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->playerService->faceOffWagon($data);
    }

    public function bowlingFaceOff($id, Request $request)
    {
        $data = $request->all();
        $data['player_id'] = isset($id) ? $id : 0;
        $validator = Validator::make($data, [
            'player_id' => 'required|exists:users,id',
            'face_off_player_id' => 'required|different:player_id',
        ], [
            'player_id.required' => "Player is required.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->playerService->bowlingFaceOff($data);
    }

    public function bowlingPostion($id, Request $request)
    {
        $data = $request->all();
        $data['player_id'] = isset($id) ? $id : 0;
        $validator = Validator::make($data, [
            'player_id' => 'required|exists:users,id',
            'compare_id' => 'required|different:player_id',
        ], [
            'player_id.required' => "Player is required.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->playerService->bowlingPostion($data);
    }

    public function battingPosition($id, Request $request)
    {
        $data = $request->all();
        $data['player_id'] = isset($id) ? $id : 0;
        $validator = Validator::make($data, [
            'player_id' => 'required|exists:users,id',
            'compare_id' => 'required|different:player_id',
        ], [
            'player_id.required' => "Player is required.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->playerService->battingPosition($data);
    }

    public function outBetweenRuns($id, Request $request)
    {
        $data = $request->all();
        $data['player_id'] = isset($id) ? $id : 0;
        $validator = Validator::make($data, [
            'player_id' => 'required|exists:users,id',
            'compare_id' => 'required|different:player_id',
        ], [
            'player_id.required' => "Player is required.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->playerService->outBetweenRuns($data);
    }


    public function battingAgainstDifferentBowlers($id, Request $request)
    {
        $data = $request->all();
        $data['player_id'] = isset($id) ? $id : 0;
        $validator = Validator::make($data, [
            'player_id' => 'required|exists:users,id',
            'compare_id' => 'required|different:player_id',
        ], [
            'player_id.required' => "Player is required.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->playerService->battingAgainstDifferentBowlers($data);
    }

    public function bowlerStatesByYear($id)
    {
        return $this->playerService->bowlerStatesByYear($id);
    }

    //Player Batting insights-end

    //Face off comparison insights start
    public function getPlayerFaceOffOutsComparison(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'player_id' => 'required|integer|exists:users,id',
            'comparer_id' => 'required|integer|different:player_id|exists:users,id',
            'stats_type' => 'required|string|in:BOWLING,BATTING'
        ], [
            'player_id.required' => "Player is required.",
            'player_id.exists' => "Player doesn't exist.",
            'comparer_id.required' => "Comparer player is required.",
            'comparer_id.exists' => "Comparer player doesn't exist.",
            'stats_type.required' => 'Stats type is required.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all()),
            ], 422);
        }

        return $this->playerService->getPlayerFaceOffOutsComparison($request->all());
    }
    //Face off comparison insights end



}
