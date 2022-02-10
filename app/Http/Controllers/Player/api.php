<?php

// ROUTE FILES ARE NOT REQUIRED TO IMPORT ANYWHRE.. ITS ADDED AUTOMATICALLY...

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Player\PlayerController;

Route::prefix('api/player')->group(function () {
    Route::get('/singlePlayerDetails/{player_id}',  [PlayerController::class, 'singlePlayerDetails']);
    Route::get('/playerBattingStats/{player_id}',  [PlayerController::class, 'playerBattingStats']);
    Route::get('/playerBowlingStats/{player_id}',  [PlayerController::class, 'playerBowlingStats']);
    Route::get('/playerFieldingStats/{player_id}',  [PlayerController::class, 'playerFieldingStats']);
    Route::get('/playerCaptainStats/{player_id}',  [PlayerController::class, 'playerCaptainStats']);
    //Player-leaderboard-start
    Route::get('/highestBattingByRun',  [PlayerController::class, 'highestBattingByRun']);
    Route::get('/highestBowlingByRun',  [PlayerController::class, 'highestBowlingByRun']);
    Route::get('/highestFieldingByDismissal',  [PlayerController::class, 'highestFieldingByDismissal']);
    //Player-leaderboard-end

    //forgot-password
    Route::post('/forgotPassword',  [PlayerController::class, 'forgotPassword']);
    Route::post('/verifyEmail',  [PlayerController::class, 'verifyEmail']);
    Route::post('/resetPassword',  [PlayerController::class, 'resetPassword']);

    // Route::get('/playerFilteringFromTournament/{player_id}',  [PlayerController::class, 'playerFilteringFromTournament']);
});

Route::prefix('api/player')->middleware('auth:sanctum')->group(function () {

    Route::post('/updateUserInfo',  [PlayerController::class, 'updateUserInfo']);
    Route::get('/awardInMatches/{player_id}',  [PlayerController::class, 'awardInMatches']);
    Route::get('/awardInTournaments/{player_id}',  [PlayerController::class, 'awardInTournaments']);
    Route::post('/awardsLike',  [PlayerController::class, 'awardsLike']);



//    Club to players request start
    Route::get('/getClubRequestsList',  [PlayerController::class, 'getClubRequestsList']);
    Route::post('/sentClubRequest', [PlayerController::class, 'sentClubRequest']);
    Route::post('/acceptClubRequest', [PlayerController::class, 'acceptClubRequest']);
    Route::post('/removeClubRequest', [PlayerController::class, 'removeClubRequest']);
//    Club to players request end


    //Player-insights-start

    //Batting-insights-start
    Route::get('/playerCurrentFormAndInnings/{player_id}',  [PlayerController::class, 'playerCurrentFormAndInnings']);
    Route::get('/battingWagon/{player_id}',  [PlayerController::class, 'battingWagon']);
    Route::get('/testingWagon/{player_id}',  [PlayerController::class, 'testingWagon']);
    Route::get('/bowlingWagon/{player_id}',  [PlayerController::class, 'bowlingWagon']);
    //Batting-insights-end

    //Bowling-insights-start
    Route::get('/playerCurrentBowlingFormAndInnings/{player_id}',  [PlayerController::class, 'playerCurrentBowlingFormAndInnings']);
    Route::get('/playerBowlingOverallStats/{player_id}',  [PlayerController::class, 'playerBowlingOverallStats']);
    //Bowling-insights-end

    //Compare-insights-start
    Route::get('/playerBattingComparison/{player_id}',  [PlayerController::class, 'playerBattingComparison']);
    Route::get('/compareBattingWagon/{player_id}',  [PlayerController::class, 'compareBattingWagon']);
    Route::get('/playerOpposite/{player_id}',  [PlayerController::class, 'playerBattingComparison']);
    Route::get('/getPlayerList/{player_id}',  [PlayerController::class, 'getPlayerList']);
    Route::get('/playerBowlingComparison/{player_id}',  [PlayerController::class, 'playerBowlingComparison']);
    Route::get('/compareBowlingWagon/{player_id}',  [PlayerController::class, 'compareBowlingWagon']);
    Route::get('/outBetweenRuns/{player_id}',  [PlayerController::class, 'outBetweenRuns']);
    Route::get('/bowlerStatesByYear/{player_id}',  [PlayerController::class, 'bowlerStatesByYear']);
    Route::get('/playerOutTypeComparison', [PlayerController::class, 'playerOutTypeComparison']);

    Route::get('/fieldingCompare/{player_id}',  [PlayerController::class, 'fieldingCompare']);
    Route::get('/battingFaceOff/{player_id}',  [PlayerController::class, 'battingFaceOff']);
    Route::get('/faceOffWagon/{player_id}',  [PlayerController::class, 'faceOffWagon']);
    Route::get('/bowlingFaceOff/{player_id}',  [PlayerController::class, 'bowlingFaceOff']);

    //Compare-insights-end

    //Face off comparison insights start
    Route::get('/getPlayerFaceOffOutsComparison', [PlayerController::class, 'getPlayerFaceOffOutsComparison']);
    //Face off comparison insights end

    //Position-start
    Route::get('/bowlingPostion/{player_id}',  [PlayerController::class, 'bowlingPostion']);
    Route::get('/battingPosition/{player_id}',  [PlayerController::class, 'battingPosition']);

    Route::get('/battingAgainstDifferentBowlers/{player_id}',  [PlayerController::class, 'battingAgainstDifferentBowlers']);
    Route::get('/battingsAvgByPosition/{player_id}',  [PlayerController::class, 'battingsAvgByPosition']);
    //Position-start



    //Player-insights-end



});
