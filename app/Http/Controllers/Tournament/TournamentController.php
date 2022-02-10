<?php

namespace App\Http\Controllers\Tournament;

use App\Http\Controllers\Controller;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Log;

class TournamentController extends Controller
{

    private $tournamentService;
    public function __construct(TournamentService $tournamentService)
    {
        $this->tournamentService = $tournamentService;
    }




        //Tournaments-start
    public function getTournamentById(Request $request){
        $validator = Validator::make($request->all(), [
            'tournament_id' => 'required|integer|exists:tournaments,id',
        ], [
            'tournament_id.exists' => "Tournament doesn't exist.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->tournamentService->getTournamentById($request->input('tournament_id'));
    }

    public function getTournaments(Request $request){
        return $this->tournamentService->getTournaments($request->all());
    }

    public function getAllTournaments(Request $request){
        return $this->tournamentService->getAllTournaments($request->all());
    }

    public function getAllTournamentsV2(Request $request){
        return $this->tournamentService->getAllTournamentsV2($request->all());
    }


    public function createTournaments(Request $request){
        $data = $request->all();

        $data['start_date'] = isset($data['start_date'])? Carbon::createFromFormat('d/m/Y', $data['start_date'])->format('Y-m-d'):'';
        $data['end_date'] =isset($data['end_date'])? Carbon::createFromFormat('d/m/Y', $data['end_date'])->format('Y-m-d'): '';
        $data['ground_id'] = isset($data['ground_id']) ? json_decode($data['ground_id'], true) :'';
        $data['is_verified_player'] = isset($data['is_verified_player']) ? json_decode($data['is_verified_player'], true) :'';
        $data['is_whatsapp'] = isset($data['is_whatsapp']) ? json_decode($data['is_whatsapp'], true) :'';

        $validator = Validator::make($data,[
            'tournament_name' => 'required|string|max:191',
            'city' => 'required|string|max:191',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'tournament_category' => 'required|string|max:50',
            'ball_type' => 'required|string|max:50',
            'match_type' => 'required|string|max:50',
            'test_match_duration' => 'required_if:match_type,TEST MATCH',
            'test_match_session' => 'required_if:match_type,TEST MATCH',
            'tags' => 'nullable|string|max:500',
            'details' => 'nullable|string|max:500',
            'ground_id' => 'required|array|exists:grounds,id',
            'timezone' => 'nullable|timezone'
        ],[
            'test_match_duration.required_if' => "Day is required.",
            'test_match_session.required_if' => "Session is required.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->tournamentService->createTournaments($data);
    }

    public function updateTournaments(Request $request){
        $data = $request->all();
        // Log::channel('slack')->info('data', ['d' => $data]);


        $data['start_date'] = isset($data['start_date'])? Carbon::createFromFormat('d/m/Y', $data['start_date'])->format('Y-m-d'):'';
        $data['end_date'] =isset($data['end_date'])? Carbon::createFromFormat('d/m/Y', $data['end_date'])->format('Y-m-d'): '';
        $data['ground_id'] = isset($data['ground_id']) ? json_decode($data['ground_id'], true) :'';
        $data['is_verified_player'] = isset($data['is_verified_player']) ? json_decode($data['is_verified_player'], true) :'';
        $data['is_whatsapp'] = isset($data['is_whatsapp']) ? json_decode($data['is_whatsapp'], true) :'';

        // Log::channel('slack')->info('data', ['d' => $data]);
        $validator = Validator::make($data,[
            'id' => 'required|exists:tournaments,id',
            'tournament_name' => 'nullable|string|max:191',
            'city' => 'nullable|string|max:191',
            // 'organizer_name' => 'required|string|max:191',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'tournament_category' => 'nullable|string|max:50',
            'ball_type' => 'nullable|string|max:50',
            'match_type' => 'nullable|string|max:50',
            'test_match_duration' => 'required_if:match_type,TEST MATCH',
            'test_match_session' => 'required_if:match_type,TEST MATCH',
            'tags' => 'nullable|string|max:500',
            'details' => 'nullable|string|max:500',
            'ground_id' => 'nullable|array|exists:grounds,id',
        ],[
            'test_match_duration.required_if' => "Day is required.",
            'test_match_session.required_if' => "Session is required.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        // return $data;
        return $this->tournamentService->updateTournaments($data);
    }

    public function deleteTournament(Request $request){
        $validator = Validator::make($request->all(),[
            'tournament_id' => 'required|exists:tournaments,id',
        ],[
            'tournament_id.required' => 'Tournament is required.',
            'tournament_id.exists' => "Tournament doesn`t exist.",
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 422);
        }
        return $this->tournamentService->deleteTournament($request->all());
    }

    public function tournamentStats($id, Request $request){
        $data = $request->all();
        $data['tournament_id'] = isset($id)?$id:0;

        $validator = Validator::make($data,[
            'tournament_id' => 'required|exists:tournaments,id',
        ],[
            'tournament_id.required' => 'Tournament is required.',
            'tournament_id.exists' => "Tournament doesn`t exist.",
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 422);
        }
        return $this->tournamentService->tournamentStats($data);
    }

         //Touranments - end

    //add-tournament-setting-start

    public function tournamentSettings(Request $request){
        $validator = Validator::make($request->all(),[
            'tournament_id' => 'required|exists:tournaments,id',
            'tournament_type' => 'required|string|max:20',
            'face_off' => 'required_if:tournament_type,IPL SYSTEM',
            'first_round' => 'required_if:tournament_type,SUPER LEAGUE',
            'first_round_face_off' => 'required_if:tournament_type,SUPER LEAGUE',
            'second_round' => 'required_if:tournament_type,SUPER LEAGUE',
            'second_round_face_off' => 'required_if:tournament_type,SUPER LEAGUE',
            'total_groups' => 'required_if:tournament_type,LEAGUE MATCHES',
            'group_winners' => 'required_if:tournament_type,LEAGUE MATCHES',
            'third_position' => 'required_if:tournament_type,LEAGUE MATCHES',
        ],[
            'tournament_id.required' => 'Tournament is required.',
            'tournament_id.exists' => "Tournament doesn`t exist.",
            'face_off.required' => "Face Off is required.",
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 422);
        }

        return $this->tournamentService->tournamentSettings($request->all());
    }

    //add-tournament-setting - end



        //Ground-start

    public function addGroundInTournament(Request $request){
          $data = [ 'data' => $request->all() ];

            $validator = Validator::make($data,[
                'data.*.tour_owner_id' => 'required|exists:users,id',
                'data.*.tournament_id' => 'required|exists:grounds,id',
                'data.*.ground_id' => 'required|exists:grounds,id',
            ]
            ,[
                'data.*.tournament_id.required' => 'Tournament is required.',
                'data.*.tournament_id.exists' => "Tournament doesn`t exist.",
                'data.*.ground_id.required' => 'Ground is required.',
                'data.*.ground_id.exists' => "Ground doesn`t exist.",
                'data.*.tour_owner_id.required' => 'Invalid User.',
                'data.*.tour_owner_id.exists' => "Invalid User.",
            ]
        );

            if($validator->fails()){
                return response()->json($validator->errors(), 422);
            }



        return $this->tournamentService->addGroundInTournament($request->all());
    }

    public function tournamentGroundLists(Request $request){
        $validator = Validator::make($request->all(), [
            'tournament_id' => 'required|exists:tournaments,id',
        ], [
            'tournament_id.required' => "Tournament is required.",
            'tournament_id.exists' => "Tournament doesn't exists.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->tournamentService->tournamentGroundLists($request->all());
    }


         //Ground - end

         //Tournament-fixture

        public function tournamentFixture($id, Request $request){
            return $this->tournamentService->tournamentFixture($id, $request->all());
        }
        //Tournament-fixture

         //Tournament-team-start

        public function tournamentTeamList($id, Request $request){
            return $this->tournamentService->tournamentTeamList($id, $request->all());
        }
        public function tournamentPointsTable($id, Request $request){
            $data =$request->all();
            $data['tournament_id'] = isset($id) ? $id : 0;
            $validator = Validator::make($data, [
                'tournament_id' => 'required|exists:tournaments,id',
                'league_group_id' => 'Nullable|exists:league_groups,id',
            ], [
                'tournament_id.required' => "Tournament is required.",
                'tournament_id.exists' => "Tournament doesn't exists.",
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'messages' => collect($validator->errors()->all())
                ], 422);
            }
            return $this->tournamentService->tournamentPointsTable($data);
        }
        public function tournamentDetails($id){
            return $this->tournamentService->tournamentDetails($id);
        }
        public function singletournamentDetails($id, Request $request){

            $data = $request->all();
            $data['tournament_id'] = isset($id) ? $id : 0;
            $validator = Validator::make($data, [
                'tournament_id' => 'required|exists:tournaments,id',
            ], [
                'tournament_id.required' => "Tournament is required.",
                'tournament_id.exists' => "Tournament doesn't exist.",
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'messages' => collect($validator->errors()->all())
                ], 422);
            }
            return $this->tournamentService->singletournamentDetails($data);
        }

        public function tournamentScore($id){
            return $this->tournamentService->tournamentScore($id);
        }
        //Tournament-team-end



    public function addRound(Request $request){
        $data = $request->all();
        // return Auth::id();
        // foreach($data as $d){

            $validator = Validator::make($data,[
                'tournament_round' => 'required|string|max:191',
                'tournament_id' => 'required|exists:tournaments,id',
            ],[
                'tournament_round.required' => "Round is required.",
                'tournament_id.required' => 'Tournament is required.',
                'tournament_id.exists' => "Tournament doesn`t exist.",
            ]);

            if($validator->fails()){
                return response()->json($validator->errors(), 422);
            }
        // }

        return $this->tournamentService->addRound($data);
    }


         //Round - end


    //Club-start

    public function addTeam(Request $request){

        $validator = Validator::make($request->all(),[
            'team_name' => 'required|string|max:191',
            'unique_name' => 'required|string|max:191',
            'short_name' => 'nullable|string|max:500',
            'city' => 'required|string|max:191',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 401);
        }
        return $this->tournamentService->addTeam($request->all());
    }


    public function editTeam(Request $request){
        $validator = Validator::make($request->all(),[
            'club_id' => 'required|exists:clubs,id',
            'team_name' => 'required|string|max:191',
            'unique_name' => 'required|string|max:191',
            'short_name' => 'nullable|string|max:500',
            'city' => 'required|string|max:191',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 401);
        }
        return $this->tournamentService->editTeam($request->all());
    }

    public function deleteTeam(Request $request){
        $validator = Validator::make($request->all(),[
            'club_id' => 'required|exists:clubs,id',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 401);
        }
        return $this->tournamentService->deleteTeam($request->all());
    }

    //Tournament-start
    public function addTournament(Request $request){

        $validator = Validator::make($request->all(),[
            'tournament_id' => 'required',
            'team_id' => 'required',
            'roll' => 'required|string|max:191',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 401);
        }
        return $this->tournamentService->addTournament($request->all());
    }

    public function removeTournament(Request $request){
        $validator = Validator::make($request->all(),[
            'team_id' => 'required|exists:playing_teams,id',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 401);
        }
        return $this->tournamentService->removeTournament($request->all());
    }
    //Tournament-end

    //Group-start
    // public function addGroup(Request $request){

    //     $validator = Validator::make($request->all(),[
    //         'name' => 'required',
    //         'round_id' => 'required',
    //     ],[
    //         'name.required' => "Group is required.",
    //         'name.round_id' => "Round is required.",
    //     ]);

    //     if($validator->fails()){
    //         return response()->json($validator->errors(), 401);
    //     }
    //     return $this->tournamentService->addGroup($request->all());
    // }
    // public function editGroup(Request $request){

    //     $validator = Validator::make($request->all(),[
    //         'group_id' => 'required',
    //         'name' => 'required',
    //         'round_id' => 'required',
    //     ],[
    //         'name.required' => "Group is required.",
    //         'name.round_id' => "Round is required.",
    //     ]);

    //     if($validator->fails()){
    //         return response()->json($validator->errors(), 401);
    //     }
    //     return $this->tournamentService->editGroup($request->all());
    // }

    // public function removeGroup(Request $request){
    //     $validator = Validator::make($request->all(),[
    //         'group_id' => 'required|exists:groups,id',
    //     ]);

    //     if($validator->fails()){
    //         return response()->json($validator->errors(), 401);
    //     }
    //     return $this->tournamentService->removeGroup($request->all());
    // }

    //Group-end

    //Group team -start
    // public function addTeamsInGroup(Request $request){

    //     $validator = Validator::make($request->all(),[
    //         'group_id' => 'required|exists:groups,id',
    //         'round_id' => 'required|exists:rounds,id',
    //         'club_id' => 'required|exists:clubs,id',
    //     ],[
    //         'group_id.required' => "Group is required.",
    //         'group_id.exists' => "Group doesn't exists.",
    //         'round_id.required' => "Round is required.",
    //         'round_id.exists' => "Round doesn't exists.",
    //         'club_id.required' => "Club is required.",
    //         'club_id.exists' => "Club doesn't exists.",
    //     ]);

    //     if($validator->fails()){
    //         return response()->json($validator->errors(), 401);
    //     }
    //     return $this->tournamentService->addTeamsInGroup($request->all());
    // }

    // public function editTeamsInGroup(Request $request){

    //     $validator = Validator::make($request->all(),[
    //         'gteam_id' => 'required|exists:group_teams,id',
    //         'group_id' => 'required|exists:groups,id',
    //         'round_id' => 'required|exists:rounds,id',
    //         'club_id' => 'required|exists:clubs,id',
    //     ],[
    //         'gteam_id.required' => "Group team is required.",
    //         'gteam_id.exists' => "Group team doesn't exists.",
    //         'group_id.required' => "Group is required.",
    //         'group_id.exists' => "Group doesn't exists.",
    //         'round_id.required' => "Round is required.",
    //         'round_id.exists' => "Round doesn't exists.",
    //         'club_id.required' => "Club is required.",
    //         'club_id.exists' => "Club doesn't exists.",
    //     ]);

    //     if($validator->fails()){
    //         return response()->json($validator->errors(), 401);
    //     }
    //     return $this->tournamentService->editTeamsInGroup($request->all());
    // }


    // public function removeTeamsInGroup(Request $request){
    //     $validator = Validator::make($request->all(),[
    //         'gteam_id' => 'required|exists:group_teams,id',
    //     ],[
    //         'gteam_id.required' => "Group team is required.",
    //         'gteam_id.exists' => "Group team doesn't exists.",
    //     ]);

    //     if($validator->fails()){
    //         return response()->json($validator->errors(), 401);
    //     }
    //     return $this->tournamentService->removeTeamsInGroup($request->all());
    // }

    //Group team -end

    //Tournament Group Draw-Start
    public function drawTournamentGroupStage(Request $request){
        // $validator = Validator::make($request->all(),[
        //     'gteam_id' => 'required|exists:group_teams,id',
        // ],[
        //     'gteam_id.required' => "Group team is required.",
        //     'gteam_id.exists' => "Group team doesn't exists.",
        // ]);

        // if($validator->fails()){
        //     return response()->json($validator->errors(), 401);
        // }
        return $this->tournamentService->drawTournamentGroupStage($request->all());
    }

    //    ongoing, upcoming and recent tournaments list start
    public function getTournamentsList(Request $request)
    {
        return [
            'ongoing_tournaments' => $this->tournamentService->getTournamentsList('ONGOING', $request->input('is_private')),
            'upcoming_tournaments' => $this->tournamentService->getTournamentsList('UPCOMING', $request->input('is_private')),
            'recent_tournaments' => $this->tournamentService->getTournamentsList('RECENT', $request->input('is_private')),
        ];
    }


}
