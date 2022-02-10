<?php

// ROUTE FILES ARE NOT REQUIRED TO IMPORT ANYWHRE.. ITS ADDED AUTOMATICALLY...

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Home\HomeController;


Route::prefix('api/home')->group(function () {
    Route::get('/getAllMatchesList',  [HomeController::class, 'getAllMatchesList']);
    Route::get('/getMatchesListByType',  [HomeController::class, 'getMatchesListByType']);

    Route::get('/getTournamentsList',  [HomeController::class, 'getTournamentsList']);
    Route::get('/getOnlyOngoingTournamentsList', [HomeController::class, 'getOnlyOngoingTournamentsList']);
    Route::get('/getOnlyUpcomingTournamentsList', [HomeController::class, 'getOnlyUpcomingTournamentsList']);
    Route::get('/getOnlyRecentTournamentsList', [HomeController::class, 'getOnlyRecentTournamentsList']);
    
});
