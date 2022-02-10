<?php

// ROUTE FILES ARE NOT REQUIRED TO IMPORT ANYWHRE.. ITS ADDED AUTOMATICALLY...

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileHandler\FileHandlerController;


    Route::prefix('api/file-handler')->group(function () {
    Route::post('/imageUploader',  [FileHandlerController::class, 'imageUploader']);

});
