<?php

// ROUTE FILES ARE NOT REQUIRED TO IMPORT ANYWHRE.. ITS ADDED AUTOMATICALLY...

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Tournament\TournamentController;

Route::prefix('api/tournament')->middleware('auth:sanctum')->group(function () {

    //tournaments-routes
    Route::get('/getTournaments', [TournamentController::class, 'getTournaments']);
    Route::get('/getAllTournaments', [TournamentController::class, 'getAllTournaments']);
    Route::post('/createTournaments', [TournamentController::class, 'createTournaments']);
    Route::post('/updateTournaments', [TournamentController::class, 'updateTournaments']);
    Route::post('/deleteTournament', [TournamentController::class, 'deleteTournament']);

    Route::get('/tournamentStats/{tournament_id}', [TournamentController::class, 'tournamentStats']);

    //tournaments-ruotes

    //add-tournaments-settings-routes
    Route::post('/tournamentSettings', [TournamentController::class, 'tournamentSettings']);
    //add-tournaments-settings-ruotes

    //Ground-start
    Route::post('/addGroundInTournament', [TournamentController::class, 'addGroundInTournament']);
    Route::get('/tournamentGroundLists', [TournamentController::class, 'tournamentGroundLists']);
    //Ground-end

    //Tournament-fixture
    Route::get('/tournamentFixture/{tournament_id}', [TournamentController::class, 'tournamentFixture']);
    //Tournament-fixture


    //Tournaments-team-start


    Route::get('/tournamentDetails/{tournament_id}', [TournamentController::class, 'tournamentDetails']);
    Route::get('/tournamentScore/{tournament_id}', [TournamentController::class, 'tournamentScore']);

    //Tournaments-team-start


    //rounds-start
    Route::post('/addRound', [TournamentController::class, 'addRound']);
    //rounds-end

    //Team-start
    Route::post('/addTeam', [TournamentController::class, 'addTeam']);
    Route::post('/editTeam', [TournamentController::class, 'editTeam']);
    Route::post('/deleteTeam', [TournamentController::class, 'deleteTeam']);


    //addTournament-start
    Route::post('/addTournament', [TournamentController::class, 'addTournament']);
    Route::post('/removeTournament', [TournamentController::class, 'removeTournament']);
    //addTournament-end

    //Group-start
    Route::post('/addGroup', [TournamentController::class, 'addGroup']);
    Route::post('/editGroup', [TournamentController::class, 'editGroup']);
    Route::post('/removeGroup', [TournamentController::class, 'removeGroup']);
    //Group-end

    //Group Team -start
    Route::post('/addTeamsInGroup', [TournamentController::class, 'addTeamsInGroup']);
    Route::post('/editTeamsInGroup', [TournamentController::class, 'editTeamsInGroup']);
    Route::post('/removeTeamsInGroup', [TournamentController::class, 'removeTeamsInGroup']);
    //Group Team -end

    // Tournament Group Draw


});

Route::prefix('api/tournament')->group(function () {
    Route::get('/getAllTournaments', [TournamentController::class, 'getAllTournaments']);
    Route::get('/getAllTournamentsV2', [TournamentController::class, 'getAllTournamentsV2']);
    //    ongoing, upcoming and recent tournaments list start
    Route::get('getTournamentsList', [TournamentController::class, 'getTournamentsList']);
    Route::get('/tournamentPointsTable/{tournament_id}', [TournamentController::class, 'tournamentPointsTable']);
    Route::get('/tournamentTeamList/{tournament_id}', [TournamentController::class, 'tournamentTeamList']);
    Route::post('/drawTournamentGroupStage', [TournamentController::class, 'drawTournamentGroupStage']);
    Route::get('/getTournamentById', [TournamentController::class, 'getTournamentById']);
    Route::get('/singletournamentDetails/{tournament_id}', [TournamentController::class, 'singletournamentDetails']);
});
