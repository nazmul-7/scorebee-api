<?php

namespace App\Http\Controllers\TeamPlayer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeamPlayerController extends Controller
{

    private $teamPlayerService;

    public function __construct(TeamPlayerService $teamPlayerService)
    {
        $this->teamPlayerService = $teamPlayerService;
    }

    //    Player to team or team to player request start
    public function sendPlayerRequest(Request $request){

        $validator = Validator::make($request->all(),[
            'team_id' => 'required|exists:teams,id',
            'player_id' => 'required|exists:users,id',
            'requested_by' => 'required|in:PLAYER,TEAM'
        ],[
            'team_id.required' => "Team is required.",
            'team_id.exists' => "Team doesn`t exist.",
            'player_id.required' => "Player is required.",
            'player_id.exists' => "Player doesn`t exist.",
            'requested_by.in' => "Request is invalid.",
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 422);
        }

        return $this->teamPlayerService->sendPlayerRequest($request->all());
    }

//    public function acceptPlayerRequest(Request $request){
//
//        $validator = Validator::make($request->all(),[
//            'id' => 'required|exists:tournament_teams,id',
//        ],[
//            'id.required' => "Invalid Info.",
//            'id.exists' => "Invalid Info.",
//        ]);
//
//        if($validator->fails()){
//            return response()->json($validator->errors(), 422);
//        }
//        return $this->teamPlayerService->acceptPlayerRequest($request->all());
//    }
//
//    public function cancelPlayerRequest(Request $request){
//
//        $validator = Validator::make($request->all(),[
//            'id' => 'required|exists:tournament_teams,id',
//        ],[
//            'id.required' => "Invalid Info.",
//            'id.exists' => "Invalid Info.",
//        ]);
//
//        if($validator->fails()){
//            return response()->json($validator->errors(), 422);
//        }
//        return $this->teamPlayerService->cancelPlayerRequest($request->all());
//    }

//    Player to team request or team to player request end

}
