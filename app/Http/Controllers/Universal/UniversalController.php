<?php

namespace App\Http\Controllers\Universal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UniversalController extends Controller
{

    private $universalService;
    public function __construct(UniversalService $universalService)
    {
        $this->universalService = $universalService;
    }
    public function getGlobalSearchResults(Request $request){
        $validator = Validator::make($request->all(), [
            'term' => 'required|string|max:191',
        ], [
            'term.required' => "Search query is required.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        return $this->universalService->getGlobalSearchResults($request->all());
    }


}
