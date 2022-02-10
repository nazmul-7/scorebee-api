<?php

namespace App\Http\Controllers\Filter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FilterController extends Controller
{

    private $filterService;
    public function __construct(FilterService $filterService)
    {
        $this->filterService = $filterService;
    }

    //========================Player-filtering-start ==================================

    public function playerFilteringFromFixture(Request $request)
    {

        $data = $request->all();
        $validator = Validator::make($data, [
            'player_id' => 'nullable|exists:users,id',
            'status' => 'required|string|in:years,overs,innings,balls',
        ], [
            'player_id.required' => "Player is required.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->filterService->playerFilteringFromFixture($data);
    }

    public function playerFilteringFromTournament(Request $request)
    {

        $data = $request->all();
        $validator = Validator::make($data, [
            'player_id' => 'nullable|exists:users,id',
            'status' => 'required|string|in:tournaments,category',
        ], [
            'player_id.required' => "Player is required.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->filterService->playerFilteringFromTournament($data);
    }

    public function playerFilteringFromTeam(Request $request)
    {

        $data = $request->all();
        $validator = Validator::make($data, [
            'player_id' => 'nullable|exists:users,id',
            'status' => 'required|string',
        ], [
            'player_id.required' => "Player is required.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->filterService->playerFilteringFromTeam($data);
    }

    //========================Player-filtering-end ==================================


    //========================== Tournament-filter-start =================================
    public function tournamentYears(Request $request){
        return $this->filterService->tournamentYears($request->all());
    }
    //========================== Tournament-filter-end =================================


    //========================== LeaderBoard-filter-start =================================
    public function filteringFromFixture(Request $request){

        $data = $request->all();

        $validator = Validator::make($data, [
            'status' => 'required|string|in:years,overs,innings,balls',
            'type' => 'required|string|in:leaderboard,my_match',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->filterService->filteringFromFixture($data);

    }

    public function filteringFromTournaments(Request $request){

        $data = $request->all();

        $validator = Validator::make($data, [
            'status' => 'required|string|in:tournaments,category,year',
            'type' => 'required|string|in:leaderboard,my_match',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->filterService->filteringFromTournaments($data);
    }

    public function filteringFromTeams(Request $request){

        $data = $request->all();

        $validator = Validator::make($data, [
            'status' => 'required|string|in:teams',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->filterService->filteringFromTeams($data);

    }
    //========================== LeaderBoard-filter-end =================================

}
