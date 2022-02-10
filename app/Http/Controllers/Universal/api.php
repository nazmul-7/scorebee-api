<?php

// ROUTE FILES ARE NOT REQUIRED TO IMPORT ANYWHRE.. ITS ADDED AUTOMATICALLY...

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Universal\UniversalController;


Route::prefix('api/universal')->group(function () {
    Route::get('/getGlobalSearchResults',  [UniversalController::class, 'getGlobalSearchResults']);
});
