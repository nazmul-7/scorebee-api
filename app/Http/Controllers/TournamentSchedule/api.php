<?php

// ROUTE FILES ARE NOT REQUIRED TO IMPORT ANYWHRE.. ITS ADDED AUTOMATICALLY...

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TournamentSchedule\TournamentScheduleController;


Route::prefix('api/tournamentSchedule')->middleware('auth:sanctum')->group(function () {
    // Rounds
    Route::get('/getRounds/{tournament_id}',  [TournamentScheduleController::class, 'getRounds']);
    Route::post('/storeRounds',  [TournamentScheduleController::class, 'storeRounds']);
    Route::post('/resetRounds', [TournamentScheduleController::class, 'resetRounds']);
    // Groups
    Route::get('/getGroups/{tournament_id}',  [TournamentScheduleController::class, 'getGroups']);
    Route::get('/getGroupListWithTeam/{tournament_id}',  [TournamentScheduleController::class, 'getGroupListWithTeam']);
    Route::get('/getGroupDetails/{group_id}',  [TournamentScheduleController::class, 'getGroupDetails']);
    Route::post('/storeGroups',  [TournamentScheduleController::class, 'storeGroups']);
    Route::post('/addGroupsTeamsAndInfo',  [TournamentScheduleController::class, 'addGroupsTeamsAndInfo']);


    // Draw
    Route::post('/autoGroupCompleteTournamentDraw',  [TournamentScheduleController::class, 'autoGroupCompleteTournamentDraw']);
    Route::post('/makeTournamentDraw',  [TournamentScheduleController::class, 'makeTournamentDraw']);
    // Teams
    Route::get('/getGlobalTeamList/{tournament_id}', [TournamentScheduleController::class, 'getGlobalTeamList']);
    Route::get('/tournamentTeamList/{tournament_id}', [TournamentScheduleController::class, 'tournamentTeamList']);
    Route::get('/tournamentAvailableTeamList/{tournament_id}', [TournamentScheduleController::class, 'tournamentAvailableTeamList']);
    // Team Request
    Route::post('/sendRequestToTeam', [TournamentScheduleController::class, 'sendRequestToTeam']);
    Route::post('/sendRequestToTournament', [TournamentScheduleController::class, 'sendRequestToTournament']);
    Route::post('/acceptOrCancelTeamRequest', [TournamentScheduleController::class, 'acceptOrCancelTeamRequest']);



});

