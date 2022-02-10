<?php

// ROUTE FILES ARE NOT REQUIRED TO IMPORT ANYWHRE.. ITS ADDED AUTOMATICALLY...

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Notification\NotificationController;


Route::prefix('api/notification')->middleware('auth:sanctum')->group(function () {
    Route::get('/notificationRoute',  [NotificationController::class, 'notificationMethod']);
    Route::post('/createMultipleNotification',  [NotificationController::class, 'createMultipleNotification']);
    Route::post('/createNotification',  [NotificationController::class, 'createNotification']);
    Route::get('/getNotificaiton',  [NotificationController::class, 'getNotificaiton']);
    Route::get('/getNewNotificationCount',  [NotificationController::class, 'getNewNotificationCount']);
    Route::get('/notificationCountClose',  [NotificationController::class, 'notificationCountClose']);
    Route::post('/seenOrUnSeenSingleNotification',  [NotificationController::class, 'seenOrUnSeenSingleNotification']);
    Route::post('/deleteNotification',  [NotificationController::class, 'deleteNotification']);
    Route::get('/seenAllNotification',  [NotificationController::class, 'seenAllNotification']);
    Route::post('/notificationFromTrunamentOwner',  [NotificationController::class, 'notificationFromTrunamentOwner']);
    Route::post('/notificationFromClubOwner',  [NotificationController::class, 'notificationFromClubOwner']);

});
Route::get('/test_pushNotis',  [NotificationController::class, 'test_pushNotis']);

