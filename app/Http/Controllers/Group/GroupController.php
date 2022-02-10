<?php

namespace App\Http\Controllers\Group;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class GroupController extends Controller
{

    private $groupService;
    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }
    
    //Group-start 
    
    public function getGroupsByTournament($id){
        return $this->groupService->getGroupsByTournament($id);
    }
    //Group-end 



    //Tournaments-start 
        
    public function getTourTeamList($id){
        return $this->groupService->getTourTeamList($id);
    }
        
    public function updateTournaments(Request $request){
        $validator = Validator::make($request->all(),[
            'tournament_id' => 'required|exists:tournaments,id',
            'name' => 'required|string|max:191',
            'city' => 'required|string|max:191',
            'ground' => 'required|string|max:500',
            'organiser_name' => 'required|string|max:191',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'category' => 'required|string|max:50',
            'ball_type' => 'required|string|max:50',
            'match_type' => 'required|string|max:50',
            'tags' => 'nullable|string|max:500',
            'details' => 'nullable|string|max:500',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 422);
        }
        return $this->groupService->updateTournaments($request->all());
    }
        
    public function deleteTournament(Request $request){
        $validator = Validator::make($request->all(),[
            'tournament_id' => 'required|exists:tournaments,id',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 401);
        }
        return $this->groupService->deleteTournament($request->all());
    }

    //Touranments - end
    
       
}
