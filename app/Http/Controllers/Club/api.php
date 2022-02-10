<?php

// ROUTE FILES ARE NOT REQUIRED TO IMPORT ANYWHRE.. ITS ADDED AUTOMATICALLY...

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Club\ClubController;


Route::prefix('api/club')->middleware('auth:sanctum')->group(function () {

//  ====================================== Club to Player request start ================================================
    Route::post('/sentPlayerRequest', [ClubController::class, 'sentPlayerRequest']);
    Route::post('/acceptPlayerRequest', [ClubController::class, 'acceptPlayerRequest']);
    Route::post('/removePlayerRequest', [ClubController::class, 'removePlayerRequest']);
    Route::get('/getPlayerRequestsList', [ClubController::class, 'getPlayerRequestsList']);
    Route::get('/getPlayerRequestsListV2', [ClubController::class, 'getPlayerRequestsListV2']);
    Route::get('/searchPlayers', [ClubController::class, 'searchPlayers']);
//  ====================================== Club to Player request end ==================================================

//  ====================================== Club to Club Challenge request end ==================================================
    Route::get('/getClubChallengeRequests', [ClubController::class, 'getClubChallengeRequests']);
    Route::post('/sentClubChallengeRequest', [ClubController::class, 'sentClubChallengeRequest']);
    Route::post('/acceptClubChallengeRequest', [ClubController::class, 'acceptClubChallengeRequest']);
    Route::post('/cancelClubChallengeRequest', [ClubController::class, 'cancelClubChallengeRequest']);
    Route::get('/getTeamsListByClub', [ClubController::class, 'getTeamsListByClub']);
    Route::get('/myTeams', [ClubController::class, 'myTeams']);

//  ====================================== Club to Club Challenge request end ==================================================

});

Route::prefix('api/club')->group(function () {
//  ====================================== Club details api =====================================================
    Route::get('/getClubById', [ClubController::class, 'getClubById']);
    Route::get('/getClubMatchesListByFilter', [ClubController::class, 'getClubMatchesListByFilter']);
    Route::get('/getClubMembersListByFilter', [ClubController::class, 'getClubMembersListByFilter']);
    Route::get('/getClubStatsListByFilter', [ClubController::class, 'getClubStatsListByFilter']);
    Route::get('/getClubBattingLeaderboardByFilter', [ClubController::class, 'getClubBattingLeaderboardByFilter']);
    Route::get('/getClubBowlingLeaderboardByFilter', [ClubController::class, 'getClubBowlingLeaderboardByFilter']);
    Route::get('/getClubFieldingLeaderboardByFilter', [ClubController::class, 'getClubFieldingLeaderboardByFilter']);
    Route::get('/getClubFilterAttributes', [ClubController::class, 'getClubFilterAttributes']);
//  ====================================== Club details =========================================================
});
