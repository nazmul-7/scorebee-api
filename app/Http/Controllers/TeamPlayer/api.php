<?php

// ROUTE FILES ARE NOT REQUIRED TO IMPORT ANYWHRE.. ITS ADDED AUTOMATICALLY...

use App\Http\Controllers\TeamPlayer\TeamPlayerController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Example\ExampleController;


Route::prefix('api/teamPlayer')->group(function () {

    //Player to team or team to player request start
    Route::post('/sentPlayerRequest', [TeamPlayerController::class, 'sentPlayerRequest']);
//    Route::post('/acceptPlayerRequest', [TeamPlayerController::class, 'acceptPlayerRequest']);
//    Route::post('/cancelPlayerRequest', [TeamPlayerController::class, 'cancelPlayerRequest']);
    //Player to team or team to player request end

});
