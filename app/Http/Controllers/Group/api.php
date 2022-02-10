<?php

// ROUTE FILES ARE NOT REQUIRED TO IMPORT ANYWHRE.. ITS ADDED AUTOMATICALLY... 

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Group\GroupController;


Route::prefix('api/group')->group(function () {

    //tournaments-routes
    Route::get('/getTourTeamList/{tournament_id}',  [GroupController::class, 'getTourTeamList']);
    Route::post('/updateTournaments',  [GroupController::class, 'updateTournaments']);
    Route::post('/deleteTournament',  [GroupController::class, 'deleteTournament']);
    Route::get('/getGroupsByTournament/{tournament_id}',  [GroupController::class, 'getGroupsByTournament']);
    //tournaments-ruotes

});
