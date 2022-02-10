<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


// ALL NON PROTECTED ROUTES..
Route::prefix('auth')->group(function () {
    // register
    Route::post('register', [AuthController::class, 'register']);

    Route::post('updateProfile', [AuthController::class, 'updateProfile']);

    // login
    Route::post('login', [AuthController::class, 'login']);
    // with this route, we can see if user is logged in or not..
    Route::get('checkIfLoggedInOrNot', [AuthController::class, 'checkIfLoggedInOrNot']);

});




// ALL PROTECTED ROUTES...
Route::prefix('auth')->middleware('auth:sanctum')->group(function () {
    Route::get('/getUser', [AuthController::class, 'getUser']);
    // logout
    Route::get('logout', [AuthController::class, 'logout']);
});


