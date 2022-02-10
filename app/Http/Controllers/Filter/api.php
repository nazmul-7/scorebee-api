<?php

// ROUTE FILES ARE NOT REQUIRED TO IMPORT ANYWHRE.. ITS ADDED AUTOMATICALLY...

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Filter\FilterController;


Route::prefix('api/filter')->group(function () {

    // ============================================= Plyer-filtering-tabs-start ========================================
        Route::get('/playerFilteringFromFixture',  [FilterController::class, 'playerFilteringFromFixture']);
        Route::get('/playerFilteringFromTournament',  [FilterController::class, 'playerFilteringFromTournament']);
        Route::get('/playerFilteringFromTeam',  [FilterController::class, 'playerFilteringFromTeam']);
    // ============================================= Plyer-filtering-tabs-end ========================================


    // ========================================== Tournament-filtering-start ==================================
        Route::get('/tournamentYears', [FilterController::class, 'tournamentYears']);
    // ========================================== Tournament-filtering-end ==================================


    // ============================================ LearderBoard-filter-start =======================================
        Route::get('/filteringFromFixture', [FilterController::class, 'filteringFromFixture']);
        Route::get('/filteringFromTournaments',  [FilterController::class, 'filteringFromTournaments']);
        Route::get('/filteringFromTeams',  [FilterController::class, 'filteringFromTeams']);
    // ============================================ LearderBoard-filter-end ========================================

});
