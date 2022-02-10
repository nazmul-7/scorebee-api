<?php

// ROUTE FILES ARE NOT REQUIRED TO IMPORT ANYWHRE.. ITS ADDED AUTOMATICALLY...

use App\Http\Controllers\Team\TeamController;
use Illuminate\Support\Facades\Route;



Route::prefix('api/team')->middleware('auth:sanctum')->group(function () {

//  ============================================ Team CRUD start =============================================
    Route::get('/getOwnerTeamsList', [TeamController::class, 'getOwnerTeamsList']);
    Route::post('/createTeam', [TeamController::class, 'createTeam']);
    Route::post('/updateTeam', [TeamController::class, 'updateTeam']);
    Route::post('/deleteTeam', [TeamController::class, 'deleteTeam']);
//    Route::get('/getSingleTeam/{teamId}', [TeamController::class, 'getSingleTeam']);

//  ============================================ Team CRUD end =============================================

//  ====================================== Team Players CRUD Start ==========================================
    Route::get('/searchClubPlayers', [TeamController::class, 'searchClubPlayers']);
    Route::get('/getTeamPlayersList', [TeamController::class, 'getTeamPlayersList']);
    Route::post('/addTeamPlayer', [TeamController::class, 'addTeamPlayer']);
    Route::post('/updateTeamPlayer', [TeamController::class, 'updateTeamPlayer']);
    Route::post('/removeTeamPlayer', [TeamController::class, 'removeTeamPlayer']);
//  ====================================== Team Players CRUD End ================================================

//  ======================================== Team Squads start ==================================================
    Route::get('/getTeamSquadList', [TeamController::class, 'getTeamSquadList']);
    Route::post('/updateTeamSquad', [TeamController::class, 'updateTeamSquad']);
//  ======================================== Team Squads end ====================================================

//  Team insights start
    Route::get('/getTeamCurrentFormInsights/{teamId}', [TeamController::class, 'getTeamCurrentFormInsights']);
    Route::get('/getTeamTossInsights/{teamId}', [TeamController::class, 'getTeamTossInsights']);
    Route::get('/getTeamOverallInsights/{teamId}', [TeamController::class, 'getTeamOverallInsights']);
    Route::get('/getTeamTopThreeBatsman/{teamId}', [TeamController::class, 'getTeamTopThreeBatsman']);
    Route::get('/getTeamTopThreeBowler/{teamId}', [TeamController::class, 'getTeamTopThreeBowler']);
    Route::get('/getTeamOverallStatistics/{teamId}', [TeamController::class, 'getTeamOverallStatistics']);
});

Route::prefix('api/team')->group(function(){
    Route::get('/getTeamPlayersList', [TeamController::class, 'getTeamPlayersList']);
    Route::get('/getTeamById', [TeamController::class, 'getTeamById']);
});
