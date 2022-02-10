<?php

// ROUTE FILES ARE NOT REQUIRED TO IMPORT ANYWHRE.. ITS ADDED AUTOMATICALLY...

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Ground\GroundController;


Route::prefix('api/ground')->middleware('auth:sanctum')->group(function () {

    Route::post('/addGround',  [GroundController::class, 'addGround']);
    Route::post('/updateGroundInfo',  [GroundController::class, 'updateGroundInfo']);
    Route::post('/removeGround',  [GroundController::class, 'removeGround']);
    Route::get('/getGroundList',  [GroundController::class, 'getGroundList']);

});
   Route::get('api/ground/getCountry',  [GroundController::class, 'getCountry']);
   Route::get('api/ground/testing',  [GroundController::class, 'testing']);

