<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{

    private $notificationService;
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    public function notificationMethod(){
        return $this->notificationService->notificationMethod();
    }
    public function createMultipleNotification(){
        return $this->notificationService->createMultipleNotification();
    }
    
    public function createNotification(Request $request){
        return $this->notificationService->createNotification($request->all());
    }
    public function getNotificaiton(Request $request){
        return $this->notificationService->getNotificaiton($request->all());
    }
    public function getNewNotificationCount(){
        return $this->notificationService->getNewNotificationCount();
    }
    public function notificationCountClose(){
        return $this->notificationService->notificationCountClose();
    }
    public function seenOrUnSeenSingleNotification(Request $request){
        $validator = Validator::make($request->all(), [
            'id' =>  'required|exists:notifications,id',
            'seen' =>  'required',
        ], [
            'id.exists' => "Notification doesn't exist.",
            'seen.required' => "invalid data format.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->notificationService->seenOrUnSeenSingleNotification($request->all());
    }
    public function deleteNotification(Request $request){
        $validator = Validator::make($request->all(), [
            'id' =>  'required|exists:notifications,id',
        ], [
            'id.exists' => "Notification doesn't exist.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        return $this->notificationService->deleteNotification($request->all());
    }
    public function seenAllNotification(){
        
        return $this->notificationService->seenAllNotification();
    }

    public function test_pushNotis(){
        
        return $this->notificationService->test_pushNotis();
    }
    public function notificationFromTrunamentOwner(Request $request){
      
        $validator = Validator::make($request->all(), [
            'tournament_id' =>'required',
            'title' =>'required',
            'msg' =>'required',
            'type' =>'required',
        ], [
            'tournament_id.required' => "invalid data format.",
            'title.required' => "invalid data format.",
            'msg.required' => "invalid data format.",
            'type.required' => "invalid data format.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        
        return $this->notificationService->notificationFromTrunamentOwner($request->all());
    }
    public function notificationFromClubOwner(Request $request){
      
        $validator = Validator::make($request->all(), [
            'club_id' =>'required',
            'title' =>'required',
            'msg' =>'required',
        ], [
            'tournament_id.required' => "invalid data format.",
            'title.required' => "invalid data format.",
            'msg.required' => "invalid data format.",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        
        return $this->notificationService->notificationFromClubOwner($request->all());
    }
    
    

}
