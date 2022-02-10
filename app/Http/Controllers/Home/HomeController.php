<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HomeController extends Controller
{

    private $homeService;

    public function __construct(HomeService $homepageService)
    {
        $this->homeService = $homepageService;
    }

    public function getAllMatchesList(Request $request)
    {
        return $this->homeService->getAllMatchesList($request->all());
    }

    public function getLiveMatchesList(Request $request)
    {
        return $this->homeService->getLiveMatchesList($request->all());
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
        return $this->homeService->getMatchesListByType($request->all());
    }

    public function getTournamentsList(Request $request)
    {
        return $this->homeService->getTournamentsList($request->all());
    }



}
