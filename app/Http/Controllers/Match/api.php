<?php

// ROUTE FILES ARE NOT REQUIRED TO IMPORT ANYWHRE.. ITS ADDED AUTOMATICALLY...

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Match\MatchController;

Route::get('/matchComentaryHighlight/{fixture_id}',  [MatchController::class, 'matchComentaryHighlight']);
Route::post('/addMatchOfficial',  [MatchController::class, 'addMatchOfficial']);

Route::get('/getMatchOfficial',  [MatchController::class, 'getMatchOfficial']);

Route::get('/getManOftheMatch/{fixture_id}',  [MatchController::class, 'getManOftheMatch']);
Route::get('/getMatchOfficial_by_fixture/{fixture_id}',  [MatchController::class, 'getMatchOfficial_by_fixture']);
Route::get('/endMatch',  [MatchController::class, 'endMatch']);
Route::get('api/match/getAllMatchesByGroup/{group_id}',  [MatchController::class, 'getAllMatchesByGroup']);
Route::get('api/match/getTournamentMatches/{tournament_id}',  [MatchController::class, 'getTournamentMatches']);
Route::get('api/match/getTournamentMatchesByStatus/{tournament_id}',  [MatchController::class, 'getTournamentMatchesByStatus']);
Route::get('api/match/getTournamentMatchesTwo/{tournament_id}',  [MatchController::class, 'getTournamentMatchesTwo']);

Route::prefix('api/match')->middleware('auth:sanctum')->group(function () {

    //Fixture
    Route::get('/getMatchesByRound/{tournament_id}/{round_type}',  [MatchController::class, 'getMatchesByRound']);
    Route::get('getMyMatchesList', [MatchController::class, 'getMyMatchesList']);

    Route::get('/getPanaltyOrBonusRuns',  [MatchController::class, 'getPanaltyOrBonusRuns']);
    Route::post('/removePanaltyOrBonusRuns',  [MatchController::class, 'removePanaltyOrBonusRuns']);
    Route::post('/storePanalty',  [MatchController::class, 'storePanalty']);
    Route::post('/insertBreakData',  [MatchController::class, 'insertBreakData']);
    Route::post('/insertScorerNotes',  [MatchController::class, 'insertScorerNotes']);
    Route::post('/breakStop',  [MatchController::class, 'breakStop']);
    Route::post('/closestCoordinate',  [MatchController::class, 'closestCoordinate']);
    Route::get('/getFieldCoordinate',  [MatchController::class, 'getFieldCoordinate']);
    Route::get('/getMatchResult',  [MatchController::class, 'getMatchResult']);


    Route::get('/getMatchOfficial',  [MatchController::class, 'getMatchOfficial']);
    Route::post('/addMatchOfficial',  [MatchController::class, 'addMatchOfficial']);
    Route::get('/getMatchOfficial_by_fixture/{fixture_id}',  [MatchController::class, 'getMatchOfficial_by_fixture']);

    Route::get('/getEditableMatchDetails', [MatchController::class, 'getEditableMatchDetails']);
    Route::post('/startAMatch',  [MatchController::class, 'startAMatch']);
    // Route::post('/updateEditableMatchDetails', [MatchController::class, 'updateEditableMatchDetails']);
    Route::post('/startAInnings',  [MatchController::class, 'startAInnings']);
    Route::post('/endAnInnings',  [MatchController::class, 'endAnInnings']);
    Route::post('/getNotOutBatsman',  [MatchController::class, 'getNotOutBatsman']);
    Route::post('/startANewOver',  [MatchController::class, 'startANewOver']);
    Route::post('/addMatchToss',  [MatchController::class, 'addMatchToss']);
    Route::post('/removeMatchOfficial',  [MatchController::class, 'removeMatchOfficial']);
    Route::post('/getAllPlayerOfMatch',  [MatchController::class, 'getAllPlayerOfMatch']);
    Route::post('/getPlyaingElevenWithSubs',  [MatchController::class, 'getPlyaingElevenWithSubs']);
    Route::get('/getMatchInnings/{fixture_id}',  [MatchController::class, 'getMatchInnings']);
    Route::post('/changeStrike',  [MatchController::class, 'changeStrike']);
    Route::post('/changeABatsman',  [MatchController::class, 'changeABatsman']);
    Route::post('/changeWicketkeeper',  [MatchController::class, 'changeWicketkeeper']);
    Route::post('/storeFixtureMedia',  [MatchController::class, 'storeFixtureMedia']);
    Route::post('/endInnings',  [MatchController::class, 'endInnings']);
    Route::post('/endMatch',  [MatchController::class, 'endMatch']);
    Route::post('/unexpectedEndMatch',  [MatchController::class, 'unexpectedEndMatch']);
    // Route::get('/getEndMatchStatus/{fixture_id}',  [MatchController::class, 'getEndMatchStatus']);
    Route::post('/storeManOftheMatch',  [MatchController::class, 'storeManOftheMatch']);
    Route::get('/getManOftheMatch/{fixture_id}',  [MatchController::class, 'getManOftheMatch']);
    Route::post('/replaceBowler',  [MatchController::class, 'replaceBowler']);
    Route::post('/storeDelivery',  [MatchController::class, 'storeDelivery']);
    Route::post('/delDelivery',  [MatchController::class, 'delDelivery']);
    Route::post('/startNextOver', [MatchController::class, 'startNextOver']);
    Route::post('/setNextBatter', [MatchController::class, 'setNextBatter']);
    Route::post('/updateEditableMatchDetails',  [MatchController::class, 'updateEditableMatchDetails']);
    // Route::post('/updateMatchPowerPlays',  [MatchController::class, 'updateMatchPowerPlays']);
    Route::get('/calculateDeliveries/{innings_id}',  [MatchController::class, 'calculateDeliveries']);

    // Route::get('/getSingleMatchWithAllDetails',  [MatchController::class, 'getSingleMatchWithAllDetails']);



    Route::post('/createAnIndividualMatch', [MatchController::class, 'createAnIndividualMatch']);
    // Score-Entry Api
    // Route::post('/storeDelivery',  [MatchController::class, 'storeDelivery']);

    Route::get('/getChallengedMatchesList',  [MatchController::class, 'getChallengedMatchesList']);
});

Route::get('api/match/getAllMatchesList',  [MatchController::class, 'getAllMatchesList']);
Route::get('api/match/getAllMatchesListV2',  [MatchController::class, 'getAllMatchesListV2']);
Route::get('api/match/getSingleMatchWithAllDetails',  [MatchController::class, 'getSingleMatchWithAllDetails']);
Route::get('api/match/getAllLiveMatchesList',  [MatchController::class, 'getAllLiveMatchesList']);
Route::get('api/match/getMatchesListByType',  [MatchController::class, 'getMatchesListByType']);
Route::post('api/match/updateMatchPowerPlays',  [MatchController::class, 'updateMatchPowerPlays']);

Route::get('api/match/getInningsLiveScore/{fixture_id}',  [MatchController::class, 'getInningsLiveScore']);
Route::get('api/match/getSingleMatch/{innings_id}',  [MatchController::class, 'getSingleMatch']);
Route::get('api/match/getEndMatchStatus/{fixture_id}',  [MatchController::class, 'getEndMatchStatus']);
Route::get('api/match/getMatchLiveScore/{inning_id}',  [MatchController::class, 'getMatchLiveScore']);
Route::get('api/match/getStreamMatchLiveScore/{inning_id}',  [MatchController::class, 'getStreamMatchLiveScore']);
Route::get('api/match/getStreamMatchLiveScore_kamran/{inning_id}',  [MatchController::class, 'getStreamMatchLiveScore_kamran']);
Route::get('api/match/shareInnings/{inning_id}',  [MatchController::class, 'shareInnings']);
Route::get('api/match/singleMatchScored/{fixture_id}',  [MatchController::class, 'singleMatchScored']);
Route::get('api/match/singleTeamScored/{inning_id}',  [MatchController::class, 'singleTeamScored']);
Route::get('api/match/deliveriesByOver/{fixture_id}',  [MatchController::class, 'deliveriesByOver']);
Route::get('api/match/getCurrentInningsLive', [MatchController::class, 'getCurrentInningsLive']);
Route::get('api/match/matchComentaryHighlight/{fixture_id}',  [MatchController::class, 'matchComentaryHighlight']);
Route::get('api/match/matchLiveCommentarty/{inning_id}',  [MatchController::class, 'matchLiveCommentarty']);
Route::post('api/match/getPlyaingEleven',  [MatchController::class, 'getPlyaingEleven']);
Route::get('api/match/getCurrentInningsLiveScore', [MatchController::class, 'getCurrentInningsLiveScore']);
